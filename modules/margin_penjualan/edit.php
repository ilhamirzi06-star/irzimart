
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

// Validasi ID margin
$idmargin = filter_input(
    INPUT_GET,
    'idmargin_penjualan',
    FILTER_VALIDATE_INT
);

if (!$idmargin || $idmargin <= 0) {
    header("Location: index.php?pesan=data_tidak_valid");
    exit;
}

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

// Mengambil data dari View
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
    WHERE idmargin_penjualan = ?
";

$stmt = mysqli_prepare($koneksi, $query);

if (!$stmt) {
    error_log('Prepare edit margin gagal: ' . mysqli_error($koneksi));
    die('Gagal mengambil data margin.');
}

mysqli_stmt_bind_param($stmt, 'i', $idmargin);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$margin = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

// Validasi keberadaan data
if (!$margin) {
    header("Location: index.php?pesan=margin_tidak_ditemukan");
    exit;
}

// Nilai awal form
$persen = (string) $margin['persen'];
$status = (string) $margin['status'];

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Edit Margin Penjualan | IrziMart</title>

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

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container-fluid px-3">

            <a class="navbar-brand" href="../../dashboard.php">
                IRZIMART
            </a>

            <a href="../../dashboard.php"
               class="btn btn-light btn-sm">
                Dashboard
            </a>

        </div>
    </nav>

    <!-- Konten -->
    <div class="container main-container">

        <div class="mb-4">
            <h4>Edit Margin Penjualan</h4>

            <p class="text-muted small">
                Perbarui persentase dan status margin penjualan.
            </p>
        </div>

        <!-- Informasi Margin -->
        <div class="card mb-3">

            <div class="card-body">

                <div class="row mb-2">

                    <div class="col-5 text-muted small">
                        ID Margin
                    </div>

                    <div class="col-7 fw-semibold">
                        <?= e($margin['idmargin_penjualan']); ?>
                    </div>

                </div>

                <div class="row mb-2">

                    <div class="col-5 text-muted small">
                        Dibuat Oleh
                    </div>

                    <div class="col-7">
                        <?= e($margin['username']); ?>
                    </div>

                </div>

                <div class="row mb-2">

                    <div class="col-5 text-muted small">
                        Tanggal Dibuat
                    </div>

                    <div class="col-7">
                        <?= e($margin['created_at']); ?>
                    </div>

                </div>

                <div class="row">

                    <div class="col-5 text-muted small">
                        Terakhir Diperbarui
                    </div>

                    <div class="col-7">
                        <?= e($margin['updated_at']); ?>
                    </div>

                </div>

            </div>
        </div>

        <!-- Form Edit -->
        <div class="card">

            <div class="card-body p-4">

                <form method="POST" action="proses.php">

                    <!-- CSRF -->
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($csrfToken); ?>"
                    >

                    <!-- Action -->
                    <input
                        type="hidden"
                        name="action"
                        value="update"
                    >

                    <!-- ID Margin -->
                    <input
                        type="hidden"
                        name="idmargin_penjualan"
                        value="<?= e($idmargin); ?>"
                    >

                    <!-- Persentase -->
                    <div class="mb-3">

                        <label for="persen" class="form-label">
                            Persentase Margin (%)
                        </label>

                        <div class="input-group">

                            <input
                                type="number"
                                class="form-control"
                                id="persen"
                                name="persen"
                                min="0"
                                max="100"
                                step="0.01"
                                value="<?= e($persen); ?>"
                                placeholder="Contoh: 15"
                                required
                            >

                            <span class="input-group-text">%</span>

                        </div>

                        <small class="text-muted">
                            Masukkan persentase antara 0 sampai 100.
                        </small>

                    </div>

                    <!-- Status -->
                    <div class="mb-4">

                        <label for="status" class="form-label">
                            Status
                        </label>

                        <select
                            class="form-select"
                            id="status"
                            name="status"
                            required
                        >

                            <option value="1"
                                <?= $status === '1' ? 'selected' : ''; ?>>
                                Aktif
                            </option>

                            <option value="0"
                                <?= $status === '0' ? 'selected' : ''; ?>>
                                Tidak Aktif
                            </option>

                        </select>

                    </div>

                    <!-- Tombol -->
                    <div class="d-flex justify-content-between">

                        <a href="index.php"
                           class="btn btn-secondary">
                            Kembali
                        </a>

                        <button type="submit"
                                class="btn btn-purple">
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
