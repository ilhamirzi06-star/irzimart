
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
    'Master Admin'
];

if (!in_array($role, $roleBisaMengubah, true)) {
    http_response_code(403);
    exit('Akses ditolak. Anda tidak memiliki izin mengedit satuan.');
}

// Validasi ID satuan
$idsatuan = filter_input(INPUT_GET, 'idsatuan', FILTER_VALIDATE_INT);

if ($idsatuan === false || $idsatuan === null || $idsatuan < 1) {
    header('Location: index.php?status=id_tidak_valid');
    exit;
}

// Membuat CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ambil data satuan dari VIEW
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT idsatuan, nama_satuan, status
     FROM vw_satuan
     WHERE idsatuan = ?"
);

if (!$stmt) {
    http_response_code(500);
    exit('Gagal menyiapkan pengambilan data satuan.');
}

mysqli_stmt_bind_param($stmt, 'i', $idsatuan);
mysqli_stmt_execute($stmt);

$resultSatuan = mysqli_stmt_get_result($stmt);
$satuan = mysqli_fetch_assoc($resultSatuan);

mysqli_stmt_close($stmt);

if (!$satuan) {
    header('Location: index.php?status=satuan_tidak_ditemukan');
    exit;
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4 mb-5">

    <!-- Header halaman -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>Edit Satuan</h3>
            <p class="text-muted mb-0">
                Perbarui informasi satuan yang dipilih.
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
                Perubahan satuan gagal disimpan.
            </div>

        <?php elseif ($_GET['status'] === 'satuan_sudah_ada'): ?>
            <div class="alert alert-warning">
                Nama satuan tersebut sudah terdaftar.
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <!-- Form edit satuan -->
    <div class="card shadow-sm">
        <div class="card-body">

            <form action="proses.php" method="POST">

                <!-- Jenis aksi -->
                <input
                    type="hidden"
                    name="action"
                    value="update"
                >

                <!-- ID satuan -->
                <input
                    type="hidden"
                    name="idsatuan"
                    value="<?= (int) $satuan['idsatuan'] ?>"
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

                <!-- ID Satuan -->
                <div class="mb-3">
                    <label for="idsatuan" class="form-label">
                        ID Satuan
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="idsatuan"
                        value="<?= (int) $satuan['idsatuan'] ?>"
                        disabled
                    >
                </div>

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
                        required
                        value="<?= htmlspecialchars(
                            $satuan['nama_satuan'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                </div>

                <!-- Status -->
                <div class="mb-3">
                    <label class="form-label">
                        Status Satuan
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?= (int) $satuan['status'] === 1 ? 'Aktif' : 'Tidak Aktif' ?>"
                        disabled
                    >

                    <div class="form-text">
                        Status satuan tidak diubah melalui formulir ini.
                    </div>
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
