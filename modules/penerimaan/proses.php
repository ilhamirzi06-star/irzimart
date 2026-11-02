
<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

/*
|--------------------------------------------------------------------------
| 1. Validasi Session dan Role
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user'])) {
    header('Location: ../../login.php');
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$userId = (int) ($_SESSION['user']['iduser'] ?? 0);

$allowedRoles = [
    'Administrator',
    'Manajer Gudang',
    'Master Admin'
];

if (!in_array($role, $allowedRoles, true)) {
    $_SESSION['flash_error'] = 'Anda tidak memiliki akses ke modul penerimaan.';
    header('Location: ../../dashboard.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| 2. Helper Function
|--------------------------------------------------------------------------
*/

function redirectPenerimaan(
    string $url,
    string $type,
    string $message
): void {
    $_SESSION[$type] = $message;
    header('Location: ' . $url);
    exit;
}

function validPositiveInteger($value): bool
{
    if (!is_scalar($value) || $value === '') {
        return false;
    }

    $number = filter_var($value, FILTER_VALIDATE_INT);

    return $number !== false && $number > 0;
}

/*
|--------------------------------------------------------------------------
| 3. Validasi Request
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $userId <= 0) {
    redirectPenerimaan(
        'index.php',
        'flash_error',
        'Permintaan tidak valid.'
    );
}

$csrfSession = $_SESSION['csrf_token'] ?? '';
$csrfPost = $_POST['csrf_token'] ?? '';

if (
    !is_string($csrfSession) ||
    !is_string($csrfPost) ||
    $csrfSession === '' ||
    !hash_equals($csrfSession, $csrfPost)
) {
    redirectPenerimaan(
        'index.php',
        'flash_error',
        'Token keamanan tidak valid. Silakan coba kembali.'
    );
}

$action = $_POST['action'] ?? '';

if (!in_array($action, ['create', 'confirm'], true)) {
    redirectPenerimaan(
        'index.php',
        'flash_error',
        'Aksi penerimaan tidak dikenal.'
    );
}

$idPenerimaan = 0;
$idPengadaan = 0;

if ($action === 'confirm') {
    $idPenerimaan = filter_var(
        $_POST['idpenerimaan'] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($idPenerimaan === false || $idPenerimaan <= 0) {
        redirectPenerimaan(
            'index.php',
            'flash_error',
            'ID penerimaan tidak valid.'
        );
    }
}

if ($action === 'create') {
    $idPengadaan = filter_var(
        $_POST['idpengadaan'] ?? null,
        FILTER_VALIDATE_INT
    );

    if ($idPengadaan === false || $idPengadaan <= 0) {
        redirectPenerimaan(
            'index.php',
            'flash_error',
            'ID pengadaan tidak valid.'
        );
    }
}

/*
|--------------------------------------------------------------------------
| 4. Koneksi Database
|--------------------------------------------------------------------------
*/

$database = new Database();
$koneksi = $database->connect();

$transactionStarted = false;

try {

    $koneksi->begin_transaction();
    $transactionStarted = true;

    /*
    |--------------------------------------------------------------------------
    | A. CREATE: Membuat Draft Penerimaan
    |--------------------------------------------------------------------------
    */

    if ($action === 'create') {

        /*
        | Periksa status PO.
        | PO yang boleh diterima adalah A atau P.
        */

        $stmtPO = $koneksi->prepare(
            "SELECT
                idpengadaan,
                status
             FROM pengadaan
             WHERE idpengadaan = ?
             FOR UPDATE"
        );

        $stmtPO->bind_param('i', $idPengadaan);
        $stmtPO->execute();

        $po = $stmtPO->get_result()->fetch_assoc();
        $stmtPO->close();

        if (!$po) {
            throw new RuntimeException(
                'Data pengadaan tidak ditemukan.'
            );
        }

        if (!in_array($po['status'], ['A', 'P'], true)) {
            throw new RuntimeException(
                'Pengadaan belum disetujui atau sudah selesai.'
            );
        }

        /*
        | Validasi array input.
        */

        $detailIds = $_POST['iddetail_pengadaan'] ?? null;
        $jumlahTerima = $_POST['jumlah_terima'] ?? null;

        if (
            !is_array($detailIds) ||
            !is_array($jumlahTerima) ||
            count($detailIds) === 0 ||
            count($detailIds) !== count($jumlahTerima)
        ) {
            throw new RuntimeException(
                'Data detail penerimaan tidak lengkap.'
            );
        }

        $items = [];
        $detailTerpilih = [];
        $totalNilai = 0;

        /*
        | Validasi setiap barang yang diterima.
        */

        foreach ($detailIds as $index => $detailIdRaw) {

            $jumlahRaw = $jumlahTerima[$index] ?? null;

            if (!validPositiveInteger($detailIdRaw)) {
                throw new RuntimeException(
                    'ID detail pengadaan tidak valid.'
                );
            }

            if (!is_scalar($jumlahRaw) || !is_numeric($jumlahRaw)) {
                throw new RuntimeException(
                    'Jumlah penerimaan tidak valid.'
                );
            }

            $detailId = (int) $detailIdRaw;
            $jumlah = filter_var(
                $jumlahRaw,
                FILTER_VALIDATE_INT
            );

            if ($jumlah === false || $jumlah < 0) {
                throw new RuntimeException(
                    'Jumlah penerimaan tidak boleh negatif.'
                );
            }

            if (isset($detailTerpilih[$detailId])) {
                throw new RuntimeException(
                    'Detail barang yang sama tidak boleh dimasukkan dua kali.'
                );
            }

            $detailTerpilih[$detailId] = true;

            // Nilai 0 berarti barang belum diterima.
            if ($jumlah === 0) {
                continue;
            }

            if ($jumlah > 2147483647) {
                throw new RuntimeException(
                    'Jumlah penerimaan terlalu besar.'
                );
            }

            /*
            | Ambil detail PO dan harga dari database.
            | Jangan mempercayai harga dari browser.
            */

            $stmtDetail = $koneksi->prepare(
                "SELECT
                    iddetail_pengadaan,
                    idpengadaan,
                    idbarang,
                    harga_satuan,
                    jumlah
                 FROM detail_pengadaan
                 WHERE iddetail_pengadaan = ?
                   AND idpengadaan = ?"
            );

            $stmtDetail->bind_param(
                'ii',
                $detailId,
                $idPengadaan
            );

            $stmtDetail->execute();

            $detailPO = $stmtDetail
                ->get_result()
                ->fetch_assoc();

            $stmtDetail->close();

            if (!$detailPO) {
                throw new RuntimeException(
                    'Detail barang tidak sesuai dengan pengadaan.'
                );
            }

            /*
            | Hitung akumulasi penerimaan yang sudah dikonfirmasi.
            */

            $stmtTotal = $koneksi->prepare(
                "SELECT
                    COALESCE(SUM(dp.jumlah_terima), 0) AS jumlah_diterima
                 FROM detail_penerimaan dp
                 JOIN penerimaan p
                    ON dp.idpenerimaan = p.idpenerimaan
                 WHERE dp.iddetail_pengadaan = ?
                   AND p.status = 'S'"
            );

            $stmtTotal->bind_param('i', $detailId);
            $stmtTotal->execute();

            $jumlahSudahDiterima = (int) $stmtTotal
                ->get_result()
                ->fetch_assoc()['jumlah_diterima'];

            $stmtTotal->close();

            $sisaPesanan = (int) $detailPO['jumlah'] -
                $jumlahSudahDiterima;

            if ($jumlah > $sisaPesanan) {
                throw new RuntimeException(
                    'Jumlah penerimaan melebihi sisa pesanan barang.'
                );
            }

            $harga = (int) $detailPO['harga_satuan'];
            $subTotal = $harga * $jumlah;

            if ($subTotal > 2147483647) {
                throw new RuntimeException(
                    'Subtotal penerimaan terlalu besar.'
                );
            }

            $totalNilai += $subTotal;

            if ($totalNilai > 2147483647) {
                throw new RuntimeException(
                    'Total nilai penerimaan terlalu besar.'
                );
            }

            $items[] = [
                'iddetail_pengadaan' => $detailId,
                'idbarang' => (int) $detailPO['idbarang'],
                'jumlah_terima' => $jumlah,
                'harga_satuan' => $harga,
                'sub_total' => (int) $subTotal
            ];
        }

        if (count($items) === 0) {
            throw new RuntimeException(
                'Masukkan minimal satu barang dengan jumlah penerimaan lebih dari nol.'
            );
        }

        /*
        | Simpan header penerimaan.
        */

        $status = 'D';

        $stmtHeader = $koneksi->prepare(
            "INSERT INTO penerimaan
                (
                    status,
                    idpengadaan,
                    iduser
                )
             VALUES (?, ?, ?)"
        );

        $stmtHeader->bind_param(
            'sii',
            $status,
            $idPengadaan,
            $userId
        );

        $stmtHeader->execute();

        $idPenerimaanBaru = $koneksi->insert_id;
        $stmtHeader->close();

        /*
        | Simpan detail penerimaan.
        */

        $stmtSimpanDetail = $koneksi->prepare(
            "INSERT INTO detail_penerimaan
                (
                    idpenerimaan,
                    iddetail_pengadaan,
                    barang_idbarang,
                    jumlah_terima,
                    harga_satuan_terima,
                    sub_total_terima
                )
             VALUES (?, ?, ?, ?, ?, ?)"
        );

        foreach ($items as $item) {

            $detailId = $item['iddetail_pengadaan'];
            $barangId = $item['idbarang'];
            $jumlah = $item['jumlah_terima'];
            $harga = $item['harga_satuan'];
            $subTotal = $item['sub_total'];

            $stmtSimpanDetail->bind_param(
                'iiiiii',
                $idPenerimaanBaru,
                $detailId,
                $barangId,
                $jumlah,
                $harga,
                $subTotal
            );

            $stmtSimpanDetail->execute();
        }

        $stmtSimpanDetail->close();

        $koneksi->commit();
        $transactionStarted = false;

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        redirectPenerimaan(
            'detail.php?id=' . $idPenerimaanBaru,
            'flash_success',
            'Draft penerimaan berhasil disimpan. Stok belum bertambah.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | B. CONFIRM: Konfirmasi Penerimaan
    |--------------------------------------------------------------------------
    */

    if ($action === 'confirm') {

        /*
        | Cari PO yang terkait dengan penerimaan.
        */

        $stmtCari = $koneksi->prepare(
            "SELECT
                idpenerimaan,
                idpengadaan,
                status
             FROM penerimaan
             WHERE idpenerimaan = ?"
        );

        $stmtCari->bind_param('i', $idPenerimaan);
        $stmtCari->execute();

        $dataPenerimaan = $stmtCari
            ->get_result()
            ->fetch_assoc();

        $stmtCari->close();

        if (!$dataPenerimaan) {
            throw new RuntimeException(
                'Data penerimaan tidak ditemukan.'
            );
        }

        $idPengadaan = (int) $dataPenerimaan['idpengadaan'];

        /*
        | Kunci PO untuk mencegah konfirmasi bersamaan
        | pada PO yang sama.
        */

        $stmtLockPO = $koneksi->prepare(
            "SELECT idpengadaan, status
             FROM pengadaan
             WHERE idpengadaan = ?
             FOR UPDATE"
        );

        $stmtLockPO->bind_param('i', $idPengadaan);
        $stmtLockPO->execute();

        $po = $stmtLockPO->get_result()->fetch_assoc();
        $stmtLockPO->close();

        if (!$po) {
            throw new RuntimeException(
                'Data pengadaan tidak ditemukan.'
            );
        }

        if (!in_array($po['status'], ['A', 'P'], true)) {
            throw new RuntimeException(
                'Pengadaan tidak dapat menerima barang pada status saat ini.'
            );
        }

        /*
        | Kunci header penerimaan.
        */

        $stmtLockPenerimaan = $koneksi->prepare(
            "SELECT idpenerimaan, status, idpengadaan
             FROM penerimaan
             WHERE idpenerimaan = ?
             FOR UPDATE"
        );

        $stmtLockPenerimaan->bind_param(
            'i',
            $idPenerimaan
        );

        $stmtLockPenerimaan->execute();

        $penerimaan = $stmtLockPenerimaan
            ->get_result()
            ->fetch_assoc();

        $stmtLockPenerimaan->close();

        if (!$penerimaan) {
            throw new RuntimeException(
                'Data penerimaan tidak ditemukan.'
            );
        }

        if ($penerimaan['status'] !== 'D') {
            throw new RuntimeException(
                'Penerimaan hanya dapat dikonfirmasi satu kali.'
            );
        }

        /*
        | Ambil seluruh detail penerimaan.
        */

        $stmtDetail = $koneksi->prepare(
            "SELECT
                iddetail_penerimaan,
                iddetail_pengadaan,
                barang_idbarang,
                jumlah_terima,
                harga_satuan_terima,
                sub_total_terima
             FROM detail_penerimaan
             WHERE idpenerimaan = ?
             ORDER BY iddetail_penerimaan ASC"
        );

        $stmtDetail->bind_param('i', $idPenerimaan);
        $stmtDetail->execute();

        $resultDetail = $stmtDetail->get_result();

        $items = [];

        while ($row = $resultDetail->fetch_assoc()) {
            $items[] = $row;
        }

        $stmtDetail->close();

        if (count($items) === 0) {
            throw new RuntimeException(
                'Penerimaan belum memiliki detail barang.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi Ulang Sisa Pesanan
        |--------------------------------------------------------------------------
        */

        foreach ($items as $item) {

            $detailPOId = (int) $item['iddetail_pengadaan'];
            $barangId = (int) $item['barang_idbarang'];
            $jumlah = (int) $item['jumlah_terima'];

            if ($jumlah <= 0) {
                throw new RuntimeException(
                    'Jumlah penerimaan harus lebih dari nol.'
                );
            }

            /*
            | Pastikan detail PO masih valid.
            */

            $stmtPOItem = $koneksi->prepare(
                "SELECT
                    iddetail_pengadaan,
                    idpengadaan,
                    idbarang,
                    jumlah
                 FROM detail_pengadaan
                 WHERE iddetail_pengadaan = ?
                   AND idpengadaan = ?
                 FOR UPDATE"
            );

            $stmtPOItem->bind_param(
                'ii',
                $detailPOId,
                $idPengadaan
            );

            $stmtPOItem->execute();

            $detailPO = $stmtPOItem
                ->get_result()
                ->fetch_assoc();

            $stmtPOItem->close();

            if (
                !$detailPO ||
                (int) $detailPO['idbarang'] !== $barangId
            ) {
                throw new RuntimeException(
                    'Detail penerimaan tidak sesuai dengan detail pengadaan.'
                );
            }

            /*
            | Hitung penerimaan sebelumnya yang sudah selesai.
            */

            $stmtTotal = $koneksi->prepare(
                "SELECT
                    COALESCE(SUM(dp.jumlah_terima), 0) AS jumlah_diterima
                 FROM detail_penerimaan dp
                 JOIN penerimaan p
                    ON dp.idpenerimaan = p.idpenerimaan
                 WHERE dp.iddetail_pengadaan = ?
                   AND p.status = 'S'"
            );

            $stmtTotal->bind_param('i', $detailPOId);
            $stmtTotal->execute();

            $jumlahSebelumnya = (int) $stmtTotal
                ->get_result()
                ->fetch_assoc()['jumlah_diterima'];

            $stmtTotal->close();

            $sisa = (int) $detailPO['jumlah'] - $jumlahSebelumnya;

            if ($jumlah > $sisa) {
                throw new RuntimeException(
                    'Konfirmasi dibatalkan. Jumlah penerimaan melebihi sisa pesanan.'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi ID Transaksi untuk Kartu Stok
        |--------------------------------------------------------------------------
        |
        | kartu_stok.idtransaksi bertipe INT.
        | penerimaan.idpenerimaan bertipe BIGINT.
        */

        if ($idPenerimaan > 2147483647) {
            throw new RuntimeException(
                'ID penerimaan melebihi kapasitas kolom idtransaksi kartu stok.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Catat Barang Masuk ke Kartu Stok
        |--------------------------------------------------------------------------
        */

        foreach ($items as $item) {

            $barangId = (int) $item['barang_idbarang'];
            $jumlahMasuk = (int) $item['jumlah_terima'];

            /*
            | Ambil saldo stok terakhir barang.
            */

            $stmtStok = $koneksi->prepare(
                "SELECT stock
                 FROM kartu_stok
                 WHERE idbarang = ?
                 ORDER BY idkartu_stok DESC
                 LIMIT 1
                 FOR UPDATE"
            );

            $stmtStok->bind_param('i', $barangId);
            $stmtStok->execute();

            $stokTerakhir = $stmtStok
                ->get_result()
                ->fetch_assoc();

            $stmtStok->close();

            $stokSebelumnya = $stokTerakhir
                ? (int) $stokTerakhir['stock']
                : 0;

            $stokBaru = $stokSebelumnya + $jumlahMasuk;

            if ($stokBaru > 2147483647) {
                throw new RuntimeException(
                    'Saldo stok melebihi batas nilai yang diperbolehkan.'
                );
            }

            /*
            | M = Barang Masuk
            | K = Barang Keluar
            */

            $jenisTransaksi = 'M';
            $masuk = $jumlahMasuk;
            $keluar = 0;
            $idTransaksi = $idPenerimaan;

            $stmtKartu = $koneksi->prepare(
                "INSERT INTO kartu_stok
                    (
                        jenis_transaksi,
                        masuk,
                        keluar,
                        stock,
                        idtransaksi,
                        idbarang
                    )
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            $stmtKartu->bind_param(
                'siiiii',
                $jenisTransaksi,
                $masuk,
                $keluar,
                $stokBaru,
                $idTransaksi,
                $barangId
            );

            $stmtKartu->execute();
            $stmtKartu->close();
        }

        /*
        |--------------------------------------------------------------------------
        | Ubah Status Penerimaan Menjadi Selesai
        |--------------------------------------------------------------------------
        */

        $statusPenerimaanBaru = 'S';

        $stmtUpdatePenerimaan = $koneksi->prepare(
            "UPDATE penerimaan
             SET status = ?
             WHERE idpenerimaan = ?
               AND status = 'D'"
        );

        $stmtUpdatePenerimaan->bind_param(
            'si',
            $statusPenerimaanBaru,
            $idPenerimaan
        );

        $stmtUpdatePenerimaan->execute();

        if ($stmtUpdatePenerimaan->affected_rows !== 1) {
            $stmtUpdatePenerimaan->close();

            throw new RuntimeException(
                'Status penerimaan gagal diperbarui.'
            );
        }

        $stmtUpdatePenerimaan->close();

        /*
        |--------------------------------------------------------------------------
        | Periksa Apakah Seluruh Pesanan Sudah Diterima
        |--------------------------------------------------------------------------
        */

        $stmtSisa = $koneksi->prepare(
            "SELECT COUNT(*) AS jumlah_sisa
             FROM vw_sisa_pengadaan
             WHERE idpengadaan = ?
               AND sisa_pesanan > 0"
        );

        $stmtSisa->bind_param('i', $idPengadaan);
        $stmtSisa->execute();

        $jumlahSisa = (int) $stmtSisa
            ->get_result()
            ->fetch_assoc()['jumlah_sisa'];

        $stmtSisa->close();

        /*
        | P = Masih ada sisa pesanan.
        | S = Semua barang sudah diterima.
        */

        $statusPOBaru = $jumlahSisa > 0 ? 'P' : 'S';

        $stmtUpdatePO = $koneksi->prepare(
            "UPDATE pengadaan
             SET status = ?
             WHERE idpengadaan = ?"
        );

        $stmtUpdatePO->bind_param(
            'si',
            $statusPOBaru,
            $idPengadaan
        );

        $stmtUpdatePO->execute();
        $stmtUpdatePO->close();

        /*
        |--------------------------------------------------------------------------
        | Commit Seluruh Proses
        |--------------------------------------------------------------------------
        */

        $koneksi->commit();
        $transactionStarted = false;

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        redirectPenerimaan(
            'detail.php?id=' . $idPenerimaan,
            'flash_success',
            'Penerimaan berhasil dikonfirmasi. Kartu stok dan status pengadaan telah diperbarui.'
        );
    }

} catch (Throwable $e) {

    if ($transactionStarted) {
        try {
            $koneksi->rollback();
        } catch (Throwable $rollbackError) {
            error_log(
                'Rollback penerimaan: ' . $rollbackError->getMessage()
            );
        }
    }

    error_log(
        'Penerimaan process error: ' . $e->getMessage()
    );

    if ($action === 'create') {
        $url = 'create.php?idpengadaan=' . $idPengadaan;
    } else {
        $url = 'detail.php?id=' . $idPenerimaan;
    }

    redirectPenerimaan(
        $url,
        'flash_error',
        $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Terjadi kesalahan saat memproses penerimaan. Periksa kembali database.'
    );
}
