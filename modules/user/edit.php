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
    header("Location: ../../dashboard.php?pesan=akses_ditolak");
    exit;
}

// ==========================================
// VALIDASI METODE POST
// ==========================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// ==========================================
// VALIDASI CSRF
// ==========================================
$csrfToken = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    header("Location: index.php?pesan=csrf_invalid");
    exit;
}

// ==========================================
// AMBIL ID USER
// ==========================================
$iduser = filter_input(INPUT_POST, 'iduser', FILTER_VALIDATE_INT);

if (!$iduser || $iduser <= 0) {
    header("Location: index.php?pesan=data_tidak_valid");
    exit;
}

// ==========================================
// AMBIL DATA USER
// ==========================================
try {
    $database = new Database();
    $koneksi = $database->connect();

    // Mengambil data user dari View
    $stmt = $koneksi->prepare("
        SELECT *
        FROM vw_user
        WHERE iduser = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $iduser);
    $stmt->execute();

    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();

    $stmt->close();

    if (!$userData) {
        header("Location: index.php?pesan=data_tidak_ditemukan");
        exit;
    }

    // Mengambil daftar role dari View
    $resultRole = $koneksi->query("
        SELECT *
        FROM vw_role
        ORDER BY idrole ASC
    ");

    $roles = [];

    while ($row = $resultRole->fetch_assoc()) {
        $roles[] = $row;
    }

} catch (Throwable $e) {
    error_log("User edit error: " . $e->getMessage());
    header("Location: index.php?pesan=database_error");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit User | IrziMart</title>

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

            <a href="../../dashboard.php" class="btn btn-light btn-sm">
                Dashboard
            </a>

        </div>
    </nav>

    <!-- KONTEN -->
    <div class="container main-container">

        <div class="mb-4">
            <h4>Edit User</h4>

            <p class="text-muted small">
                Ubah informasi akun pengguna pada sistem IrziMart.
            </p>
        </div>

        <!-- FORM EDIT USER -->
        <div class="card">

            <div class="card-body p-4">

                <form action="proses.php" method="POST">

                    <!-- CSRF TOKEN -->
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($csrfToken); ?>"
                    >

                    <!-- ID USER MELALUI POST -->
                    <input
                        type="hidden"
                        name="iduser"
                        value="<?= e($userData['iduser']); ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="update"
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
                            pattern="[A-Za-z0-9_.-]+"
                            placeholder="Masukkan username"
                            value="<?= e($userData['username']); ?>"
                            required
                        >

                        <small class="text-muted">
                            Maksimal 45 karakter. Gunakan huruf, angka,
                            titik, garis bawah, atau tanda hubung.
                        </small>

                    </div>

                    <!-- ROLE -->
                    <div class="mb-3">

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

                            <?php foreach ($roles as $role): ?>

                                <option
                                    value="<?= e($role['idrole']); ?>"
                                    <?= ((int) $role['idrole'] === (int) $userData['idrole']) ? 'selected' : ''; ?>
                                >
                                    <?= e($role['nama_role']); ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <hr>

                    <!-- PASSWORD -->
                    <h6 class="mb-3">Ubah Password</h6>

                    <div class="mb-3">

                        <label for="password" class="form-label">
                            Password Baru
                        </label>

                        <input
                            type="password"
                            class="form-control"
                            id="password"
                            name="password"
                            minlength="8"
                            maxlength="72"
                            placeholder="Masukkan password baru"
                            autocomplete="new-password"
                        >

                        <small class="text-muted">
                            Kosongkan jika tidak ingin mengubah password.
                        </small>

                    </div>

                    <!-- KONFIRMASI PASSWORD -->
                    <div class="mb-4">

                        <label for="password_confirm" class="form-label">
                            Konfirmasi Password Baru
                        </label>

                        <input
                            type="password"
                            class="form-control"
                            id="password_confirm"
                            name="password_confirm"
                            minlength="8"
                            maxlength="72"
                            placeholder="Ulangi password baru"
                            autocomplete="new-password"
                        >

                    </div>

                    <!-- TOMBOL -->
                    <div class="d-flex justify-content-between">

                        <a href="index.php" class="btn btn-secondary">
                            Kembali
                        </a>

                        <button
                            type="submit"
                            class="btn btn-purple">

                            <i class="bi bi-save"></i>
                            Simpan Perubahan

                        </button>

                    </div>

                </form>

            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
