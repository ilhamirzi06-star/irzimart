<?php
session_start();

require_once '../../config/Database.php';

// Validasi autentikasi
if (!isset($_SESSION['user']['iduser'])) {
    header('Location: ../../login.php');
    exit;
}

// Validasi hak akses
$role = $_SESSION['user']['role'] ?? '';

$roleDiizinkan = [
    'Administrator',
    'Master Admin',
    'Manajer Gudang',
    'Kasir / Staf Penjualan',
    'Auditor'
];

if (!in_array($role, $roleDiizinkan, true)) {
    header('Location: ../../index.php');
    exit;
}

// Hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create.php');
    exit;
}

// Validasi CSRF
if (
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])
) {
    $_SESSION['flash_error'] = 'Permintaan tidak valid. Silakan coba kembali.';
    header('Location: create.php');
    exit;
}

// Validasi action
if (($_POST['action'] ?? '') !== 'create') {
    header('Location: create.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

function gagal(string $pesan): void
{
    $_SESSION['flash_error'] = $pesan;
    header('Location: create.php');
    exit;
}

$idUser = (int) $_SESSION['user']['iduser'];

$idMargin = filter_var(
    $_POST['idmargin_penjualan'] ?? null,
    FILTER_VALIDATE_INT
);

$ppn = filter_var(
    $_POST['ppn'] ?? null,
    FILTER_VALIDATE_INT
);

$idBarang = $_POST['idbarang'] ?? [];
$jumlahBarang = $_POST['jumlah'] ?? [];

if (!$idMargin || $ppn === false || $ppn < 0 || $ppn > 100) {
    gagal('Margin atau persentase PPN tidak valid.');
}

if (!is_array($idBarang) || !is_array($jumlahBarang)) {
    gagal('Data barang tidak valid.');
}

if (count($idBarang) === 0 || count($idBarang) !== count($jumlahBarang)) {
    gagal('Tambahkan minimal satu barang.');
}

// Validasi margin melalui VIEW
$stmtMargin = $conn->prepare("
    SELECT *
    FROM vw_margin_penjualan_aktif
    WHERE idmargin_penjualan = ?
      AND status = 1
    LIMIT 1
");

$stmtMargin->bind_param('i', $idMargin);
$stmtMargin->execute();

$margin = $stmtMargin->get_result()->fetch_assoc();
$stmtMargin->close();

if (!$margin) {
    gagal('Margin yang dipilih tidak tersedia atau sudah tidak aktif.');
}

$marginPersen = (float) $margin['persen'];

if ($marginPersen < 0) {
    gagal('Persentase margin tidak valid.');
}

// Gabungkan barang yang dipilih lebih dari satu kali
$barangDipilih = [];

foreach ($idBarang as $index => $id) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    $jumlah = filter_var(
        $jumlahBarang[$index] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!$id || !$jumlah || $jumlah < 1) {
        gagal('Data barang atau jumlah tidak valid.');
    }

    if (!isset($barangDipilih[$id])) {
        $barangDipilih[$id] = 0;
    }

    $barangDipilih[$id] += $jumlah;
}

try {
    $conn->begin_transaction();

    // Ambil data barang dan stok melalui VIEW
    $stmtBarang = $conn->prepare("
        SELECT *
        FROM vw_barang_penjualan
        WHERE idbarang = ?
          AND status = 1
        LIMIT 1
    ");

    // Siapkan query INSERT transaksi
    $stmtPenjualan = $conn->prepare("
        INSERT INTO penjualan
        (
            subtotal_nilai,
            ppn,
            total_nilai,
            iduser,
            idmargin_penjualan
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    // Siapkan query INSERT detail
    $stmtDetail = $conn->prepare("
        INSERT INTO detail_penjualan
        (
            harga_satuan,
            jumlah,
            subtotal,
            penjualan_idpenjualan,
            idbarang
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    // Siapkan query INSERT kartu stok
    $stmtStok = $conn->prepare("
        INSERT INTO kartu_stok
        (
            jenis_transaksi,
            masuk,
            keluar,
            stock,
            idtransaksi,
            idbarang
        )
        VALUES ('K', 0, ?, ?, ?, ?)
    ");

    $daftarDetail = [];
    $subtotalTransaksi = 0;

    // Validasi stok dan hitung harga
    foreach ($barangDipilih as $id => $jumlah) {

        $stmtBarang->bind_param('i', $id);
        $stmtBarang->execute();

        $barang = $stmtBarang->get_result()->fetch_assoc();

        if (!$barang) {
            throw new RuntimeException('Barang tidak ditemukan atau tidak aktif.');
        }

        $stok = (int) $barang['stok'];

        if ($jumlah > $stok) {
            throw new RuntimeException(
                'Stok ' . $barang['nama'] . ' tidak mencukupi. Stok tersedia: ' . $stok
            );
        }

        $hargaDasar = (int) $barang['harga_dasar'];

        $hargaJual = (int) round(
            $hargaDasar * (1 + $marginPersen / 100)
        );

        $subtotalDetail = $hargaJual * $jumlah;

        $subtotalTransaksi += $subtotalDetail;

        $daftarDetail[] = [
            'idbarang' => (int) $id,
            'jumlah' => $jumlah,
            'harga_satuan' => $hargaJual,
            'subtotal' => $subtotalDetail,
            'stok_awal' => $stok
        ];
    }

    if ($subtotalTransaksi <= 0) {
        throw new RuntimeException('Subtotal transaksi tidak valid.');
    }

    // Hitung PPN dan total
    $nominalPpn = (int) round(
        $subtotalTransaksi * $ppn / 100
    );

    $totalTransaksi = $subtotalTransaksi + $nominalPpn;

    // Simpan header penjualan
    $stmtPenjualan->bind_param(
        'iiiii',
        $subtotalTransaksi,
        $ppn,
        $totalTransaksi,
        $idUser,
        $idMargin
    );

    $stmtPenjualan->execute();

    $idPenjualan = $conn->insert_id;

    // Simpan detail dan kartu stok
    foreach ($daftarDetail as $detail) {

        $idBarangSaatIni = $detail['idbarang'];
        $jumlah = $detail['jumlah'];
        $hargaJual = $detail['harga_satuan'];
        $subtotalDetail = $detail['subtotal'];

        $stmtDetail->bind_param(
            'iiiii',
            $hargaJual,
            $jumlah,
            $subtotalDetail,
            $idPenjualan,
            $idBarangSaatIni
        );

        $stmtDetail->execute();

        // Catat pengeluaran stok
        $stokBaru = $detail['stok_awal'] - $jumlah;

        $stmtStok->bind_param(
            'iiii',
            $jumlah,
            $stokBaru,
            $idPenjualan,
            $idBarangSaatIni
        );

        $stmtStok->execute();
    }

    $conn->commit();

    // Tutup statement
    $stmtBarang->close();
    $stmtPenjualan->close();
    $stmtDetail->close();
    $stmtStok->close();

    // Buat token CSRF baru
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header('Location: detail.php?id=' . $idPenjualan);
    exit;

} catch (Throwable $e) {

    $conn->rollback();

    error_log('Transaksi penjualan gagal: ' . $e->getMessage());

    $_SESSION['flash_error'] = $e instanceof RuntimeException
        ? $e->getMessage()
        : 'Transaksi gagal disimpan. Periksa data dan struktur database.';

    header('Location: create.php');
    exit;
}
