<?php
session_start();

// 1. Validasi autentikasi
if (!isset($_SESSION['user']['iduser'])) {
    header('Location: ../../login.php');
    exit;
}

// 2. Validasi hak akses
$role = $_SESSION['user']['role'] ?? '';

$roleDiizinkan = [
    'Administrator',
    'Master Admin',
    'Manajer Gudang',
    'Auditor'
];

if (!in_array($role, $roleDiizinkan, true)) {
    header('Location: ../../index.php');
    exit;
}

// 3. Membuat CSRF token jika belum tersedia
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 4. Memanggil koneksi database
require_once __DIR__ . '/../../config/Database.php';

try {
    $database = new Database();
    $koneksi = $database->connect();

    // 5. Mengambil data dari VIEW vw_barang
    $query = "
        SELECT
            idbarang,
            jenis,
            nama,
            idsatuan,
            nama_satuan,
            harga,
            status
        FROM vw_barang
        ORDER BY idbarang DESC
    ";

    $result = $koneksi->query($query);

} catch (Throwable $e) {
    error_log('Error modul barang: ' . $e->getMessage());
    die('Terjadi kesalahan saat mengambil data barang.');
}

// 6. Menentukan hak pengelolaan data
$bisaMengubah = in_array(
    $role,
    ['Administrator', 'Master Admin', 'Manajer Gudang'],
    true
);

// 7. Helper untuk keamanan output
function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

// 8. Memuat header setelah proses autentikasi dan database
include __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-1">Master Data Barang</h4>
        <p class="text-muted mb-0">
            Kelola informasi barang IrziMart.
        </p>
    </div>

    <?php if ($bisaMengubah): ?>
        <a href="create.php" class="btn btn-primary">
            + Tambah Barang
        </a>
    <?php endif; ?>
</div>

<div class="card p-3">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Barang</th>
                    <th>Jenis</th>
                    <th>Satuan</th>
                    <th>Harga</th>
                    <th>Status</th>

                    <?php if ($bisaMengubah): ?>
                        <th class="text-center">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>

            <tbody>
                <?php if ($result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                #<?= (int) $row['idbarang'] ?>
                            </td>

                            <td>
                                <?= e($row['nama']) ?>
                            </td>

                            <td>
                                <?= e($row['jenis']) ?>
                            </td>

                            <td>
                                <?= e($row['nama_satuan']) ?>
                            </td>

                            <td>
                                Rp <?= number_format(
                                    (int) $row['harga'],
                                    0,
                                    ',',
                                    '.'
                                ) ?>
                            </td>

                            <td>
                                <?php if ((int) $row['status'] === 1): ?>
                                    <span class="badge bg-success">
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">
                                        Nonaktif
                                    </span>
                                <?php endif; ?>
                            </td>

                            <?php if ($bisaMengubah): ?>
                                <td class="text-center">
                                    <a
                                        href="edit.php?idbarang=<?= (int) $row['idbarang'] ?>"
                                        class="btn btn-sm btn-warning"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        action="proses.php"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Yakin ingin menghapus barang ini?')"
                                    >
                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="idbarang"
                                            value="<?= (int) $row['idbarang'] ?>"
                                        >

                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= e($_SESSION['csrf_token']) ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-sm btn-danger"
                                        >
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td
                            colspan="<?= $bisaMengubah ? 7 : 6 ?>"
                            class="text-center text-secondary py-4"
                        >
                            Belum ada data barang.
                        </td>
                    </tr>

                <?php endif; ?>
            </tbody>

        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
