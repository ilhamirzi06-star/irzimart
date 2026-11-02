<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../../login.php');
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$namaUser = $_SESSION['user']['username'] ?? 'User';

if ($role !== 'Manajer Gudang') {
    $_SESSION['flash_error'] = 'Halaman ini hanya dapat diakses oleh Manajer Gudang.';
    header('Location: index.php');
    exit;
}

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

$idPengadaan = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idPengadaan || $idPengadaan <= 0) {
    $_SESSION['flash_error'] = 'ID pengadaan tidak valid.';
    header('Location: index.php');
    exit;
}

$database = new Database();
$koneksi = $database->connect();

/*
|--------------------------------------------------------------------------
| Ambil Data Pengadaan
|--------------------------------------------------------------------------
*/

$stmt = $koneksi->prepare(
    "SELECT *
     FROM vw_pengadaan
     WHERE idpengadaan = ?"
);

$stmt->bind_param('i', $idPengadaan);
$stmt->execute();

$pengadaan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pengadaan) {
    $_SESSION['flash_error'] = 'Data pengadaan tidak ditemukan.';
    header('Location: index.php');
    exit;
}

if ($pengadaan['status'] !== 'M') {
    $_SESSION['flash_error'] = 'Pengadaan ini tidak sedang menunggu approval.';
    header('Location: detail.php?id=' . $idPengadaan);
    exit;
}

/*
|--------------------------------------------------------------------------
| Ambil Detail Barang
|--------------------------------------------------------------------------
*/

$stmtDetail = $koneksi->prepare(
    "SELECT *
     FROM vw_detail_pengadaan
     WHERE idpengadaan = ?
     ORDER BY iddetail_pengadaan ASC"
);

$stmtDetail->bind_param('i', $idPengadaan);
$stmtDetail->execute();

$resultDetail = $stmtDetail->get_result();

$details = [];

while ($row = $resultDetail->fetch_assoc()) {
    $details[] = $row;
}

$stmtDetail->close();

$nominalPPN = (int) round(
    (float) $pengadaan['subtotal_nilai'] *
    ((float) $pengadaan['ppn'] / 100)
);

$totalNilai = (int) $pengadaan['subtotal_nilai'] + $nominalPPN;

/*
|--------------------------------------------------------------------------
| CSRF Token
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Pengadaan - IrziMart</title>

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
            max-width: 1000px;
            margin: 32px auto;
        }

        .card {
            border-radius: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0,0,0,.08);
            margin-bottom: 20px;
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

        .section-title {
            color: #482875;
            font-weight: bold;
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

    <div class="mb-4">
        <h3 class="section-title">Approval Pengadaan</h3>
        <p class="text-muted mb-0">
            Periksa informasi pengadaan sebelum memberikan keputusan.
        </p>
    </div>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger">
            <?= e($_SESSION['flash_error']); ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">

            <h5 class="section-title mb-3">
                Informasi Pengadaan
            </h5>

            <div class="row mb-2">
                <div class="col-md-4 text-muted">ID Pengadaan</div>
                <div class="col-md-8 fw-bold">
                    #<?= e($pengadaan['idpengadaan']); ?>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4 text-muted">Tanggal Pengadaan</div>
                <div class="col-md-8">
                    <?= e(tanggalIndonesia($pengadaan['timestamp'])); ?>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4 text-muted">Vendor</div>
                <div class="col-md-8">
                    <?= e($pengadaan['nama_vendor']); ?>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4 text-muted">Dibuat Oleh</div>
                <div class="col-md-8">
                    <?= e($pengadaan['username_pembuat']); ?>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-md-4 text-muted">Status</div>
                <div class="col-md-8">
                    <span class="badge bg-warning text-dark">
                        Menunggu Approval
                    </span>
                </div>
            </div>

        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <h5 class="section-title mb-3">
                Detail Barang Pengadaan
            </h5>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-center">Jumlah</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (count($details) > 0): ?>
                        <?php foreach ($details as $index => $detail): ?>
                            <tr>
                                <td><?= $index + 1; ?></td>

                                <td>
                                    <?= e($detail['nama_barang']); ?>
                                </td>

                                <td>
                                    <?= e($detail['nama_satuan']); ?>
                                </td>

                                <td class="text-end">
                                    <?= rupiah($detail['harga_satuan']); ?>
                                </td>

                                <td class="text-center">
                                    <?= number_format((int) $detail['jumlah'], 0, ',', '.'); ?>
                                </td>

                                <td class="text-end">
                                    <?= rupiah($detail['sub_total']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                Tidak ada detail barang.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <h5 class="section-title mb-3">
                Ringkasan Pembayaran
            </h5>

            <div class="row mb-2">
                <div class="col-7 text-muted">Subtotal</div>
                <div class="col-5 text-end fw-bold">
                    <?= rupiah($pengadaan['subtotal_nilai']); ?>
                </div>
            </div>

            <div class="row mb-2">
                <div class="col-7 text-muted">
                    PPN (<?= number_format((float) $pengadaan['ppn'], 2, ',', '.'); ?>%)
                </div>
                <div class="col-5 text-end">
                    <?= rupiah($nominalPPN); ?>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-7 fw-bold">Total Pengadaan</div>
                <div class="col-5 text-end fw-bold text-success fs-5">
                    <?= rupiah($totalNilai); ?>
                </div>
            </div>

        </div>
    </div>

    <div class="card">
        <div class="card-body">

            <h5 class="section-title mb-3">
                Keputusan Approval
            </h5>

            <form action="proses.php" method="POST">

                <input type="hidden" name="csrf_token"
                       value="<?= e($csrfToken); ?>">

                <input type="hidden" name="idpengadaan"
                       value="<?= e($idPengadaan); ?>">

                <div class="mb-3">
                    <label for="catatan_approval" class="form-label">
                        Catatan Approval
                    </label>

                    <textarea
                        name="catatan_approval"
                        id="catatan_approval"
                        class="form-control"
                        rows="3"
                        maxlength="255"
                        placeholder="Masukkan catatan atau alasan keputusan..."
                    ></textarea>

                    <small class="text-muted">
                        Maksimal 255 karakter. Catatan wajib diisi jika menolak.
                    </small>
                </div>

                <div class="d-flex justify-content-end gap-2 flex-wrap">

                    <a href="index.php" class="btn btn-secondary">
                        Batal
                    </a>

                    <button
                        type="submit"
                        name="action"
                        value="reject"
                        class="btn btn-danger"
                        onclick="return confirm('Apakah Anda yakin ingin menolak pengadaan ini?');"
                    >
                        Tolak Pengadaan
                    </button>

                    <button
                        type="submit"
                        name="action"
                        value="approve"
                        class="btn btn-success"
                        onclick="return confirm('Apakah Anda yakin ingin menyetujui pengadaan ini?');"
                    >
                        Setujui Pengadaan
                    </button>

                </div>

            </form>

        </div>
    </div>

</div>

<footer>
    © 2026 IrziMart. Sistem Informasi Manajemen Toko Bangunan.
</footer>

</body>
</html>
