
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
    exit('Akses ditolak. Anda tidak memiliki izin menambah barang.');
}

// Membuat CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ambil pilihan satuan aktif dari VIEW
$query = "SELECT idsatuan, nama_satuan
          FROM vw_satuan
          WHERE status = 1
          ORDER BY nama_satuan ASC";

$resultSatuan = mysqli_query($koneksi, $query);

if (!$resultSatuan) {
    http_response_code(500);
    exit('Gagal mengambil data satuan.');
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4 mb-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>Tambah Barang</h3>
            <p class="text-muted mb-0">
                Masukkan informasi barang baru ke dalam sistem.
            </p>
        </div>

        <a href="index.php" class="btn btn-secondary">
            Kembali
        </a>
    </div>

    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] === 'data_tidak_valid'): ?>
            <div class="alert alert-warning">
                Data tidak valid. Periksa kembali isian formulir.
            </div>

        <?php elseif ($_GET['status'] === 'satuan_tidak_valid'): ?>
            <div class="alert alert-warning">
                Satuan tidak tersedia atau sudah tidak aktif.
            </div>

        <?php elseif ($_GET['status'] === 'tambah_gagal'): ?>
            <div class="alert alert-danger">
                Barang gagal ditambahkan.
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">

            <form action="proses.php" method="POST">

                <!-- Jenis aksi -->
                <input
                    type="hidden"
                    name="action"
                    value="create"
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

                <!-- Nama Barang -->
                <div class="mb-3">
                    <label for="nama" class="form-label">
                        Nama Barang
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="nama"
                        name="nama"
                        maxlength="45"
                        placeholder="Masukkan nama barang"
                        required
                    >
                </div>

                <!-- Jenis Barang -->
                <div class="mb-3">
                    <label for="jenis" class="form-label">
                        Jenis Barang
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        id="jenis"
                        name="jenis"
                        maxlength="1"
                        placeholder="Masukkan kode jenis"
                        required
                    >

                    <div class="form-text">
                        Isi dengan kode jenis satu karakter sesuai aturan database.
                    </div>
                </div>

                <!-- Satuan -->
                <div class="mb-3">
                    <label for="idsatuan" class="form-label">
                        Satuan
                    </label>

                    <select
                        class="form-select"
                        id="idsatuan"
                        name="idsatuan"
                        required
                    >
                        <option value="">
                            -- Pilih Satuan --
                        </option>

                        <?php while ($satuan = mysqli_fetch_assoc($resultSatuan)): ?>
                            <option value="<?= (int) $satuan['idsatuan'] ?>">
                                <?= htmlspecialchars(
                                    $satuan['nama_satuan'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endwhile; ?>

                    </select>
                </div>

                <!-- Harga -->
                <div class="mb-3">
                    <label for="harga" class="form-label">
                        Harga
                    </label>

                    <input
                        type="number"
                        class="form-control"
                        id="harga"
                        name="harga"
                        min="0"
                        step="1"
                        placeholder="Masukkan harga barang"
                        required
                    >
                </div>

                <!-- Tombol -->
                <div class="d-flex gap-2">
                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Simpan Barang
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
