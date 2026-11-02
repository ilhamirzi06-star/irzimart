
<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

// ==========================================
// 1. VALIDASI SESSION DAN HAK AKSES
// ==========================================

if (!isset($_SESSION['user'])) {
    header('Location: ../../login.php');
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$namaUser = $_SESSION['user']['username'] ?? 'User';

$roleDiizinkan = [
    'Administrator',
    'Manajer Gudang',
    'Staf Pengadaan',
    'Master Admin'
];

if (!in_array($role, $roleDiizinkan, true)) {
    header('Location: ../../dashboard.php');
    exit;
}

// ==========================================
// 2. FUNGSI BANTU
// ==========================================

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka): string
{
    return 'Rp ' . number_format((float) $angka, 0, ',', '.');
}

function statusPengadaan($status): array
{
    $daftarStatus = [
        'D' => ['Draft', 'secondary'],
        'M' => ['Menunggu Approval', 'warning'],
        'A' => ['Disetujui', 'success'],
        'R' => ['Ditolak', 'danger'],
        'P' => ['Dipesan', 'primary'],
        'S' => ['Selesai', 'success'],
        'B' => ['Dibatalkan', 'dark']
    ];

    return $daftarStatus[$status] ?? ['Tidak Diketahui', 'secondary'];
}

// ==========================================
// 3. KONEKSI DATABASE
// ==========================================

$database = new Database();
$koneksi = $database->connect();

// ==========================================
// 4. AMBIL DATA PENGADAAN DARI VIEW
// ==========================================

$dataPengadaan = [];
$error = '';

try {
    $query = "SELECT * FROM vw_pengadaan ORDER BY idpengadaan DESC";

    $result = $koneksi->query($query);

    while ($row = $result->fetch_assoc()) {
        $dataPengadaan[] = $row;
    }

} catch (mysqli_sql_exception $e) {
    error_log('Pengadaan index error: ' . $e->getMessage());
    $error = 'Data pengadaan gagal dimuat. Silakan periksa database.';
}

// ==========================================
// 5. HAK AKSES TOMBOL
// ==========================================

$bolehTambah = in_array($role, [
    'Administrator',
    'Staf Pengadaan',
    'Master Admin'
], true);

$bolehApproval = ($role === 'Manajer Gudang');

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Data Pengadaan | IrziMart</title>

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
            max-width: 1150px;
            margin: 32px auto;
            padding: 0 15px;
        }

        .card {
            border-radius: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0, 0, 0, .08);
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

        .table th {
            white-space: nowrap;
        }

        .nomor-po {
            font-weight: bold;
            color: #482875;
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

<!-- ======================================
     NAVBAR
====================================== -->

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

<!-- ======================================
     KONTEN UTAMA
====================================== -->

<div class="main-container">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>
            <h4 class="mb-1">Data Pengadaan</h4>

            <small class="text-muted">
                Pengelolaan Purchase Order dan pengadaan barang.
            </small>
        </div>

        <?php if ($bolehTambah): ?>
            <a href="create.php" class="btn btn-purple btn-sm">
                + Tambah Pengadaan
            </a>
        <?php endif; ?>

    </div>

    <!-- NOTIFIKASI ERROR -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">
            <?= e($error); ?>
        </div>

    <?php endif; ?>

    <!-- TABEL PENGADAAN -->

    <div class="card">

        <div class="card-body p-2">

            <div class="table-responsive">

                <table class="table table-hover table-sm mb-1">

                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>ID Pengadaan</th>
                            <th>Tanggal</th>
                            <th>Vendor</th>
                            <th>Pembuat PO</th>
                            <th>Total Nilai</th>
                            <th>Status</th>
                            <th>Approver</th>
                            <th width="190">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (count($dataPengadaan) > 0): ?>

                            <?php $no = 1; ?>

                            <?php foreach ($dataPengadaan as $row): ?>

                                <?php
                                    [$namaStatus, $warnaStatus] =
                                        statusPengadaan($row['status']);

                                    $isPembuat =
                                        (int) $row['user_iduser'] ===
                                        (int) ($_SESSION['user']['iduser'] ?? 0);

                                    $bolehEdit =
                                        $row['status'] === 'D' &&
                                        (
                                            $isPembuat ||
                                            in_array($role, [
                                                'Administrator',
                                                'Master Admin'
                                            ], true)
                                        );

                                    $bolehProsesApproval =
                                        $bolehApproval &&
                                        $row['status'] === 'M';
                                ?>

                                <tr>

                                    <td><?= $no++; ?></td>

                                    <td class="nomor-po">
                                        PO-<?= e($row['idpengadaan']); ?>
                                    </td>

                                    <td>
                                        <?= e(date(
                                            'd-m-Y H:i',
                                            strtotime($row['timestamp'])
                                        )); ?>
                                    </td>

                                    <td>
                                        <?= e($row['nama_vendor']); ?>
                                    </td>

                                    <td>
                                        <?= e($row['username_pembuat']); ?>
                                    </td>

                                    <td class="fw-semibold">
                                        <?= rupiah($row['total_nilai']); ?>
                                    </td>

                                    <td>
                                        <span class="badge text-bg-<?= e($warnaStatus); ?>">
                                            <?= e($namaStatus); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= !empty($row['username_approver'])
                                            ? e($row['username_approver'])
                                            : '-'; ?>
                                    </td>

                                    <td>

                                        <div class="d-flex gap-1 flex-wrap">

                                            <a
                                                href="detail.php?id=<?= (int) $row['idpengadaan']; ?>"
                                                class="btn btn-info btn-sm text-white"
                                            >
                                                Detail
                                            </a>

                                            <?php if ($bolehEdit): ?>

                                                <a
                                                    href="edit.php?id=<?= (int) $row['idpengadaan']; ?>"
                                                    class="btn btn-warning btn-sm"
                                                >
                                                    Edit
                                                </a>

                                            <?php endif; ?>

                                            <?php if ($bolehProsesApproval): ?>

                                                <a
                                                    href="approval.php?id=<?= (int) $row['idpengadaan']; ?>"
                                                    class="btn btn-success btn-sm"
                                                >
                                                    Approval
                                                </a>

                                            <?php endif; ?>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    Belum ada data pengadaan.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <footer>
        © 2026 IrziMart. Sistem Informasi Manajemen Toko Bangunan.
    </footer>

</div>

</body>
</html>
