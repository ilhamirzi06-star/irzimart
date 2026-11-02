
<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

// Koneksi database
$database = new Database();
$koneksi = $database->connect();

// Validasi login
if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

// Validasi role
$role = $_SESSION['user']['role'] ?? '';

if ($role !== 'Master Admin') {
    header("Location: ../../dashboard.php");
    exit;
}

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

// Mengambil data langsung dari View
$query = "SELECT * FROM vw_role ORDER BY idrole ASC";
$result = mysqli_query($koneksi, $query);

if (!$result) {
    error_log('Query role gagal: ' . mysqli_error($koneksi));
    die('Gagal mengambil data role.');
}

// Helper keamanan output
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Notifikasi
$pesan = $_GET['pesan'] ?? '';

$notifikasi = [
    'berhasil_tambah' => ['success', 'Role berhasil ditambahkan.'],
    'berhasil_edit' => ['success', 'Role berhasil diperbarui.'],
    'berhasil_hapus' => ['success', 'Role berhasil dihapus.'],
    'nama_kosong' => ['warning', 'Nama role wajib diisi.'],
    'nama_duplikat' => ['warning', 'Nama role sudah digunakan.'],
    'data_digunakan' => ['danger', 'Role tidak dapat dihapus karena masih digunakan oleh akun.'],
    'data_tidak_ditemukan' => ['warning', 'Data role tidak ditemukan.'],
    'data_tidak_valid' => ['danger', 'Data yang dimasukkan tidak valid.'],
    'csrf_invalid' => ['danger', 'Token keamanan tidak valid.'],
    'akses_ditolak' => ['danger', 'Anda tidak memiliki hak akses.'],
    'database_error' => ['danger', 'Terjadi kesalahan database.']
];

$namaUser = $_SESSION['user']['username'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Master Role | IrziMart</title>

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
            border: none;
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

        .role-badge {
            background-color: #eee5ff;
            color: #482875;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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

                <a href="../../dashboard.php" class="btn btn-light btn-sm">
                    Dashboard
                </a>

                <a href="../../logout.php" class="btn btn-outline-light btn-sm">
                    Keluar
                </a>

            </div>
        </div>
    </nav>

    <!-- Konten -->
    <div class="container main-container">

        <!-- Header halaman -->
        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h4 class="mb-1">Master Role</h4>

                <p class="text-muted small mb-0">
                    Kelola data hak akses dan role pengguna IrziMart.
                </p>
            </div>

            <a href="create.php" class="btn btn-purple btn-sm">
                Tambah Role
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

        <!-- Tabel Role -->
        <div class="card">

            <div class="card-body p-2">

                <div class="table-responsive">

                    <table class="table table-hover table-sm mb-1">

                        <thead class="table-light">
                            <tr>
                                <th>No.</th>
                                <th>ID Role</th>
                                <th>Nama Role</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (mysqli_num_rows($result) > 0): ?>

                                <?php $no = 1; ?>

                                <?php while ($row = mysqli_fetch_assoc($result)): ?>

                                    <tr>

                                        <td><?= $no++; ?></td>

                                        <td>
                                            <?= e($row['idrole']); ?>
                                        </td>

                                        <td>
                                            <span class="role-badge">
                                                <?= e($row['nama_role']); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <div class="d-flex justify-content-center gap-1">

                                                <!-- Edit -->
                                                <form action="edit.php" method="POST">

                                                    <input type="hidden"
                                                           name="csrf_token"
                                                           value="<?= e($csrfToken); ?>">

                                                    <input type="hidden"
                                                           name="idrole"
                                                           value="<?= e($row['idrole']); ?>">

                                                    <button type="submit"
                                                            class="btn btn-warning btn-sm">
                                                        Edit
                                                    </button>

                                                </form>

                                                <!-- Hapus -->
                                                <form action="proses.php"
                                                      method="POST"
                                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus role ini?');">

                                                    <input type="hidden"
                                                           name="csrf_token"
                                                           value="<?= e($csrfToken); ?>">

                                                    <input type="hidden"
                                                           name="action"
                                                           value="delete">

                                                    <input type="hidden"
                                                           name="idrole"
                                                           value="<?= e($row['idrole']); ?>">

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
                                    <td colspan="4"
                                        class="text-center text-muted py-4">
                                        Belum ada data role.
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
