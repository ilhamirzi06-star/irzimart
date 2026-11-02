
<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

// ==========================================
// FUNGSI OUTPUT
// ==========================================
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ==========================================
// VALIDASI LOGIN
// ==========================================
if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

// ==========================================
// VALIDASI ROLE
// ==========================================
if (($_SESSION['user']['role'] ?? '') !== 'Master Admin') {
    header("Location: ../../index.php?pesan=akses_ditolak");
    exit;
}

// ==========================================
// CSRF TOKEN
// ==========================================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

// ==========================================
// NILAI AWAL FORM
// ==========================================
$username = '';
$idrole = '';
$errors = [];

try {
    $database = new Database();
    $koneksi = $database->connect();

    // Ambil pilihan role dari View
    $queryRole = "
        SELECT *
        FROM vw_role
        ORDER BY idrole ASC
    ";

    $resultRole = $koneksi->query($queryRole);

    $roles = [];

    while ($row = $resultRole->fetch_assoc()) {
        $roles[] = $row;
    }

} catch (Throwable $e) {
    error_log("Create user initialization error: " . $e->getMessage());
    die("Terjadi kesalahan saat mengambil data role.");
}

// ==========================================
// PROSES FORM
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validasi CSRF
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        header("Location: index.php?pesan=csrf_invalid");
        exit;
    }

    // Ambil input
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasiPassword = $_POST['konfirmasi_password'] ?? '';
    $idrole = $_POST['idrole'] ?? '';

    // ======================================
    // VALIDASI USERNAME
    // ======================================
    if ($username === '') {
        $errors[] = 'Username wajib diisi.';
    } elseif (strlen($username) > 45) {
        $errors[] = 'Username maksimal 45 karakter.';
    } elseif (!preg_match('/\A[A-Za-z0-9_.-]+\z/', $username)) {
        $errors[] = 'Username hanya boleh menggunakan huruf, angka, titik, garis bawah, dan tanda hubung.';
    }

    // ======================================
    // VALIDASI PASSWORD
    // ======================================
    if ($password === '') {
        $errors[] = 'Password wajib diisi.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password minimal 8 karakter.';
    } elseif (strlen($password) > 72) {
        $errors[] = 'Password maksimal 72 byte.';
    }

    // ======================================
    // VALIDASI KONFIRMASI PASSWORD
    // ======================================
    if ($password !== $konfirmasiPassword) {
        $errors[] = 'Konfirmasi password tidak sesuai.';
    }

    // ======================================
    // VALIDASI ROLE
    // ======================================
    if (
        filter_var($idrole, FILTER_VALIDATE_INT) === false ||
        (int) $idrole <= 0
    ) {
        $errors[] = 'Role wajib dipilih.';
    } else {

        $idroleValue = (int) $idrole;

        $stmt = $koneksi->prepare("
            SELECT idrole
            FROM vw_role
            WHERE idrole = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $idroleValue);
        $stmt->execute();

        $roleExists = $stmt->get_result()->num_rows > 0;

        $stmt->close();

        if (!$roleExists) {
            $errors[] = 'Role yang dipilih tidak tersedia.';
        }
    }

    // ======================================
    // VALIDASI USERNAME UNIK
    // ======================================
    if (
        $username !== '' &&
        strlen($username) <= 45 &&
        preg_match('/\A[A-Za-z0-9_.-]+\z/', $username)
    ) {

        $stmt = $koneksi->prepare("
            SELECT iduser
            FROM vw_user
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $usernameExists = $stmt->get_result()->num_rows > 0;

        $stmt->close();

        if ($usernameExists) {
            $errors[] = 'Username sudah digunakan.';
        }
    }

    // ======================================
    // SIMPAN DATA USER
    // ======================================
    if (empty($errors)) {

        try {
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $idroleValue = (int) $idrole;

            $stmt = $koneksi->prepare("
                INSERT INTO user
                    (username, password, idrole)
                VALUES
                    (?, ?, ?)
            ");

            $stmt->bind_param(
                "ssi",
                $username,
                $passwordHash,
                $idroleValue
            );

            $stmt->execute();
            $stmt->close();

            // Redirect setelah berhasil
            header("Location: index.php?pesan=berhasil");
            exit;

        } catch (mysqli_sql_exception $e) {

            error_log("Insert user error: " . $e->getMessage());

            if ($e->getCode() === 1062) {
                $errors[] = 'Username sudah digunakan.';
            } else {
                $errors[] = 'Pengguna gagal disimpan karena terjadi kesalahan database.';
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

    <title>Tambah User | IrziMart</title>

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
            max-width: 650px;
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
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar">
        <div class="container-fluid px-3">

            <a class="navbar-brand" href="../../dashboard.php">
                IRZIMART
            </a>

            <a
                href="../../dashboard.php"
                class="btn btn-light btn-sm">
                Dashboard
            </a>

        </div>
    </nav>

    <!-- KONTEN -->
    <div class="container main-container">

        <div class="mb-4">
            <h4>Tambah User</h4>

            <p class="text-muted small">
                Tambahkan akun pengguna baru ke dalam sistem IrziMart.
            </p>
        </div>

        <!-- PESAN ERROR -->
        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger alert-dismissible fade show" role="alert">

                <strong>Terjadi kesalahan:</strong>

                <ul class="mb-0 mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error); ?></li>
                    <?php endforeach; ?>
                </ul>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Close">
                </button>

            </div>

        <?php endif; ?>

        <!-- FORM TAMBAH USER -->
        <div class="card">

            <div class="card-body p-4">

                <form method="POST" action="">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($csrfToken); ?>"
                    >

                    <!-- USERNAME -->
                    <div class="mb-3">

                        <label for="username" class="form-label">
                            Username
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="username"
                            name="username"
                            maxlength="45"
                            placeholder="Masukkan username"
                            value="<?= e($username); ?>"
                            required
                        >

                        <small class="text-muted">
                            Maksimal 45 karakter. Gunakan huruf, angka,
                            titik, garis bawah, atau tanda hubung.
                        </small>

                    </div>

                    <!-- PASSWORD -->
                    <div class="mb-3">

                        <label for="password" class="form-label">
                            Password
                        </label>

                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            minlength="8"
                            maxlength="72"
                            placeholder="Masukkan password"
                            autocomplete="new-password"
                            required
                        >

                        <small class="text-muted">
                            Password minimal 8 karakter.
                        </small>

                    </div>

                    <!-- KONFIRMASI PASSWORD -->
                    <div class="mb-3">

                        <label for="konfirmasi_password" class="form-label">
                            Konfirmasi Password
                        </label>

                        <input
                            type="password"
                            class="form-control"
                            id="konfirmasi_password"
                            name="konfirmasi_password"
                            minlength="8"
                            maxlength="72"
                            placeholder="Ulangi password"
                            autocomplete="new-password"
                            required
                        >

                    </div>

                    <!-- ROLE -->
                    <div class="mb-4">

                        <label for="idrole" class="form-label">
                            Role Pengguna
                        </label>

                        <select
                            class="form-select"
                            id="idrole"
                            name="idrole"
                            required
                        >

                            <option value="">
                                -- Pilih Role --
                            </option>

                            <?php foreach ($roles as $roleData): ?>

                                <option
                                    value="<?= e($roleData['idrole']); ?>"
                                    <?= ((string) $idrole === (string) $roleData['idrole']) ? 'selected' : ''; ?>
                                >
                                    <?= e($roleData['nama_role']); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <!-- TOMBOL -->
                    <div class="d-flex justify-content-between">

                        <a
                            href="index.php"
                            class="btn btn-secondary">
                            Kembali
                        </a>

                        <button
                            type="submit"
                            class="btn btn-purple">
                            Simpan User
                        </button>

                    </div>

                </form>

            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
