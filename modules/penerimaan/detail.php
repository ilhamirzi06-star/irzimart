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
$namaUser = $_SESSION['user']['username'] ?? 'User';

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

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function tanggalIndonesia($tanggal): string
{
    if (!$tanggal) {
        return '-';
    }

    return date('d-m-Y H:i', strtotime($tanggal));
}

function statusPenerimaan($status): string
{
    switch ($status) {
        case 'D':
            return '<span class="badge bg-warning text-dark">Draft</span>';

        case 'S':
            return '<span class="badge bg-success">Selesai</span>';

        default:
            return '<span class="badge bg-secondary">Tidak Diketahui</span>';
    }
}

/*
|--------------------------------------------------------------------------
| 3. Validasi ID Penerimaan
|--------------------------------------------------------------------------
*/

$idPenerimaan = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idPenerimaan || $idPenerimaan <= 0) {
    $_SESSION['flash_error'] = 'ID penerimaan tidak valid.';
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| 4. Koneksi Database
|--------------------------------------------------------------------------
*/

$database = new Database();
$koneksi = $database->connect();

/*
|--------------------------------------------------------------------------
| 5. Ambil Header Penerimaan
|--------------------------------------------------------------------------
*/

$stmt = $koneksi->prepare(
    "SELECT *
     FROM vw_penerimaan
     WHERE idpenerimaan = ?"
);

$stmt->bind_param('i', $idPenerimaan);
$stmt->execute();

$penerimaan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$penerimaan) {
    $_SESSION['flash_error'] = 'Data penerimaan tidak ditemukan.';
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| 6. Ambil Detail Penerimaan
|--------------------------------------------------------------------------
*/

$stmtDetail = $koneksi->prepare(
    "SELECT *
     FROM vw_detail_penerimaan
     WHERE idpenerimaan = ?
     ORDER BY iddetail_penerimaan ASC"
);

$stmtDetail->bind_param('i', $idPenerimaan);
$stmtDetail->execute();

$resultDetail = $stmtDetail->get_result();

$details = [];
$totalBarang = 0;
$totalNilai = 0;

while ($row = $resultDetail->fetch_assoc()) {

    $details[] = $row;

    $totalBarang += (int) $row['jumlah_terima'];
    $totalNilai += (int) $row['sub_total_terima'];
}

$stmtDetail->close();

/*
|--------------------------------------------------------------------------
| 7. Ambil Sisa Pesanan Setiap Barang
|--------------------------------------------------------------------------
*/

$sisaBarang = [];

$stmtSisa = $koneksi->prepare(
    "SELECT *
     FROM vw_sisa_pengadaan
     WHERE idpengadaan = ?
     ORDER BY iddetail_pengadaan ASC"
);

$stmtSisa->bind_param('i', $penerimaan['idpengadaan']);
$stmtSisa->execute();

$resultSisa = $stmtSisa->get_result();

while ($row = $resultSisa->fetch_assoc()) {
    $sisaBarang[(int) $row['iddetail_pengadaan']] = $row;
}

$stmtSisa->close();

/*
|--------------------------------------------------------------------------
| 8. CSRF Token
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

/*
|--------------------------------------------------------------------------
| 9. Status dan Hak Akses
|--------------------------------------------------------------------------
*/

$isDraft = $penerimaan['status'] === 'D';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detail Penerimaan - IrziMart</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background-color: #f4effa;
            font-family: Arial, sans-serif;
        }

        .navbar {
            background-color: #482875;
        }

        .navbar-brand {
            font-weight: bold;
            color: white !important;
        }

        .main-container {
            max-width: 1100px;
            margin: 32px auto;
        }

        .card {
            border-radius: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0,0,0,.08);
            margin-bottom: 20px;
        }

        .section-title {
            color: #482875;
            font-weight: bold;
        }

        .btn-purple {
            background-color: #8655e8;
            color: white;
        }

        .btn-purple:hover {
            background-color: #7040cc;
            color: white;
        }

        .table th,
        .table td {
            vertical-align: middle;
            font-size: 13px;
        }

        footer {
            margin-top: 65px;
            text-align: center;
            font-size: 11px;
            color: #526078;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid px-3">

        <a class="navbar-brand" href="../../dashboard.php">
            IRZIMART
        </a>

        <div class="d-flex align-items-center gap-2">

            <span class="text-white small">
                <?= e($namaUser); ?>
            </span>

            <a href="index.php" class="btn btn-light btn-sm">
                Kembali
            </a>

            <a href="../../logout.php" class="btn btn-outline-light btn-sm">
                Keluar
            </a>

        </div>

    </div>
</nav>

<div class="container main-container">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">

        <div>
            <h3 class="section-title mb-1">
                Detail Penerimaan Barang
            </h3>

            <p class="text-muted mb-0">
                Informasi dan rincian transaksi penerimaan barang.
            </p>
        </div>

        <?= statusPenerimaan($penerimaan['status']); ?>

    </div>

    <?php if (isset($_SESSION['flash_success'])): ?>

        <div class="alert alert-success">
            <?= e($_SESSION['flash_success']); ?>
        </div>

        <?php unset($_SESSION['flash_success']); ?>

    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>

        <div class="alert alert-danger">
            <?= e($_SESSION['flash_error']); ?>
        </div>

        <?php unset($_SESSION['flash_error']); ?>

    <?php endif; ?>


    <!-- INFORMASI PENERIMAAN -->

    <div class="card">

        <div class="card-body">

            <h5 class="section-title mb-3">
                Informasi Penerimaan
            </h5>

            <div class="row mb-2">

                <div class="col-md-4 text-muted">
                    ID Penerimaan
                </div>

                <div class="col-md-8 fw-bold">
                    #<?= e($penerimaan['idpenerimaan']); ?>
                </div>

            </div>

            <div class="row mb-2">

                <div class="col-md-4 text-muted">
                    Tanggal Penerimaan
                </div>

                <div class="col-md-8">
                    <?= e(tanggalIndonesia($penerimaan['created_at'])); ?>
                </div>

            </div>

            <div class="row mb-2">

                <div class="col-md-4 text-muted">
                    ID Pengadaan
                </div>

                <div class="col-md-8">
                    <a href="../pengadaan/detail.php?id=<?= (int) $penerimaan['idpengadaan']; ?>">
                        #<?= e($penerimaan['idpengadaan']); ?>
                    </a>
                </div>

            </div>

            <div class="row mb-2">

                <div class="col-md-4 text-muted">
                    Vendor
                </div>

                <div class="col-md-8">
                    <?= e($penerimaan['nama_vendor']); ?>
                </div>

            </div>

            <div class="row mb-2">

                <div class="col-md-4 text-muted">
                    Dicatat Oleh
                </div>

                <div class="col-md-8">
                    <?= e($penerimaan['username']); ?>
                </div>

            </div>

            <div class="row">

                <div class="col-md-4 text-muted">
                    Status Penerimaan
                </div>

                <div class="col-md-8">
                    <?= statusPenerimaan($penerimaan['status']); ?>
                </div>

            </div>

        </div>

    </div>


    <!-- DETAIL BARANG -->

    <div class="card">

        <div class="card-body">

            <h5 class="section-title mb-3">
                Detail Barang yang Diterima
            </h5>

            <div class="table-responsive">

                <table class="table table-bordered table-striped">

                    <thead class="table-light">

                        <tr>
                            <th>No.</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th class="text-center">Pesanan</th>
                            <th class="text-center">Jumlah Diterima</th>
                            <th class="text-center">Sisa Setelah Transaksi</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Subtotal</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (count($details) > 0): ?>

                        <?php foreach ($details as $index => $detail): ?>

                            <?php
                            $idDetailPO = (int) $detail['iddetail_pengadaan'];

                            $sisa = $sisaBarang[$idDetailPO]['sisa_pesanan'] ?? 0;

                            $jumlahPesanan = $sisaBarang[$idDetailPO]['jumlah_pesanan'] ?? 0;

                            // View sisa hanya menghitung penerimaan berstatus S.
                            // Pada Draft, jumlah penerimaan saat ini belum dikurangi
                            // dari sisa pesanan.
                            if ($penerimaan['status'] === 'D') {
                                $sisaSetelahTransaksi = (int) $sisa -
                                    (int) $detail['jumlah_terima'];
                            } else {
                                $sisaSetelahTransaksi = (int) $sisa;
                            }
                            ?>

                            <tr>

                                <td>
                                    <?= $index + 1; ?>
                                </td>

                                <td>
                                    <?= e($detail['nama_barang']); ?>
                                </td>

                                <td>
                                    <?= e($detail['nama_satuan']); ?>
                                </td>

                                <td class="text-center">
                                    <?= number_format(
                                        (int) $jumlahPesanan,
                                        0,
                                        ',',
                                        '.'
                                    ); ?>
                                </td>

                                <td class="text-center fw-bold">
                                    <?= number_format(
                                        (int) $detail['jumlah_terima'],
                                        0,
                                        ',',
                                        '.'
                                    ); ?>
                                </td>

                                <td class="text-center">
                                    <?= number_format(
                                        $sisaSetelahTransaksi,
                                        0,
                                        ',',
                                        '.'
                                    ); ?>
                                </td>

                                <td class="text-end">
                                    <?= rupiah($detail['harga_satuan_terima']); ?>
                                </td>

                                <td class="text-end">
                                    <?= rupiah($detail['sub_total_terima']); ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                Belum ada detail penerimaan.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    <!-- RINGKASAN -->

    <div class="card">

        <div class="card-body">

            <h5 class="section-title mb-3">
                Ringkasan Penerimaan
            </h5>

            <div class="row mb-2">

                <div class="col-7 text-muted">
                    Total Jenis Barang
                </div>

                <div class="col-5 text-end fw-bold">
                    <?= count($details); ?> jenis
                </div>

            </div>

            <div class="row mb-2">

                <div class="col-7 text-muted">
                    Total Kuantitas Diterima
                </div>

                <div class="col-5 text-end fw-bold">
                    <?= number_format($totalBarang, 0, ',', '.'); ?>
                </div>

            </div>

            <div class="row mb-2">

                <div class="col-7 text-muted">
                    Total Nilai Penerimaan
                </div>

                <div class="col-5 text-end fw-bold">
                    <?= rupiah($totalNilai); ?>
                </div>

            </div>

            <hr>

            <div class="alert <?= $isDraft ? 'alert-warning' : 'alert-success'; ?> mb-0">

                <?php if ($isDraft): ?>

                    Penerimaan masih berstatus Draft.
                    Stok belum bertambah.

                <?php else: ?>

                    Penerimaan telah dikonfirmasi.
                    Barang sudah diproses sebagai barang masuk.

                <?php endif; ?>

            </div>

        </div>

    </div>


    <!-- AKSI -->

    <div class="d-flex justify-content-end gap-2 flex-wrap">

        <a href="index.php" class="btn btn-secondary">
            Kembali
        </a>

        <?php if ($isDraft): ?>

            <form action="proses.php" method="POST"
                  onsubmit="return confirm('Konfirmasi penerimaan ini? Setelah dikonfirmasi, stok akan bertambah dan transaksi tidak dapat dikonfirmasi ulang.');">

                <input type="hidden" name="csrf_token"
                       value="<?= e($csrfToken); ?>">

                <input type="hidden" name="action"
                       value="confirm">

                <input type="hidden" name="idpenerimaan"
                       value="<?= e($idPenerimaan); ?>">

                <button type="submit" class="btn btn-success">
                    Konfirmasi Penerimaan
                </button>

            </form>

        <?php endif; ?>

    </div>

</div>

<footer>
    © 2026 IrziMart. Sistem Informasi Manajemen Toko Bangunan.
</footer>

</body>
</html>
