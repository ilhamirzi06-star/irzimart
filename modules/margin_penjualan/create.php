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

// Pastikan ID user tersedia
$iduser = $_SESSION['user']['iduser'] ?? null;

if (!$iduser || !filter_var($iduser, FILTER_VALIDATE_INT)) {
    header("Location: index.php?pesan=data_tidak_valid");
    exit;
}

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

$errors = [];
$persen = '';
$status = '1';

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validasi CSRF
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        die('Token keamanan tidak valid.');
    }

    $persenInput = trim($_POST['persen'] ?? '');
    $statusInput = $_POST['status'] ?? '1';

    $persen = $persenInput;
    $status = $statusInput;

    // Validasi persentase
    if ($persenInput === '' || !is_numeric($persenInput)) {
        $errors[] = 'Persentase margin wajib diisi dengan angka.';
    } elseif ((float) $persenInput < 0 || (float) $persenInput > 100) {
        $errors[] = 'Persentase margin harus berada di antara 0 sampai 100.';
    }

    // Validasi status
    if (!in_array($statusInput, ['0', '1'], true)) {
        $errors[] = 'Status margin tidak valid.';
    }

    if (empty($errors)) {

        $persenValue = (float) $persenInput;
        $statusValue = (int) $statusInput;
        $iduserValue = (int) $iduser;

        $query = "
            INSERT INTO margin_penjualan
                (persen, status, iduser)
            VALUES
                (?, ?, ?)
        ";

        $stmt = mysqli_prepare($koneksi, $query);

        if (!$stmt) {
            error_log('Prepare insert margin gagal: ' . mysqli_error($koneksi));
            $errors[] = 'Gagal menyiapkan penyimpanan data.';
        } else {

            mysqli_stmt_bind_param(
                $stmt,
                'dii',
                $persenValue,
                $statusValue,
                $iduserValue
            );

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);

                header("Location: index.php?pesan=tambah_berhasil");
                exit;
            }

            error_log('Insert margin gagal: ' . mysqli_stmt_error($stmt));
            $errors[] = 'Data margin gagal disimpan.';

            mysqli_stmt_close($stmt);
        }
    }
}

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

    <title>Tambah Margin Penjualan | IrziMart</title>

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
            <h4>Tambah Margin Penjualan</h4>

            <p class="text-muted small">
                Masukkan persentase margin penjualan yang akan digunakan.
            </p>
        </div>

        <!-- Pesan Error -->
        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">

                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error); ?></li>
                    <?php endforeach; ?>
                </ul>

            </div>

        <?php endif; ?>

        <div class="card">

            <div class="card-body p-4">

                <form method="POST" action="">

                    <input type="hidden"
                           name="csrf_token"
                           value="<?= e($csrfToken); ?>">

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
                                placeholder="Contoh: 15"
                                value="<?= e($persen); ?>"
                                required
                            >

                            <span class="input-group-text">%</span>

                        </div>

                        <small class="text-muted">
                            Masukkan nilai persentase antara 0 sampai 100.
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
                            Simpan Margin
                        </button>

                    </div>

                </form>

            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
