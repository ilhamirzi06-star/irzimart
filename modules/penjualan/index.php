<?php
session_start();
require_once '../../config/Database.php';

// 1. Validasi autentikasi
if (!isset($_SESSION['user']['iduser'])) {
    header('Location: ../../login.php');
    exit;
}

// 2. Validasi hak akses
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

$db = new Database();
$conn = $db->connect();

function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$namaUser = $_SESSION['user']['username'] ?? 'User';

$result = $conn->query("
    SELECT *
    FROM vw_penjualan
    ORDER BY idpenjualan DESC
");

$pesan = '';
$tipePesan = 'success';

if (isset($_SESSION['flash_success'])) {
    $pesan = $_SESSION['flash_success'];
} elseif (isset($_SESSION['flash_error'])) {
    $pesan = $_SESSION['flash_error'];
    $tipePesan = 'danger';
}

unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Penjualan - IrziMart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f4effa; font-family:Arial,sans-serif; }
        .navbar { background:#482875; }
        .navbar-brand { color:white!important; font-weight:bold; }
        .main-container { max-width:1100px; margin:32px auto; }
        .card { border-radius:10px; border:1px solid #ddd; box-shadow:0 2px 5px rgba(0,0,0,.08); }
        .btn-purple { background:#8655e8; color:white; }
        .btn-purple:hover { background:#7040cc; color:white; }
        table th, table td { vertical-align:middle; font-size:13px; }
        footer { margin-top:65px; text-align:center; font-size:11px; color:#526078; }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="container-fluid px-3">
        <a class="navbar-brand" href="../../dashboard.php">IRZIMART</a>
        <div class="d-flex align-items-center gap-2">
            <span class="text-white small"><?= e($namaUser); ?></span>
            <a href="../../dashboard.php" class="btn btn-light btn-sm">Dashboard</a>
            <a href="../../logout.php" class="btn btn-outline-light btn-sm">Keluar</a>
        </div>
    </div>
</nav>

<div class="container main-container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Data Penjualan</h4>
            <p class="text-muted mb-0">Daftar transaksi penjualan IrziMart</p>
        </div>
        <a href="create.php" class="btn btn-purple">+ Transaksi Baru</a>
    </div>

    <?php if ($pesan): ?>
        <div class="alert alert-<?= e($tipePesan); ?>">
            <?= e($pesan); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>ID Penjualan</th>
                            <th>Tanggal</th>
                            <th>Kasir</th>
                            <th>Margin</th>
                            <th>Subtotal</th>
                            <th>PPN</th>
                            <th>Total</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php $no = 1; ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td>#<?= e($row['idpenjualan']); ?></td>
                                <td><?= e($row['created_at']); ?></td>
                                <td><?= e($row['username']); ?></td>
                                <td><?= e($row['margin_persen']); ?>%</td>
                                <td>Rp <?= number_format($row['subtotal_nilai'], 0, ',', '.'); ?></td>
                                <td><?= e($row['ppn']); ?>%</td>
                                <td class="fw-bold">
                                    Rp <?= number_format($row['total_nilai'], 0, ',', '.'); ?>
                                </td>
                                <td>
                                    <a href="detail.php?id=<?= (int)$row['idpenjualan']; ?>"
                                       class="btn btn-sm btn-info text-white">Detail</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted">
                                Belum ada transaksi penjualan.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<footer>© 2026 IrziMart. Sistem Informasi Manajemen Toko Bangunan.</footer>
</body>
</html>
