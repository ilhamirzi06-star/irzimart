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

if (!in_array($role, ['Administrator', 'Manajer Gudang', 'Master Admin'], true)) {
    http_response_code(403);
    exit('Akses ditolak. Anda tidak memiliki izin mengedit barang.');
}

// Validasi ID barang dari URL.
$idbarang = filter_input(INPUT_GET, 'idbarang', FILTER_VALIDATE_INT);

if ($idbarang === false || $idbarang === null || $idbarang < 1) {
    header('Location: index.php?status=id_tidak_valid');
    exit;
}

// Membuat CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ambil data barang dari VIEW.
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT idbarang, nama, jenis, idsatuan, harga
     FROM vw_barang
     WHERE idbarang = ?"
);

if (!$stmt) {
    http_response_code(500);
    exit('Gagal menyiapkan pengambilan data barang.');
}

mysqli_stmt_bind_param($stmt, 'i', $idbarang);
mysqli_stmt_execute($stmt);

$resultBarang = mysqli_stmt_get_result($stmt);
$barang = mysqli_fetch_assoc($resultBarang);

mysqli_stmt_close($stmt);

if (!$barang) {
    header('Location: index.php?status=barang_tidak_ditemukan');
    exit;
}

// Ambil daftar satuan aktif.
$querySatuan = "SELECT idsatuan, nama_satuan
                FROM vw_satuan
                WHERE status = 1
                ORDER BY nama_satuan ASC";

$resultSatuan = mysqli_query($koneksi, $querySatuan);

if (!$resultSatuan) {
    http_response_code(500);
    exit('Gagal mengambil data satuan.');
}

// Pastikan satuan lama tetap bisa ditampilkan jika sudah tidak aktif.
$satuanTersedia = false;

while ($satuan = mysqli_fetch_assoc($resultSatuan)) {
    if ((int) $satuan['idsatuan'] === (int) $barang['idsatuan']) {
        $satuanTersedia = true;
        break;
    }
}

mysqli_data_seek($resultSatuan, 0);

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>Edit Barang</h3>
            <p class="text-muted mb-0">
                Perbarui informasi barang yang dipilih.
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
        <?php elseif ($_GET['status'] === 'edit_gagal'): ?>
            <div class="alert alert-danger">
                Perubahan barang gagal disimpan.
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form action="proses.php" method="POST">
                <input
                    type="hidden"
                    name="action"
                    value="update"
                >

                <input
                    type="hidden"
                    name="idbarang"
                    value="<?= (int) $barang['idbarang'] ?>"
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

                <div class="mb-3">
                    <label class="form-label" for="idbarang">
                        ID Barang
                    </label>
                    <input
                        type="text"
                        class="form-control"
                        id="idbarang"
                        value="<?= (int) $barang['idbarang'] ?>"
                        disabled
                    >
                </div>

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
                        required
                        value="<?= htmlspecialchars(
                            $barang['nama'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                </div>

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
                        required
                        value="<?= htmlspecialchars(
                            $barang['jenis'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >
                    <div class="form-text">
                        Isi dengan kode jenis satu karakter sesuai aturan
                        database.
                    </div>
                </div>

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
                        <option value="">-- Pilih Satuan --</option>

                        <?php if (!$satuanTersedia): ?>
                            <option
                                value="<?= (int) $barang['idsatuan'] ?>"
                                selected
                                disabled
                            >
                                Satuan lama tidak aktif
                            </option>
                        <?php endif; ?>

                        <?php while ($satuan = mysqli_fetch_assoc($resultSatuan)): ?>
                            <option
                                value="<?= (int) $satuan['idsatuan'] ?>"
                                <?= (int) $satuan['idsatuan'] ===
                                    (int) $barang['idsatuan']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars(
                                    $satuan['nama_satuan'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

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
                        required
                        value="<?= (int) $barang['harga'] ?>"
                    >
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        Simpan Perubahan
                    </button>

                    <a href="index.php" class="btn btn-outline-secondary">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
```