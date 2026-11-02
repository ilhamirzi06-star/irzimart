<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/Database.php';

// Inisialisasi koneksi database
$database = new Database();
$koneksi = $database->connect();

// Validasi login
if (!isset($_SESSION['user'])) {
    header('Location: ../../login.php');
    exit;
}

// Validasi hak akses
$role = $_SESSION['user']['role'] ?? '';

$roleBisaMenambah = [
    'Administrator',
    'Manajer Gudang',
    'Staf Pengadaan',
    'Master Admin'
];

if (!in_array($role, $roleBisaMenambah, true)) {
    http_response_code(403);
    exit('Akses ditolak. Anda tidak memiliki izin menambah vendor.');
}

// Membuat CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4 mb-5">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>Tambah Vendor</h3>
            <p class="text-muted mb-0">
                Masukkan informasi vendor atau pemasok baru.
            </p>
        </div>

        <a href="index.php" class="btn btn-secondary">
            Kembali
        </a>
    </div>

    <!-- Notifikasi -->
    <?php if (isset($_GET['status'])): ?>

        <?php if ($_GET['status'] === 'data_tidak_valid'): ?>
            <div class="alert alert-warning">
                Data tidak valid. Periksa kembali isian formulir.
            </div>

        <?php elseif ($_GET['status'] === 'tambah_gagal'): ?>
            <div class="alert alert-danger">
                Vendor gagal ditambahkan.
            </div>

        <?php elseif ($_GET['status'] === 'vendor_sudah_ada'): ?>
            <div class="alert alert-warning">
                Nama vendor sudah terdaftar.
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <!-- Form Tambah Vendor -->
    <div class="card shadow-sm">
        <div class="card-body">

            <form action="proses.php" method="POST">

                <input
                    type="hidden"
                    name="action"
                    value="create"
                >

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_token'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <!-- Nama Vendor -->
                <div class="mb-3">
                    <label for="nama_vendor" class="form-label">
                        Nama Vendor
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="nama_vendor"
                        name="nama_vendor"
                        maxlength="100"
                        placeholder="Contoh: PT Maju Bersama"
                        required
                    >
                </div>

                <!-- Badan Hukum -->
                <div class="mb-3">
                    <label for="badan_hukum" class="form-label">
                        Badan Hukum
                    </label>

                    <select
                        class="form-select"
                        id="badan_hukum"
                        name="badan_hukum"
                        required
                    >
                        <option value="">
                            -- Pilih Badan Hukum --
                        </option>

                        <option value="Y">Ya</option>
                        <option value="N">Tidak</option>
                    </select>
                </div>

                <!-- Status Vendor -->
                <div class="mb-3">
                    <label for="status" class="form-label">
                        Status Vendor
                    </label>

                    <select
                        class="form-select"
                        id="status"
                        name="status"
                        required
                    >
                        <option value="A" selected>
                            Aktif
                        </option>

                        <option value="TA">
                            Tidak Aktif
                        </option>
                    </select>
                </div>

                <!-- Tombol -->
                <div class="d-flex gap-2">
                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Simpan Vendor
                    </button>

                    <a
                        href="index.php"
                        class="btn btn-outline-secondary"
                    >
                        Batal
                    </a>
                </div>

            </form>

        </div>
    </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
