<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

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

// Koneksi database
$database = new Database();
$koneksi = $database->connect();

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

// Helper keamanan output
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$namaUser = $_SESSION['user']['username'] ?? 'User';
$nama_role = '';
$error = '';

// Proses tambah role
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validasi CSRF
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($csrfToken, $_POST['csrf_token'])
    ) {
        $error = 'Token keamanan tidak valid.';
    } else {

        $nama_role = trim($_POST['nama_role'] ?? '');

        if ($nama_role === '') {
            $error = 'Nama role wajib diisi.';
        } elseif (mb_strlen($nama_role) > 100) {
            $error = 'Nama role maksimal 100 karakter.';
        } else {

            try {

                // Periksa duplikasi nama role
                $stmt = $koneksi->prepare(
                    "SELECT idrole FROM role WHERE nama_role = ? LIMIT 1"
                );

                $stmt->bind_param("s", $nama_role);
                $stmt->execute();

                $cek = $stmt->get_result();

                if ($cek->num_rows > 0) {
                    $error = 'Nama role sudah digunakan.';
                } else {

                    // INSERT ke tabel role
                    $stmtInsert = $koneksi->prepare(
                        "INSERT INTO role (nama_role) VALUES (?)"
                    );

                    $stmtInsert->bind_param("s", $nama_role);
                    $stmtInsert->execute();
                    $stmtInsert->close();

                    header("Location: index.php?pesan=berhasil_tambah");
                    exit;
                }

                $stmt->close();

            } catch (mysqli_sql_exception $e) {
                error_log('Tambah role gagal: ' . $e->getMessage());
                $error = 'Terjadi kesalahan database saat menambahkan role.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tambah Role | IrziMart</title>

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

        .form-container {
            max-width: 650px;
            margin: 0 auto;
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

        .form-label {
            font-size: 13px;
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

        <div class="form-container">

            <!-- Header halaman -->
            <div class="mb-4">

                <h4 class="mb-1">Tambah Role</h4>

                <p class="text-muted small mb-0">
                    Tambahkan data role baru untuk pengelolaan hak akses pengguna IrziMart.
                </p>

            </div>

            <!-- Form Tambah -->
            <div class="card">

                <div class="card-body p-4">

                    <h6 class="fw-bold mb-4">
                        Form Tambah Role
                    </h6>

                    <?php if ($error !== ''): ?>

                        <div class="alert alert-danger" role="alert">
                            <?= e($error); ?>
                        </div>

                    <?php endif; ?>

                    <form action="" method="POST">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($csrfToken); ?>"
                        >

                        <div class="mb-4">

                            <label for="nama_role" class="form-label">
                                Nama Role
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                id="nama_role"
                                name="nama_role"
                                maxlength="100"
                                placeholder="Masukkan nama role"
                                value="<?= e($nama_role); ?>"
                                required
                            >

                            <small class="text-muted">
                                Maksimal 100 karakter.
                            </small>

                        </div>

                        <div class="d-flex justify-content-end gap-2">

                            <a href="index.php" class="btn btn-secondary btn-sm">
                                Batal
                            </a>

                            <button type="submit" class="btn btn-purple btn-sm">
                                Simpan Role
                            </button>

                        </div>

                    </form>

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
