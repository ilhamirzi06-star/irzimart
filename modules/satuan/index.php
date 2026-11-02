
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

$roleBisaMengelola = [
    'Administrator',
    'Manajer Gudang',
    'Master Admin'
];

if (!in_array($role, $roleBisaMengelola, true)) {
    http_response_code(403);
    exit('Akses ditolak. Anda tidak memiliki izin mengakses master satuan.');
}

// Membuat CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Mengambil data satuan dari VIEW
$query = "SELECT idsatuan, nama_satuan, status
          FROM vw_satuan
          ORDER BY idsatuan DESC";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    http_response_code(500);
    exit('Gagal mengambil data satuan.');
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4 mb-5">

    <!-- Header halaman -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>Master Satuan</h3>
            <p class="text-muted mb-0">
                Kelola data satuan barang dalam sistem IrziMart.
            </p>
        </div>

        <a href="create.php" class="btn btn-primary">
            Tambah Satuan
        </a>
    </div>

    <!-- Notifikasi -->
    <?php if (isset($_GET['status'])): ?>

        <?php if ($_GET['status'] === 'tambah_berhasil'): ?>
            <div class="alert alert-success">
                Satuan berhasil ditambahkan.
            </div>

        <?php elseif ($_GET['status'] === 'edit_berhasil'): ?>
            <div class="alert alert-success">
                Satuan berhasil diperbarui.
            </div>

        <?php elseif ($_GET['status'] === 'hapus_berhasil'): ?>
            <div class="alert alert-success">
                Satuan berhasil dihapus.
            </div>

        <?php elseif ($_GET['status'] === 'data_tidak_valid'): ?>
            <div class="alert alert-warning">
                Data yang dimasukkan tidak valid.
            </div>

        <?php elseif ($_GET['status'] === 'id_tidak_valid'): ?>
            <div class="alert alert-warning">
                ID satuan tidak valid.
            </div>

        <?php elseif ($_GET['status'] === 'satuan_tidak_ditemukan'): ?>
            <div class="alert alert-warning">
                Data satuan tidak ditemukan.
            </div>

        <?php elseif ($_GET['status'] === 'satuan_digunakan'): ?>
            <div class="alert alert-warning">
                Satuan tidak dapat dihapus karena masih digunakan oleh barang.
            </div>

        <?php elseif ($_GET['status'] === 'satuan_sudah_ada'): ?>
            <div class="alert alert-warning">
                Nama satuan tersebut sudah terdaftar.
            </div>

        <?php elseif ($_GET['status'] === 'hapus_gagal'): ?>
            <div class="alert alert-danger">
                Satuan gagal dihapus. Periksa kembali relasi data.
            </div>

        <?php elseif ($_GET['status'] === 'aksi_tidak_valid'): ?>
            <div class="alert alert-warning">
                Aksi yang diminta tidak valid.
            </div>

        <?php endif; ?>

    <?php endif; ?>

    <!-- Tabel satuan -->
    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>ID Satuan</th>
                            <th>Nama Satuan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (mysqli_num_rows($result) > 0): ?>

                            <?php $no = 1; ?>

                            <?php while ($satuan = mysqli_fetch_assoc($result)): ?>

                                <tr>
                                    <td><?= $no++ ?></td>

                                    <td>
                                        <?= (int) $satuan['idsatuan'] ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $satuan['nama_satuan'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?php if ((int) $satuan['status'] === 1): ?>
                                            <span class="badge bg-success">
                                                Aktif
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                Tidak Aktif
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="d-flex gap-2">

                                            <!-- Tombol Edit -->
                                            <a
                                                href="edit.php?idsatuan=<?= (int) $satuan['idsatuan'] ?>"
                                                class="btn btn-sm btn-warning"
                                            >
                                                Edit
                                            </a>

                                            <!-- Form Hapus -->
                                            <form
                                                action="proses.php"
                                                method="POST"
                                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus satuan ini?');"
                                            >
                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="idsatuan"
                                                    value="<?= (int) $satuan['idsatuan'] ?>"
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

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-danger"
                                                >
                                                    Hapus
                                                </button>
                                            </form>

                                        </div>
                                    </td>
                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Belum ada data satuan.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
