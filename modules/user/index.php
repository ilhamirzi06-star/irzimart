<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Validasi login
if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

// Validasi role
if (($_SESSION['user']['role'] ?? '') !== 'Master Admin') {
    header("Location: ../../dashboard.php?pesan=akses_ditolak");
    exit;
}

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];
$currentUserId = (int) ($_SESSION['user']['iduser'] ?? 0);

// Notifikasi
$pesan = $_GET['pesan'] ?? '';
$alertClass = '';
$pesanText = '';

switch ($pesan) {
    case 'berhasil':
        $alertClass = 'success';
        $pesanText = 'Proses berhasil dilakukan.';
        break;

    case 'gagal':
        $alertClass = 'danger';
        $pesanText = 'Proses gagal dilakukan.';
        break;

    case 'data_tidak_valid':
        $alertClass = 'warning';
        $pesanText = 'Data yang dikirim tidak valid.';
        break;

    case 'data_tidak_ditemukan':
        $alertClass = 'warning';
        $pesanText = 'Data user tidak ditemukan.';
        break;

    case 'csrf_invalid':
        $alertClass = 'danger';
        $pesanText = 'Token keamanan tidak valid. Silakan coba kembali.';
        break;

    case 'username_sudah_digunakan':
        $alertClass = 'warning';
        $pesanText = 'Username sudah digunakan oleh user lain.';
        break;

    case 'password_tidak_valid':
        $alertClass = 'warning';
        $pesanText = 'Password minimal 8 karakter dan maksimal 72 byte.';
        break;

    case 'password_tidak_sesuai':
        $alertClass = 'warning';
        $pesanText = 'Konfirmasi password tidak sesuai.';
        break;

    case 'role_akun_sendiri':
        $alertClass = 'warning';
        $pesanText = 'Role akun yang sedang digunakan tidak dapat diubah.';
        break;

    case 'hapus_akun_sendiri':
        $alertClass = 'warning';
        $pesanText = 'Anda tidak dapat menghapus akun sendiri.';
        break;

    case 'data_digunakan':
        $alertClass = 'warning';
        $pesanText = 'User tidak dapat dihapus karena masih digunakan oleh data lain.';
        break;

    case 'akses_ditolak':
        $alertClass = 'danger';
        $pesanText = 'Anda tidak memiliki hak akses.';
        break;

    case 'database_error':
        $alertClass = 'danger';
        $pesanText = 'Terjadi kesalahan pada database.';
        break;
}

try {
    $database = new Database();
    $koneksi = $database->connect();

    // Mengambil data dari View
    $query = "
        SELECT *
        FROM vw_user
        ORDER BY iduser DESC
    ";

    $result = $koneksi->query($query);

} catch (Throwable $e) {
    error_log("User index error: " . $e->getMessage());
    die("Terjadi kesalahan saat mengambil data user.");
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manajemen User | IrziMart</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
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
            margin: 40px auto;
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

        .table th {
            white-space: nowrap;
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="container-fluid px-3">

            <a class="navbar-brand" href="../../dashboard.php">
                IRZIMART
            </a>

            <a href="../../dashboard.php" class="btn btn-light btn-sm">
                Dashboard
            </a>

        </div>
    </nav>

    <!-- KONTEN -->
    <div class="container main-container">

        <!-- HEADER HALAMAN -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4>Manajemen User</h4>
                <p class="text-muted small mb-0">
                    Kelola akun pengguna dan hak akses sistem IrziMart.
                </p>
            </div>

            <a href="create.php" class="btn btn-purple">
                <i class="bi bi-plus-circle"></i>
                Tambah User
            </a>
        </div>

        <!-- NOTIFIKASI -->
        <?php if ($pesanText !== ''): ?>

            <div class="alert alert-<?= e($alertClass); ?> alert-dismissible fade show"
                 role="alert">

                <?= e($pesanText); ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close">
                </button>

            </div>

        <?php endif; ?>

        <!-- TABEL USER -->
        <div class="card">

            <div class="card-body p-4">

                <div class="table-responsive">

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-light">
                            <tr>
                                <th width="65">No.</th>
                                <th width="100">ID User</th>
                                <th>Username</th>
                                <th>Role Pengguna</th>
                                <th width="190" class="text-center">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if ($result->num_rows > 0): ?>

                                <?php $no = 1; ?>

                                <?php while ($user = $result->fetch_assoc()): ?>

                                    <tr>
                                        <td><?= $no++; ?></td>

                                        <td><?= e($user['iduser']); ?></td>

                                        <td>
                                            <?= e($user['username']); ?>

                                            <?php if ((int) $user['iduser'] === $currentUserId): ?>
                                                <span class="badge bg-success">
                                                    Akun Anda
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= e($user['nama_role']); ?>
                                            </span>
                                        </td>

                                        <td class="text-center">

                                            <!-- EDIT -->
                                            <form
                                                action="edit.php"
                                                method="POST"
                                                class="d-inline">

                                                <input
                                                    type="hidden"
                                                    name="csrf_token"
                                                    value="<?= e($csrfToken); ?>">

                                                <input
                                                    type="hidden"
                                                    name="iduser"
                                                    value="<?= e($user['iduser']); ?>">

                                                <button
                                                    type="submit"
                                                    class="btn btn-warning btn-sm">

                                                    <i class="bi bi-pencil-square"></i>
                                                    Edit

                                                </button>

                                            </form>

                                            <!-- HAPUS -->
                                            <?php if ((int) $user['iduser'] !== $currentUserId): ?>

                                                <form
                                                    action="proses.php"
                                                    method="POST"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?');">

                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e($csrfToken); ?>">

                                                    <input
                                                        type="hidden"
                                                        name="action"
                                                        value="delete">

                                                    <input
                                                        type="hidden"
                                                        name="iduser"
                                                        value="<?= e($user['iduser']); ?>">

                                                    <button
                                                        type="submit"
                                                        class="btn btn-danger btn-sm">

                                                        <i class="bi bi-trash"></i>
                                                        Hapus

                                                    </button>

                                                </form>

                                            <?php else: ?>

                                                <button
                                                    type="button"
                                                    class="btn btn-secondary btn-sm"
                                                    disabled>

                                                    <i class="bi bi-lock"></i>
                                                    Hapus

                                                </button>

                                            <?php endif; ?>

                                        </td>
                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-person-x fs-3 d-block mb-2"></i>
                                        Belum ada data user.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>