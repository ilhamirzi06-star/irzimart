
<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

// =====================================================
// 1. VALIDASI SESSION
// =====================================================

if (!isset($_SESSION['user'])) {
    header('Location: ../../login.php');
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$userId = (int)($_SESSION['user']['iduser'] ?? 0);

$rolesPengelola = [
    'Administrator',
    'Staf Pengadaan',
    'Master Admin'
];

$rolesAkses = [
    'Administrator',
    'Manajer Gudang',
    'Staf Pengadaan',
    'Master Admin'
];

if (!in_array($role, $rolesAkses, true) || $userId <= 0) {
    http_response_code(403);
    exit('Anda tidak memiliki akses.');
}

// =====================================================
// 2. HELPER
// =====================================================

function redirectPengadaan(string $url, string $type, string $message): void
{
    $_SESSION[$type] = $message;
    header('Location: ' . $url);
    exit;
}

function validInteger($value, int $minimum = 0): bool
{
    if (!is_scalar($value) || $value === '') {
        return false;
    }

    $number = filter_var($value, FILTER_VALIDATE_INT);

    return $number !== false && $number >= $minimum;
}

function validCsrf(): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $postToken = $_POST['csrf_token'] ?? '';

    return is_string($sessionToken)
        && is_string($postToken)
        && $sessionToken !== ''
        && $postToken !== ''
        && hash_equals($sessionToken, $postToken);
}

function getPengadaanId(): int
{
    $id = filter_var(
        $_POST['idpengadaan'] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($id === false || $id === null || $id <= 0) {
        throw new RuntimeException('ID pengadaan tidak valid.');
    }

    return (int)$id;
}

// =====================================================
// 3. VALIDASI REQUEST DAN CSRF
// =====================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectPengadaan('index.php', 'flash_error', 'Permintaan tidak valid.');
}

if (!validCsrf()) {
    redirectPengadaan(
        'index.php',
        'flash_error',
        'Token keamanan tidak valid. Silakan coba kembali.'
    );
}

$action = $_POST['action'] ?? '';

$actionsValid = [
    'create',
    'update',
    'submit',
    'approve',
    'reject'
];

if (!in_array($action, $actionsValid, true)) {
    redirectPengadaan(
        'index.php',
        'flash_error',
        'Aksi pengadaan tidak dikenal.'
    );
}

// =====================================================
// 4. VALIDASI HAK AKSES BERDASARKAN AKSI
// =====================================================

if (in_array($action, ['create', 'update', 'submit'], true)) {
    if (!in_array($role, $rolesPengelola, true)) {
        redirectPengadaan(
            'index.php',
            'flash_error',
            'Anda tidak memiliki hak untuk mengelola pengadaan.'
        );
    }
}

if (in_array($action, ['approve', 'reject'], true)) {
    if ($role !== 'Manajer Gudang') {
        redirectPengadaan(
            'index.php',
            'flash_error',
            'Hanya Manajer Gudang yang dapat melakukan approval.'
        );
    }
}

// =====================================================
// 5. KONEKSI DATABASE
// =====================================================

$database = new Database();
$koneksi = $database->connect();

$transactionActive = false;
$idPengadaan = 0;

$batasInt = 2147483647;

try {

    // =================================================
    // 6. CREATE DAN UPDATE
    // =================================================

    if ($action === 'create' || $action === 'update') {

        if ($action === 'update') {
            $idPengadaan = getPengadaanId();
        }

        $idVendor = filter_var(
            $_POST['vendor_idvendor'] ?? null,
            FILTER_VALIDATE_INT
        );

        $inputPPN = $_POST['ppn'] ?? '';

        $barangIds = $_POST['barang_id'] ?? [];
        $hargaSatuan = $_POST['harga_satuan'] ?? [];
        $jumlahBarang = $_POST['jumlah'] ?? [];

        // ---------------------------------------------
        // Validasi input
        // ---------------------------------------------

        if ($idVendor === false || $idVendor <= 0) {
            throw new RuntimeException('Vendor wajib dipilih.');
        }

        if (
            !is_scalar($inputPPN)
            || !is_numeric($inputPPN)
            || !is_finite((float)$inputPPN)
            || (float)$inputPPN < 0
            || (float)$inputPPN > 100
        ) {
            throw new RuntimeException(
                'Tarif PPN harus antara 0 sampai 100%.'
            );
        }

        $ppn = round((float)$inputPPN, 2);

        if (
            !is_array($barangIds)
            || !is_array($hargaSatuan)
            || !is_array($jumlahBarang)
            || count($barangIds) === 0
            || count($barangIds) !== count($hargaSatuan)
            || count($barangIds) !== count($jumlahBarang)
        ) {
            throw new RuntimeException(
                'Detail barang pengadaan tidak lengkap.'
            );
        }

        // ---------------------------------------------
        // Mulai transaksi
        // ---------------------------------------------

        $koneksi->begin_transaction();
        $transactionActive = true;

        // ---------------------------------------------
        // Validasi PO ketika update
        // ---------------------------------------------

        if ($action === 'update') {

            $stmtPO = $koneksi->prepare("
                SELECT idpengadaan, user_iduser, status
                FROM pengadaan
                WHERE idpengadaan = ?
                FOR UPDATE
            ");

            $stmtPO->bind_param('i', $idPengadaan);
            $stmtPO->execute();

            $po = $stmtPO->get_result()->fetch_assoc();
            $stmtPO->close();

            if (!$po) {
                throw new RuntimeException(
                    'Data pengadaan tidak ditemukan.'
                );
            }

            if ($po['status'] !== 'D') {
                throw new RuntimeException(
                    'Hanya pengadaan berstatus Draft yang dapat diedit.'
                );
            }

            $isOwner = (int)$po['user_iduser'] === $userId;
            $isAdmin = in_array(
                $role,
                ['Administrator', 'Master Admin'],
                true
            );

            if (!$isOwner && !$isAdmin) {
                throw new RuntimeException(
                    'Anda tidak memiliki hak untuk mengedit pengadaan ini.'
                );
            }
        }

        // ---------------------------------------------
        // Validasi vendor aktif
        // ---------------------------------------------

        $stmtVendor = $koneksi->prepare("
            SELECT idvendor
            FROM vw_vendor
            WHERE idvendor = ?
              AND status = 'A'
        ");

        $stmtVendor->bind_param('i', $idVendor);
        $stmtVendor->execute();

        $vendor = $stmtVendor->get_result()->fetch_assoc();
        $stmtVendor->close();

        if (!$vendor) {
            throw new RuntimeException(
                'Vendor tidak ditemukan atau tidak aktif.'
            );
        }

        // ---------------------------------------------
        // Validasi detail barang
        // ---------------------------------------------

        $detailBarang = [];
        $barangTerpilih = [];
        $subtotalPengadaan = 0;

        $stmtBarang = $koneksi->prepare("
            SELECT idbarang
            FROM vw_barang
            WHERE idbarang = ?
              AND status = 1
        ");

        foreach ($barangIds as $index => $idBarangInput) {

            $hargaInput = $hargaSatuan[$index] ?? null;
            $jumlahInput = $jumlahBarang[$index] ?? null;

            $idBarang = filter_var(
                $idBarangInput,
                FILTER_VALIDATE_INT
            );

            $harga = filter_var(
                $hargaInput,
                FILTER_VALIDATE_INT
            );

            $jumlah = filter_var(
                $jumlahInput,
                FILTER_VALIDATE_INT
            );

            if (
                $idBarang === false
                || $idBarang <= 0
                || $harga === false
                || $harga <= 0
                || $harga > $batasInt
                || $jumlah === false
                || $jumlah <= 0
                || $jumlah > $batasInt
            ) {
                throw new RuntimeException(
                    'Terdapat data barang, harga, atau jumlah yang tidak valid.'
                );
            }

            if (isset($barangTerpilih[$idBarang])) {
                throw new RuntimeException(
                    'Barang yang sama tidak boleh dimasukkan dua kali.'
                );
            }

            $barangTerpilih[$idBarang] = true;

            $stmtBarang->bind_param('i', $idBarang);
            $stmtBarang->execute();

            $barang = $stmtBarang->get_result()->fetch_assoc();

            if (!$barang) {
                throw new RuntimeException(
                    'Barang tidak ditemukan atau sudah tidak aktif.'
                );
            }

            $subtotalDetail = $harga * $jumlah;

            if (
                $subtotalDetail > $batasInt
                || $subtotalPengadaan > $batasInt - $subtotalDetail
            ) {
                throw new RuntimeException(
                    'Subtotal pengadaan melebihi batas penyimpanan.'
                );
            }

            $subtotalPengadaan += $subtotalDetail;

            $detailBarang[] = [
                'idbarang' => (int)$idBarang,
                'harga_satuan' => (int)$harga,
                'jumlah' => (int)$jumlah,
                'sub_total' => (int)$subtotalDetail
            ];
        }

        $stmtBarang->close();

        // ---------------------------------------------
        // Hitung PPN dan total
        // ---------------------------------------------

        $nominalPPN = (int)round(
            $subtotalPengadaan * ($ppn / 100),
            0,
            PHP_ROUND_HALF_UP
        );

        if ($nominalPPN > $batasInt) {
            throw new RuntimeException(
                'Nominal PPN melebihi batas penyimpanan.'
            );
        }

        if ($subtotalPengadaan > $batasInt - $nominalPPN) {
            throw new RuntimeException(
                'Total pengadaan melebihi batas penyimpanan.'
            );
        }

        $totalPengadaan = $subtotalPengadaan + $nominalPPN;

        // ---------------------------------------------
        // Simpan header
        // ---------------------------------------------

        if ($action === 'create') {

            $status = 'D';

            $stmtHeader = $koneksi->prepare("
                INSERT INTO pengadaan (
                    user_iduser,
                    status,
                    vendor_idvendor,
                    subtotal_nilai,
                    ppn,
                    total_nilai
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmtHeader->bind_param(
                'iidii',
                $userId,
                $status,
                $idVendor,
                $subtotalPengadaan,
                $ppn,
                $totalPengadaan
            );

            $stmtHeader->execute();

            $idPengadaan = (int)$koneksi->insert_id;
            $stmtHeader->close();

            if ($idPengadaan <= 0) {
                throw new RuntimeException(
                    'Gagal mendapatkan ID pengadaan.'
                );
            }

        } else {

            $stmtHeader = $koneksi->prepare("
                UPDATE pengadaan
                SET
                    vendor_idvendor = ?,
                    subtotal_nilai = ?,
                    ppn = ?,
                    total_nilai = ?
                WHERE idpengadaan = ?
                  AND status = 'D'
            ");

            $stmtHeader->bind_param(
                'idiii',
                $idVendor,
                $subtotalPengadaan,
                $ppn,
                $totalPengadaan,
                $idPengadaan
            );

            $stmtHeader->execute();

            if ($stmtHeader->affected_rows < 0) {
                throw new RuntimeException(
                    'Gagal memperbarui pengadaan.'
                );
            }

            $stmtHeader->close();

            // Hapus detail lama, lalu simpan detail terbaru.
            $stmtHapus = $koneksi->prepare("
                DELETE FROM detail_pengadaan
                WHERE idpengadaan = ?
            ");

            $stmtHapus->bind_param('i', $idPengadaan);
            $stmtHapus->execute();
            $stmtHapus->close();
        }

        // ---------------------------------------------
        // Simpan detail pengadaan
        // ---------------------------------------------

        $stmtDetail = $koneksi->prepare("
            INSERT INTO detail_pengadaan (
                harga_satuan,
                jumlah,
                sub_total,
                idbarang,
                idpengadaan
            )
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($detailBarang as $detail) {

            $harga = $detail['harga_satuan'];
            $jumlah = $detail['jumlah'];
            $subtotal = $detail['sub_total'];
            $barangId = $detail['idbarang'];

            $stmtDetail->bind_param(
                'iiiii',
                $harga,
                $jumlah,
                $subtotal,
                $barangId,
                $idPengadaan
            );

            $stmtDetail->execute();
        }

        $stmtDetail->close();

        $koneksi->commit();
        $transactionActive = false;

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        if ($action === 'create') {
            redirectPengadaan(
                'detail.php?id=' . $idPengadaan,
                'flash_success',
                'Purchase Order PO-' . $idPengadaan . ' berhasil dibuat sebagai Draft.'
            );
        }

        redirectPengadaan(
            'detail.php?id=' . $idPengadaan,
            'flash_success',
            'Pengadaan berhasil diperbarui.'
        );
    }

    // =================================================
    // 7. SUBMIT: DRAFT MENJADI MENUNGGU APPROVAL
    // =================================================

    if ($action === 'submit') {

        $idPengadaan = getPengadaanId();

        $koneksi->begin_transaction();
        $transactionActive = true;

        $stmtPO = $koneksi->prepare("
            SELECT
                idpengadaan,
                user_iduser,
                status
            FROM pengadaan
            WHERE idpengadaan = ?
            FOR UPDATE
        ");

        $stmtPO->bind_param('i', $idPengadaan);
        $stmtPO->execute();

        $po = $stmtPO->get_result()->fetch_assoc();
        $stmtPO->close();

        if (!$po) {
            throw new RuntimeException(
                'Data pengadaan tidak ditemukan.'
            );
        }

        if ($po['status'] !== 'D') {
            throw new RuntimeException(
                'Hanya pengadaan berstatus Draft yang dapat diajukan.'
            );
        }

        $isOwner = (int)$po['user_iduser'] === $userId;
        $isAdmin = in_array(
            $role,
            ['Administrator', 'Master Admin'],
            true
        );

        if (!$isOwner && !$isAdmin) {
            throw new RuntimeException(
                'Anda tidak memiliki hak untuk mengajukan pengadaan ini.'
            );
        }

        // Pastikan PO memiliki minimal satu detail barang.
        $stmtCekDetail = $koneksi->prepare("
            SELECT COUNT(*) AS jumlah
            FROM detail_pengadaan
            WHERE idpengadaan = ?
        ");

        $stmtCekDetail->bind_param('i', $idPengadaan);
        $stmtCekDetail->execute();

        $jumlahDetail = (int)$stmtCekDetail
            ->get_result()
            ->fetch_assoc()['jumlah'];

        $stmtCekDetail->close();

        if ($jumlahDetail === 0) {
            throw new RuntimeException(
                'Pengadaan tidak dapat diajukan karena belum memiliki barang.'
            );
        }

        // D = Draft, M = Menunggu Approval.
        $stmtSubmit = $koneksi->prepare("
            UPDATE pengadaan
            SET status = 'M'
            WHERE idpengadaan = ?
              AND status = 'D'
        ");

        $stmtSubmit->bind_param('i', $idPengadaan);
        $stmtSubmit->execute();

        if ($stmtSubmit->affected_rows !== 1) {
            $stmtSubmit->close();

            throw new RuntimeException(
                'Status pengadaan gagal diperbarui.'
            );
        }

        $stmtSubmit->close();

        $koneksi->commit();
        $transactionActive = false;

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        redirectPengadaan(
            'detail.php?id=' . $idPengadaan,
            'flash_success',
            'Pengadaan berhasil diajukan dan menunggu approval Manajer Gudang.'
        );
    }

    // =================================================
    // 8. APPROVE / REJECT
    // =================================================

    if ($action === 'approve' || $action === 'reject') {

        $idPengadaan = getPengadaanId();

        $catatan = trim((string)($_POST['catatan_approval'] ?? ''));

        if (mb_strlen($catatan) > 255) {
            throw new RuntimeException(
                'Catatan approval maksimal 255 karakter.'
            );
        }

        if ($action === 'reject' && $catatan === '') {
            throw new RuntimeException(
                'Catatan penolakan wajib diisi.'
            );
        }

        $koneksi->begin_transaction();
        $transactionActive = true;

        $stmtPO = $koneksi->prepare("
            SELECT idpengadaan, status
            FROM pengadaan
            WHERE idpengadaan = ?
            FOR UPDATE
        ");

        $stmtPO->bind_param('i', $idPengadaan);
        $stmtPO->execute();

        $po = $stmtPO->get_result()->fetch_assoc();
        $stmtPO->close();

        if (!$po) {
            throw new RuntimeException(
                'Data pengadaan tidak ditemukan.'
            );
        }

        if ($po['status'] !== 'M') {
            throw new RuntimeException(
                'Pengadaan ini tidak sedang menunggu approval.'
            );
        }

        if ($action === 'approve') {

            $statusBaru = 'A';

            $pesanBerhasil =
                'Pengadaan berhasil disetujui.';

        } else {

            $statusBaru = 'R';

            $pesanBerhasil =
                'Pengadaan berhasil ditolak.';
        }

        $stmtApproval = $koneksi->prepare("
            UPDATE pengadaan
            SET
                status = ?,
                approval_user_id = ?,
                approval_timestamp = NOW(),
                catatan_approval = ?
            WHERE idpengadaan = ?
              AND status = 'M'
        ");

        $stmtApproval->bind_param(
            'sisi',
            $statusBaru,
            $userId,
            $catatan,
            $idPengadaan
        );

        $stmtApproval->execute();

        if ($stmtApproval->affected_rows !== 1) {
            $stmtApproval->close();

            throw new RuntimeException(
                'Perubahan approval gagal disimpan.'
            );
        }

        $stmtApproval->close();

        $koneksi->commit();
        $transactionActive = false;

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        redirectPengadaan(
            'detail.php?id=' . $idPengadaan,
            'flash_success',
            $pesanBerhasil
        );
    }

} catch (Throwable $e) {

    if ($transactionActive) {
        try {
            $koneksi->rollback();
        } catch (Throwable $rollbackError) {
            error_log(
                'Rollback pengadaan: ' . $rollbackError->getMessage()
            );
        }
    }

    error_log(
        'Proses pengadaan error: ' . $e->getMessage()
    );

    $url = $idPengadaan > 0
        ? 'detail.php?id=' . $idPengadaan
        : 'index.php';

    $pesan = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'Terjadi kesalahan saat memproses pengadaan. Periksa log aplikasi.';

    redirectPengadaan(
        $url,
        'flash_error',
        $pesan
    );
}
