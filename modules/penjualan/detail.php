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

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$namaUser = $_SESSION['user']['username'] ?? 'User';

// 3. Validasi ID Penjualan
$idPenjualan = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$idPenjualan || $idPenjualan < 1) {
    header('Location: index.php');
    exit;
}

// 4. Ambil informasi penjualan melalui VIEW
$stmtPenjualan = $conn->prepare("
    SELECT *
    FROM vw_penjualan
    WHERE idpenjualan = ?
    LIMIT 1
");

$stmtPenjualan->bind_param('i', $idPenjualan);
$stmtPenjualan->execute();

$penjualan = $stmtPenjualan->get_result()->fetch_assoc();
$stmtPenjualan->close();

if (!$penjualan) {
    http_response_code(404);
    exit('Data penjualan tidak ditemukan.');
}

// 5. Ambil detail barang melalui VIEW
$stmtDetail = $conn->prepare("
    SELECT *
    FROM vw_detail_penjualan
    WHERE penjualan_idpenjualan = ?
    ORDER BY iddetail_penjualan ASC
");

$stmtDetail->bind_param('i', $idPenjualan);
$stmtDetail->execute();

$detailResult = $stmtDetail->get_result();

$daftarDetail = [];

while ($row = $detailResult->fetch_assoc()) {
    $daftarDetail[] = $row;
}

$stmtDetail->close();

// 6. Hitung informasi transaksi
$subtotal = (int) $penjualan['subtotal_nilai'];
$ppn = (int) $penjualan['ppn'];
$total = (int) $penjualan['total_nilai'];

$nominalPpn = (int) round(
    $subtotal * $ppn / 100
);

$marginPersen = (float) $penjualan['margin_persen'];

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Detail Penjualan - IrziMart</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f4effa;
            font-family: Arial, sans-serif;
        }

        .navbar {
            background: #482875;
        }

        .navbar-brand {
            color: white !important;
            font-weight: bold;
        }

        .main-container {
            max-width: 1000px;
            margin: 32px auto;
        }

        .card {
            border-radius: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0,0,0,.08);
        }

        .btn-purple {
            background: #8655e8;
            color: white;
        }

        .btn-purple:hover {
            background: #7040cc;
            color: white;
        }

        table th,
        table td {
            vertical-align: middle;
            font-size: 13px;
        }

        .nota {
            max-width: 750px;
            margin: auto;
        }

        footer {
            margin-top: 65px;
            text-align: center;
            font-size: 11px;
            color: #526078;
        }

        @media print {
            body {
                background: white;
            }

            .navbar,
            .no-print,
            footer {
                display: none !important;
            }

            .main-container {
                max-width: 100%;
                margin: 0;
            }

            .card {
                box-shadow: none;
                border: 1px solid #ccc;
            }

            .nota {
                max-width: 100%;
            }
        }
    </style>
</head>

<body>

<!-- Navbar -->
<nav class="navbar no-print">
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

            <a href="../../logout.php"
               class="btn btn-outline-light btn-sm">
                Keluar
            </a>

        </div>
    </div>
</nav>

<div class="container main-container">

    <div class="nota">

        <!-- Judul -->
        <div class="d-flex justify-content-between align-items-center mb-3">

            <div>
                <h4 class="fw-bold mb-1">
                    Detail Penjualan
                </h4>

                <p class="text-muted mb-0">
                    Informasi dan rincian transaksi penjualan.
                </p>
            </div>

            <span class="badge bg-success">
                Berhasil
            </span>

        </div>

        <!-- Informasi transaksi -->
        <div class="card mb-3">

            <div class="card-body">

                <h5 class="fw-bold mb-3">
                    Informasi Penjualan
                </h5>

                <div class="row mb-2">
                    <div class="col-5 text-muted">
                        ID Penjualan
                    </div>

                    <div class="col-7 fw-bold">
                        #<?= e($penjualan['idpenjualan']); ?>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-5 text-muted">
                        Tanggal Penjualan
                    </div>

                    <div class="col-7">
                        <?= e($penjualan['created_at']); ?>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-5 text-muted">
                        Kasir
                    </div>

                    <div class="col-7">
                        <?= e($penjualan['username']); ?>
                    </div>
                </div>

                <div class="row mb-2">
                    <div class="col-5 text-muted">
                        Margin Penjualan
                    </div>

                    <div class="col-7">
                        <?= e($marginPersen); ?>%
                    </div>
                </div>

                <div class="row">
                    <div class="col-5 text-muted">
                        PPN
                    </div>

                    <div class="col-7">
                        <?= e($ppn); ?>%
                    </div>
                </div>

            </div>
        </div>

        <!-- Detail barang -->
        <div class="card mb-3">

            <div class="card-body">

                <h5 class="fw-bold mb-3">
                    Detail Barang yang Dijual
                </h5>

                <div class="table-responsive">

                    <table class="table table-bordered">

                        <thead class="table-light">
                            <tr>
                                <th>No.</th>
                                <th>Nama Barang</th>
                                <th>Satuan</th>
                                <th>Harga Jual</th>
                                <th>Jumlah</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php if (!empty($daftarDetail)): ?>

                            <?php $no = 1; ?>

                            <?php foreach ($daftarDetail as $item): ?>

                                <tr>

                                    <td><?= $no++; ?></td>

                                    <td>
                                        <?= e($item['nama_barang']); ?>
                                    </td>

                                    <td>
                                        <?= e($item['nama_satuan']); ?>
                                    </td>

                                    <td class="text-end">
                                        Rp <?= number_format(
                                            $item['harga_satuan'],
                                            0,
                                            ',',
                                            '.'
                                        ); ?>
                                    </td>

                                    <td class="text-center">
                                        <?= e($item['jumlah']); ?>
                                    </td>

                                    <td class="text-end">
                                        Rp <?= number_format(
                                            $item['subtotal'],
                                            0,
                                            ',',
                                            '.'
                                        ); ?>
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

        <!-- Ringkasan -->
        <div class="card mb-3">

            <div class="card-body">

                <h5 class="fw-bold mb-3">
                    Ringkasan Pembayaran
                </h5>

                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>

                    <strong>
                        Rp <?= number_format(
                            $subtotal,
                            0,
                            ',',
                            '.'
                        ); ?>
                    </strong>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span>PPN (<?= e($ppn); ?>%)</span>

                    <strong>
                        Rp <?= number_format(
                            $nominalPpn,
                            0,
                            ',',
                            '.'
                        ); ?>
                    </strong>
                </div>

                <hr>

                <div class="d-flex justify-content-between fs-5">

                    <strong>Total Pembayaran</strong>

                    <strong>
                        Rp <?= number_format(
                            $total,
                            0,
                            ',',
                            '.'
                        ); ?>
                    </strong>

                </div>

            </div>
        </div>

        <!-- Tombol -->
        <div class="d-flex justify-content-between no-print">

            <a href="index.php" class="btn btn-secondary">
                Kembali
            </a>

            <button type="button"
                    onclick="window.print()"
                    class="btn btn-purple">
                Cetak Nota
            </button>

        </div>

    </div>

</div>

<footer>
    © 2026 IrziMart. Sistem Informasi Manajemen Toko Bangunan.
</footer>

</body>
</html>
