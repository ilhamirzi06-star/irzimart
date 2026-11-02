
<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

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

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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

function tanggalIndonesia($tanggal): string
{
    if (!$tanggal) {
        return '-';
    }

    return date('d-m-Y H:i', strtotime($tanggal));
}

$database = new Database();
$koneksi = $database->connect();

/*
|--------------------------------------------------------------------------
| Ambil Data Penerimaan
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT *
    FROM vw_penerimaan
    ORDER BY idpenerimaan DESC
";

$result = $koneksi->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penerimaan - IrziMart</title>

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

            <a href="../../dashboard.php" class="btn btn-light btn-sm">
                Dashboard
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
            <h3 class="section-title mb-1">Data Penerimaan Barang</h3>
            <p class="text-muted mb-0">
                Kelola transaksi penerimaan barang dari pengadaan.
            </p>
        </div>

        <a href="create.php" class="btn btn-purple">
            + Buat Penerimaan
        </a>
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

    <div class="card">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>ID Penerimaan</th>
                            <th>Tanggal</th>
                            <th>ID Pengadaan</th>
                            <th>Vendor</th>
                            <th>Dicatat Oleh</th>
                            <th>Status</th>
                            <th width="100">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $no = 1; ?>

                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++; ?></td>

                                <td>
                                    #<?= e($row['idpenerimaan']); ?>
                                </td>

                                <td>
                                    <?= e(tanggalIndonesia($row['created_at'])); ?>
                                </td>

                                <td>
                                    #<?= e($row['idpengadaan']); ?>
                                </td>

                                <td>
                                    <?= e($row['nama_vendor']); ?>
                                </td>

                                <td>
                                    <?= e($row['username']); ?>
                                </td>

                                <td>
                                    <?= statusPenerimaan($row['status']); ?>
                                </td>

                                <td>
                                    <a
                                        href="detail.php?id=<?= (int) $row['idpenerimaan']; ?>"
                                        class="btn btn-sm btn-purple"
                                    >
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Belum ada transaksi penerimaan barang.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>

<footer>
    © 2026 IrziMart. Sistem Informasi Manajemen Toko Bangunan.
</footer>

</body>
</html>