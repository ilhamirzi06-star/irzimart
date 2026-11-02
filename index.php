
<?php
session_start();

require_once __DIR__ . '/config/Database.php';

// ==========================================
// VALIDASI LOGIN
// ==========================================
if (!isset($_SESSION['user']['iduser'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['user']['username'] ?? 'User';
$role = $_SESSION['user']['role'] ?? 'Pengguna';

// ==========================================
// KONEKSI DATABASE
// ==========================================
$database = new Database();
$conn = $database->connect();

// Fungsi keamanan output
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ==========================================
// MENGAMBIL DATA STATISTIK
// ==========================================

// Total barang
$qBarang = $conn->query("
    SELECT COUNT(*) AS total
    FROM vw_barang
");

$totalBarang = (int) $qBarang->fetch_assoc()['total'];

// Total vendor
$qVendor = $conn->query("
    SELECT COUNT(*) AS total
    FROM vw_vendor
");

$totalVendor = (int) $qVendor->fetch_assoc()['total'];

// Total transaksi penjualan
$qPenjualan = $conn->query("
    SELECT COUNT(*) AS total
    FROM vw_penjualan
");

$totalPenjualan = (int) $qPenjualan->fetch_assoc()['total'];

// Total nilai penjualan
$qNilai = $conn->query("
    SELECT COALESCE(SUM(total_nilai), 0) AS total
    FROM vw_penjualan
");

$totalNilaiPenjualan = (float) $qNilai->fetch_assoc()['total'];

// Total pengadaan
// Menggunakan tabel pengadaan karena view pengadaan
// belum ditetapkan dalam struktur view yang tersedia.
$qPengadaan = $conn->query("
    SELECT COUNT(*) AS total
    FROM pengadaan
");

$totalPengadaan = (int) $qPengadaan->fetch_assoc()['total'];

// ==========================================
// DATA GRAFIK PENJUALAN BULANAN
// ==========================================

$namaBulan = [
    1 => 'Jan', 2 => 'Feb', 3 => 'Mar',
    4 => 'Apr', 5 => 'Mei', 6 => 'Jun',
    7 => 'Jul', 8 => 'Agu', 9 => 'Sep',
    10 => 'Okt', 11 => 'Nov', 12 => 'Des'
];

$dataGrafik = array_fill(1, 12, 0);

$qGrafik = $conn->query("
    SELECT
        MONTH(created_at) AS bulan,
        SUM(total_nilai) AS total
    FROM vw_penjualan
    WHERE YEAR(created_at) = YEAR(CURDATE())
    GROUP BY MONTH(created_at)
    ORDER BY MONTH(created_at)
");

while ($grafik = $qGrafik->fetch_assoc()) {
    $dataGrafik[(int) $grafik['bulan']] =
        (float) $grafik['total'];
}

$nilaiMaksimum = max($dataGrafik);
if ($nilaiMaksimum <= 0) {
    $nilaiMaksimum = 1;
}

// ==========================================
// TRANSAKSI PENJUALAN TERBARU
// ==========================================

$qTransaksi = $conn->query("
    SELECT *
    FROM vw_penjualan
    ORDER BY idpenjualan DESC
    LIMIT 5
");

// ==========================================
// FORMAT RUPIAH
// ==========================================

function rupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | IrziMart</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
          rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f5f1fa;
            font-family: Arial, sans-serif;
            color: #343044;
        }

        /* SIDEBAR */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: #ffffff;
            padding: 22px 17px;
            overflow-y: auto;
            z-index: 1000;
            border-right: 1px solid #eee9f5;
        }

        .brand {
            height: 55px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
        }

        .brand img {
            max-width: 175px;
            max-height: 48px;
            object-fit: contain;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 5px 22px;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
        }

        .profile-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #eee3ff;
            color: #9555e8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            flex-shrink: 0;
        }

        .profile strong {
            font-size: 13px;
            display: block;
        }

        .profile small {
            font-size: 11px;
            color: #999;
        }

        .menu-label {
            font-size: 10px;
            font-weight: bold;
            color: #aaa;
            letter-spacing: 1px;
            margin: 22px 10px 10px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #625d70;
            font-size: 13px;
            padding: 11px 13px;
            margin-bottom: 5px;
            border-radius: 7px;
            transition: .2s;
        }

        .sidebar a i {
            font-size: 17px;
            width: 20px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #f1e5ff;
            color: #9450e5;
        }

        /* MAIN */
        .main {
            margin-left: 250px;
            min-height: 100vh;
        }

        /* TOPBAR */
        .topbar {
            height: 70px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 15px;
            padding: 0 30px;
            box-shadow: 0 1px 5px rgba(0,0,0,.03);
        }

        .topbar-user {
            font-size: 13px;
            color: #555;
        }

        /* CONTENT */
        .content {
            padding: 30px 32px;
        }

        .page-heading {
            display: flex;
            align-items: center;
            gap: 13px;
            margin-bottom: 28px;
        }

        .heading-icon {
            width: 43px;
            height: 43px;
            border-radius: 8px;
            background: linear-gradient(135deg, #c77dff, #914de8);
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
            box-shadow: 0 4px 10px #dfc6f7;
        }

        .page-heading h4 {
            font-size: 20px;
            font-weight: bold;
            margin: 0;
        }

        .page-heading p {
            font-size: 12px;
            color: #999;
            margin: 5px 0 0;
        }

        /* STATISTICS */
        .stat-card {
            min-height: 155px;
            padding: 23px;
            border-radius: 9px;
            color: white;
            margin-bottom: 22px;
            position: relative;
            overflow: hidden;
        }

        .stat-card::after {
            content: "";
            position: absolute;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: rgba(255,255,255,.13);
            right: -40px;
            top: -45px;
        }

        .stat-card .stat-icon {
            float: right;
            font-size: 24px;
            opacity: .9;
        }

        .stat-card h6 {
            font-size: 13px;
            margin-bottom: 17px;
        }

        .stat-card h3 {
            font-size: 27px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-card small {
            font-size: 11px;
            opacity: .9;
        }

        .purple {
            background: linear-gradient(120deg, #c77dff, #9855ed);
        }

        .blue {
            background: linear-gradient(120deg, #82c8f8, #3299e8);
        }

        .green {
            background: linear-gradient(120deg, #75d8c9, #25c6ad);
        }

        .orange {
            background: linear-gradient(120deg, #ffb68f, #ff789e);
        }

        /* PANEL */
        .panel {
            background: white;
            padding: 24px;
            border-radius: 9px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,.03);
        }

        .panel-title {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        /* CHART */
        .chart-container {
            height: 230px;
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            gap: 10px;
            padding-top: 20px;
        }

        .chart-item {
            flex: 1;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
        }

        .chart-bar {
            width: 65%;
            max-width: 38px;
            min-height: 3px;
            background: linear-gradient(180deg, #c77dff, #9450e5);
            border-radius: 6px 6px 0 0;
            transition: .3s;
        }

        .chart-bar:hover {
            opacity: .7;
        }

        .chart-item span {
            font-size: 10px;
            color: #999;
        }

        /* TABLE */
        .table th {
            font-size: 11px;
            color: #777;
            font-weight: 600;
            background: #faf8fc;
            white-space: nowrap;
        }

        .table td {
            font-size: 12px;
            vertical-align: middle;
        }

        .btn-purple {
            background: #9655e8;
            color: white;
            border: none;
        }

        .btn-purple:hover {
            background: #7840c7;
            color: white;
        }

        /* QUICK ACCESS */
        .quick-menu {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .quick-menu a {
            text-decoration: none;
            color: #5d536e;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 17px 7px;
            text-align: center;
            font-size: 11px;
            transition: .2s;
        }

        .quick-menu a:hover {
            background: #f6efff;
            border-color: #d8baf8;
        }

        .quick-menu i {
            display: block;
            color: #9655e8;
            font-size: 22px;
            margin-bottom: 8px;
        }

        /* FOOTER */
        footer {
            padding: 25px;
            text-align: center;
            color: #999;
            font-size: 11px;
        }

        /* RESPONSIVE */
        @media (max-width: 900px) {
            .sidebar {
                width: 75px;
                padding: 15px 8px;
            }

            .brand img {
                max-width: 48px;
            }

            .sidebar a {
                justify-content: center;
                font-size: 0;
                padding: 13px 5px;
            }

            .sidebar a i {
                font-size: 19px;
            }

            .profile {
                justify-content: center;
            }

            .profile div:last-child,
            .menu-label {
                display: none;
            }

            .main {
                margin-left: 75px;
            }

            .content {
                padding: 20px 15px;
            }
        }

        @media (max-width: 600px) {
            .topbar {
                padding: 0 15px;
            }

            .quick-menu {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>

<body>

<!-- ======================================
     SIDEBAR
====================================== -->
<aside class="sidebar">

    <div class="brand">
        <a href="index.php">
             <img src="assets/images/logo.png" alt="IrziMart">
        </a>
    </div>

    <!-- PROFIL -->
    <div class="profile">
        <div class="profile-icon">
            <i class="bi bi-person-fill"></i>
        </div>

        <div>
            <strong><?= e($username); ?></strong>
            <small><?= e($role); ?></small>
        </div>
    </div>

    <!-- DASHBOARD -->
    <div class="menu-label">MENU UTAMA</div>

    <a href="index.php" class="active">
        <i class="bi bi-grid-fill"></i>
        Dashboard
    </a>

    <!-- MASTER DATA -->
    <div class="menu-label">MASTER DATA</div>

    <a href="modules/barang/index.php">
        <i class="bi bi-box-seam"></i>
        Barang
    </a>

    <a href="modules/satuan/index.php">
        <i class="bi bi-rulers"></i>
        Satuan
    </a>

    <a href="modules/vendor/index.php">
        <i class="bi bi-buildings"></i>
        Vendor
    </a>

    <a href="modules/user/index.php">
        <i class="bi bi-people"></i>
        User
    </a>

    <a href="modules/role/index.php">
        <i class="bi bi-person-badge"></i>
        Role
    </a>

    <!-- TRANSAKSI -->
    <div class="menu-label">TRANSAKSI</div>

    <a href="modules/pengadaan/index.php">
        <i class="bi bi-cart-plus"></i>
        Pengadaan
    </a>

    <a href="modules/penerimaan/index.php">
        <i class="bi bi-box-arrow-in-down"></i>
        Penerimaan
    </a>

    <a href="modules/penjualan/index.php">
        <i class="bi bi-receipt"></i>
        Penjualan
    </a>

    <a href="modules/retur/index.php">
        <i class="bi bi-arrow-counterclockwise"></i>
        Retur
    </a>

    <!-- PENGATURAN -->
    <div class="menu-label">PENGATURAN</div>

    <a href="modules/margin_penjualan/index.php">
        <i class="bi bi-percent"></i>
        Margin Penjualan
    </a>

    <!-- AKUN -->
    <div class="menu-label">AKUN</div>

    <a href="logout.php">
        <i class="bi bi-box-arrow-right"></i>
        Keluar
    </a>

</aside>


<!-- ======================================
     MAIN CONTENT
====================================== -->
<main class="main">

    <!-- TOPBAR -->
    <header class="topbar">

        <span class="topbar-user">
            <i class="bi bi-person-circle me-1"></i>
            <?= e($username); ?>
        </span>

        <span class="badge bg-light text-dark">
            <?= e($role); ?>
        </span>

        <a href="logout.php"
           class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-power"></i>
        </a>

    </header>


    <!-- CONTENT -->
    <div class="content">

        <!-- PAGE TITLE -->
        <div class="page-heading">

            <div class="heading-icon">
                <i class="bi bi-house-fill"></i>
            </div>

            <div>
                <h4>Dashboard</h4>
                <p>
                    Selamat datang di Sistem Informasi
                    Manajemen IrziMart.
                </p>
            </div>

        </div>


        <!-- ==================================
             STATISTIK
        ================================== -->
        <div class="row">

            <div class="col-lg-3 col-md-6">
                <div class="stat-card purple">

                    <i class="bi bi-box-seam stat-icon"></i>

                    <h6>Total Barang</h6>

                    <h3>
                        <?= number_format($totalBarang); ?>
                    </h3>

                    <small>Data barang dalam sistem</small>

                </div>
            </div>


            <div class="col-lg-3 col-md-6">
                <div class="stat-card blue">

                    <i class="bi bi-buildings stat-icon"></i>

                    <h6>Total Vendor</h6>

                    <h3>
                        <?= number_format($totalVendor); ?>
                    </h3>

                    <small>Vendor terdaftar</small>

                </div>
            </div>


            <div class="col-lg-3 col-md-6">
                <div class="stat-card green">

                    <i class="bi bi-receipt stat-icon"></i>

                    <h6>Total Penjualan</h6>

                    <h3>
                        <?= number_format($totalPenjualan); ?>
                    </h3>

                    <small>Transaksi tersimpan</small>

                </div>
            </div>


            <div class="col-lg-3 col-md-6">
                <div class="stat-card orange">

                    <i class="bi bi-cash-stack stat-icon"></i>

                    <h6>Nilai Penjualan</h6>

                    <h3 style="font-size:19px">
                        <?= rupiah($totalNilaiPenjualan); ?>
                    </h3>

                    <small>Akumulasi seluruh transaksi</small>

                </div>
            </div>

        </div>


        <!-- ==================================
             GRAFIK DAN PENGADAAN
        ================================== -->
        <div class="row">

            <!-- GRAFIK PENJUALAN -->
            <div class="col-lg-8">

                <div class="panel">

                    <div class="d-flex justify-content-between align-items-center">

                        <h5 class="panel-title">
                            Grafik Penjualan Tahunan
                        </h5>

                        <span class="badge bg-light text-dark">
                            <?= date('Y'); ?>
                        </span>

                    </div>

                    <div class="chart-container">

                        <?php foreach ($dataGrafik as $bulan => $nilai): ?>

                            <?php
                            $tinggi = ($nilai / $nilaiMaksimum) * 100;
                            ?>

                            <div class="chart-item">

                                <div class="chart-bar"
                                     style="height: <?= max(2, $tinggi); ?>%"
                                     title="<?= e(rupiah($nilai)); ?>">
                                </div>

                                <span>
                                    <?= e($namaBulan[$bulan]); ?>
                                </span>

                            </div>

                        <?php endforeach; ?>

                    </div>

                    <small class="text-muted">
                        Grafik berdasarkan total penjualan per bulan.
                    </small>

                </div>

            </div>


            <!-- RINGKASAN PENGADAAN -->
            <div class="col-lg-4">

                <div class="panel">

                    <h5 class="panel-title">
                        Ringkasan Sistem
                    </h5>

                    <div class="d-flex justify-content-between mb-3">

                        <span class="text-muted small">
                            Total Pengadaan
                        </span>

                        <strong>
                            <?= number_format($totalPengadaan); ?>
                        </strong>

                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-3">

                        <span class="text-muted small">
                            Total Barang
                        </span>

                        <strong>
                            <?= number_format($totalBarang); ?>
                        </strong>

                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">

                        <span class="text-muted small">
                            Total Vendor
                        </span>

                        <strong>
                            <?= number_format($totalVendor); ?>
                        </strong>

                    </div>

                    <hr>

                    <a href="modules/pengadaan/index.php"
                       class="btn btn-purple btn-sm w-100 mt-2">

                        <i class="bi bi-cart-plus me-1"></i>
                        Kelola Pengadaan

                    </a>

                </div>

            </div>

        </div>


        <!-- ==================================
             TRANSAKSI TERBARU
        ================================== -->
        <div class="row">

            <div class="col-lg-8">

                <div class="panel">

                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <h5 class="panel-title mb-0">
                            Transaksi Penjualan Terbaru
                        </h5>

                        <a href="modules/penjualan/index.php"
                           class="btn btn-purple btn-sm">

                            Lihat Semua

                        </a>

                    </div>

                    <div class="table-responsive">

                        <table class="table table-hover">

                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tanggal</th>
                                    <th>Kasir</th>
                                    <th>Total</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody>

                            <?php if ($qTransaksi && $qTransaksi->num_rows > 0): ?>

                                <?php while ($row = $qTransaksi->fetch_assoc()): ?>

                                    <tr>

                                        <td>
                                            #<?= e($row['idpenjualan']); ?>
                                        </td>

                                        <td>
                                            <?= e($row['created_at']); ?>
                                        </td>

                                        <td>
                                            <?= e($row['username']); ?>
                                        </td>

                                        <td>
                                            <?= rupiah($row['total_nilai']); ?>
                                        </td>

                                        <td>

                                            <a href="modules/penjualan/detail.php?id=<?= (int) $row['idpenjualan']; ?>"
                                               class="btn btn-outline-primary btn-sm">

                                                Detail

                                            </a>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="5"
                                        class="text-center text-muted py-4">

                                        Belum ada transaksi penjualan.

                                    </td>
                                </tr>

                            <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            <!-- AKSES CEPAT -->
            <div class="col-lg-4">

                <div class="panel">

                    <h5 class="panel-title">
                        Akses Cepat
                    </h5>

                    <div class="quick-menu">

                        <a href="modules/barang/index.php">
                            <i class="bi bi-box-seam"></i>
                            Barang
                        </a>

                        <a href="modules/vendor/index.php">
                            <i class="bi bi-buildings"></i>
                            Vendor
                        </a>

                        <a href="modules/pengadaan/index.php">
                            <i class="bi bi-cart-plus"></i>
                            Pengadaan
                        </a>

                        <a href="modules/penerimaan/index.php">
                            <i class="bi bi-box-arrow-in-down"></i>
                            Penerimaan
                        </a>

                        <a href="modules/penjualan/index.php">
                            <i class="bi bi-receipt"></i>
                            Penjualan
                        </a>

                        <a href="modules/retur/index.php">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            Retur
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>


    <!-- FOOTER -->
    <footer>
        © 2026 IrziMart. Sistem Informasi Manajemen Toko Bangunan.
    </footer>

</main>

</body>
</html>
