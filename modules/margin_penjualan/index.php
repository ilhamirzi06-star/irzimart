
<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

$database = new Database();
$koneksi = $database->connect();

// Validasi login
if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

// Validasi role
$role = $_SESSION['user']['role'] ?? '';

$allowedRoles = [
    'Administrator',
    'Master Admin'
];

if (!in_array($role, $allowedRoles, true)) {
    header("Location: ../../dashboard.php");
    exit;
}

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

// Mengambil data margin dari View
$query = "
    SELECT
        idmargin_penjualan,
        created_at,
        persen,
        status,
        iduser,
        username,
        updated_at
    FROM vw_margin_penjualan
    ORDER BY idmargin_penjualan DESC
";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    error_log('Query margin penjualan gagal: ' . mysqli_error($koneksi));
    die('Gagal mengambil data margin penjualan.');
}

// Helper keamanan output
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Notifikasi
$pesan = $_GET['pesan'] ?? '';

$notifikasi = [
    'tambah_berhasil' => ['success', 'Data margin berhasil ditambahkan.'],
    'edit_berhasil' => ['success', 'Data margin berhasil diperbarui.'],
    'hapus_berhasil' => ['success', 'Data margin berhasil dihapus.'],
    'data_tidak_valid' => ['danger', 'Data yang dimasukkan tidak valid.'],
    'margin_sudah_ada' => ['warning', 'Data margin sudah terdaftar.'],
    'margin_tidak_ditemukan' => ['warning', 'Data margin tidak ditemukan.'],
    'hapus_gagal' => ['danger', 'Data margin gagal dihapus.'],
    'aksi_tidak_valid' => ['danger', 'Aksi yang diminta tidak valid.']
];

$namaUser = $_SESSION['user']['username'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Master Margin Penjualan | IrziMart</title>

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
            max-width: 950px;
            margin: 32px auto;
        }

        .card {
            border-radius: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.08);
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

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-3">

            <a class="navbar-brand" href="../../dashboard.php">
                IRZIMART
            </a>

            <div class="d-flex align-items-center gap-2">

                <span class="text-white small">
                    <?= e($namaUser); ?>
                </span>

                <a href="../../dashboard.php"
                   class="btn btn-light btn-sm">
                    Dashboard
                </a>

                <a href="../../logout.php"
                   class="btn btn-outline-light btn-sm">
                    Keluar
                </a>

            </div>
        </div>
    </nav>

    <!-- Konten -->
    <div class="container main-container">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h4 class="mb-1">Master Margin Penjualan</h4>

                <p class="text-muted small mb-0">
                    Kelola persentase margin penjualan dalam sistem IrziMart.
                </p>
            </div>

            <a href="create.php" class="btn btn-purple btn-sm">
                Tambah Margin
            </a>

        </div>

        <!-- Notifikasi -->
        <?php if (isset($notifikasi[$pesan])): ?>

            <div class="alert alert-<?= e($notifikasi[$pesan][0]); ?> alert-dismissible fade show"
                 role="alert">

                <?= e($notifikasi[$pesan][1]); ?>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="alert">
                </button>

            </div>

        <?php endif; ?>

        <!-- Tabel Margin -->
        <div class="card">

            <div class="card-body p-2">

                <div class="table-responsive">

                    <table class="table table-hover table-sm mb-1">

                        <thead class="table-light">
                            <tr>
                                <th>No.</th>
                                <th>ID Margin</th>
                                <th>Persentase</th>
                                <th>Status</th>
                                <th>Dibuat Oleh</th>
                                <th>Tanggal Dibuat</th>
                                <th>Terakhir Diperbarui</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (mysqli_num_rows($result) > 0): ?>

                                <?php $no = 1; ?>

                                <?php while ($margin = mysqli_fetch_assoc($result)): ?>

                                    <tr>

                                        <td><?= $no++; ?></td>

                                        <td>
                                            <?= e($margin['idmargin_penjualan']); ?>
                                        </td>

                                        <td>
                                            <?= e(number_format((float) $margin['persen'], 2, ',', '.')); ?>%
                                        </td>

                                        <td>
                                            <?php if ((int) $margin['status'] === 1): ?>

                                                <span class="badge bg-success">
                                                    Aktif
                                                </span>

                                            <?php elseif ((int) $margin['status'] === 0): ?>

                                                <span class="badge bg-secondary">
                                                    Tidak Aktif
                                                </span>

                                            <?php else: ?>

                                                <span class="badge bg-danger">
                                                    Tidak Diketahui
                                                </span>

                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <?= e($margin['username']); ?>
                                        </td>

                                        <td>
                                            <?= e($margin['created_at']); ?>
                                        </td>

                                        <td>
                                            <?= e($margin['updated_at']); ?>
                                        </td>

                                        <td>

                                            <div class="d-flex gap-1">

                                                <a href="edit.php?idmargin_penjualan=<?= urlencode($margin['idmargin_penjualan']); ?>"
                                                   class="btn btn-warning btn-sm">
                                                    Edit
                                                </a>

                                                <form action="proses.php"
                                                      method="POST"
                                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus data margin ini?');">

                                                    <input type="hidden"
                                                           name="csrf_token"
                                                           value="<?= e($csrfToken); ?>">

                                                    <input type="hidden"
                                                           name="action"
                                                           value="delete">

                                                    <input type="hidden"
                                                           name="idmargin_penjualan"
                                                           value="<?= e($margin['idmargin_penjualan']); ?>">

                                                    <button type="submit"
                                                            class="btn btn-danger btn-sm">
                                                        Hapus
                                                    </button>

                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="8"
                                        class="text-center text-muted py-4">
                                        Belum ada data margin penjualan.
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
