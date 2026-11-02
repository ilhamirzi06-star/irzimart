
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
    'Master Admin'
];

if (!in_array($role, $roleBisaMenambah, true)) {
    http_response_code(403);
    exit('Akses ditolak. Anda tidak memiliki izin menambah satuan.');
}

// Membuat CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4 mb-5">

    <!-- Header halaman -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>Tambah Satuan</h3>
            <p class="text-muted mb-0">
                Tambahkan satuan baru untuk kebutuhan pengelolaan barang.
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
                Satuan gagal ditambahkan.
            </div>

        <?php elseif ($_GET['status'] === 'satuan_sudah_ada'): ?>
            <div class="alert alert-warning">
                Nama satuan tersebut sudah terdaftar.
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <!-- Form tambah satuan -->
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

                <!-- Nama Satuan -->
                <div class="mb-3">
                    <label for="nama_satuan" class="form-label">
                        Nama Satuan
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="nama_satuan"
                        name="nama_satuan"
                        maxlength="45"
                        placeholder="Contoh: Pcs, Kg, Liter, Box"
                        required
                    >

                    <div class="form-text">
                        Masukkan nama satuan barang yang akan digunakan.
                    </div>
                </div>

                <!-- Tombol -->
                <div class="d-flex gap-2">
                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Simpan Satuan
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
