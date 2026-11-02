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

$roleBisaMengubah = [
    'Administrator',
    'Manajer Gudang',
    'Staf Pengadaan',
    'Master Admin'
];

if (!in_array($role, $roleBisaMengubah, true)) {
    http_response_code(403);
    exit('Akses ditolak. Anda tidak memiliki izin mengedit vendor.');
}

// Validasi ID vendor
$idvendor = filter_input(INPUT_GET, 'idvendor', FILTER_VALIDATE_INT);

if ($idvendor === false || $idvendor === null || $idvendor < 1) {
    header('Location: index.php?status=id_tidak_valid');
    exit;
}

// Membuat CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ambil data vendor dari VIEW
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT idvendor, nama_vendor, badan_hukum, status
     FROM vw_vendor
     WHERE idvendor = ?"
);

if (!$stmt) {
    http_response_code(500);
    exit('Gagal menyiapkan pengambilan data vendor.');
}

mysqli_stmt_bind_param($stmt, 'i', $idvendor);
mysqli_stmt_execute($stmt);

$resultVendor = mysqli_stmt_get_result($stmt);
$vendor = mysqli_fetch_assoc($resultVendor);

mysqli_stmt_close($stmt);

// Validasi keberadaan vendor
if (!$vendor) {
    header('Location: index.php?status=vendor_tidak_ditemukan');
    exit;
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4 mb-5">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>Edit Vendor</h3>
            <p class="text-muted mb-0">
                Perbarui informasi vendor yang dipilih.
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

        <?php elseif ($_GET['status'] === 'edit_gagal'): ?>
            <div class="alert alert-danger">
                Perubahan vendor gagal disimpan.
            </div>

        <?php elseif ($_GET['status'] === 'vendor_sudah_ada'): ?>
            <div class="alert alert-warning">
                Nama vendor sudah terdaftar.
            </div>

        <?php endif; ?>

    <?php endif; ?>

    <!-- Form Edit Vendor -->
    <div class="card shadow-sm">
        <div class="card-body">

            <form action="proses.php" method="POST">

                <!-- Jenis aksi -->
                <input
                    type="hidden"
                    name="action"
                    value="update"
                >

                <!-- ID Vendor -->
                <input
                    type="hidden"
                    name="idvendor"
                    value="<?= (int) $vendor['idvendor'] ?>"
                >

                <!-- CSRF Token -->
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_token'],
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <!-- ID Vendor -->
                <div class="mb-3">
                    <label for="idvendor" class="form-label">
                        ID Vendor
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="idvendor"
                        value="<?= (int) $vendor['idvendor'] ?>"
                        disabled
                    >
                </div>

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
                        required
                        value="<?= htmlspecialchars(
                            $vendor['nama_vendor'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
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
                        <option
                            value="Y"
                            <?= $vendor['badan_hukum'] === 'Y' ? 'selected' : '' ?>
                        >
                            Ya
                        </option>

                        <option
                            value="N"
                            <?= $vendor['badan_hukum'] === 'N' ? 'selected' : '' ?>
                        >
                            Tidak
                        </option>
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
                        <option
                            value="A"
                            <?= $vendor['status'] === 'A' ? 'selected' : '' ?>
                        >
                            Aktif
                        </option>

                        <option
                            value="TA"
                            <?= $vendor['status'] === 'TA' ? 'selected' : '' ?>
                        >
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
                        Simpan Perubahan
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
