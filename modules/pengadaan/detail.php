
<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

// ==========================================
// 1. VALIDASI SESSION DAN HAK AKSES
// ==========================================

if (!isset($_SESSION['user'])) {
    header('Location: ../../login.php');
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$idUser = (int)($_SESSION['user']['iduser'] ?? 0);
$namaUser = $_SESSION['user']['username'] ?? 'User';

$allowedRoles = [
    'Administrator',
    'Manajer Gudang',
    'Staf Pengadaan',
    'Master Admin'
];

if (!in_array($role, $allowedRoles, true)) {
    http_response_code(403);
    exit('Anda tidak memiliki akses ke halaman ini.');
}

// ==========================================
// 2. KONEKSI DATABASE
// ==========================================

$database = new Database();
$koneksi = $database->connect();

// ==========================================
// 3. FUNGSI BANTU
// ==========================================

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rupiah($value): string
{
    return 'Rp ' . number_format((float)$value, 0, ',', '.');
}

// ==========================================
// 4. VALIDASI ID PENGADAAN
// ==========================================

$idPengadaan = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$idPengadaan || $idPengadaan <= 0) {
    header('Location: index.php');
    exit;
}

// ==========================================
// 5. MENGAMBIL DATA PENGADAAN DARI VIEW
// ==========================================

$stmt = $koneksi->prepare("
    SELECT *
    FROM vw_pengadaan
    WHERE idpengadaan = ?
");

$stmt->bind_param('i', $idPengadaan);
$stmt->execute();

$result = $stmt->get_result();
$pengadaan = $result->fetch_assoc();

$stmt->close();

if (!$pengadaan) {
    $_SESSION['flash_message'] = 'Data pengadaan tidak ditemukan.';
    $_SESSION['flash_type'] = 'danger';

    header('Location: index.php');
    exit;
}

// ==========================================
// 6. MENGAMBIL DETAIL BARANG DARI VIEW
// ==========================================

$stmtDetail = $koneksi->prepare("
    SELECT *
    FROM vw_detail_pengadaan
    WHERE idpengadaan = ?
    ORDER BY iddetail_pengadaan ASC
");

$stmtDetail->bind_param('i', $idPengadaan);
$stmtDetail->execute();

$resultDetail = $stmtDetail->get_result();

$detailBarang = [];

while ($row = $resultDetail->fetch_assoc()) {
    $detailBarang[] = $row;
}

$stmtDetail->close();

// ==========================================
// 7. NOTIFIKASI
// ==========================================

$pesan = '';
$tipePesan = 'success';

if (isset($_SESSION['flash_success'])) {
    $pesan = $_SESSION['flash_success'];
    $tipePesan = 'success';
} elseif (isset($_SESSION['flash_error'])) {
    $pesan = $_SESSION['flash_error'];
    $tipePesan = 'danger';
}

unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// ==========================================
// 8. STATUS PENGADAAN
// ==========================================

$statusPengadaan = [
    'D' => 'Draft',
    'M' => 'Menunggu Approval',
    'A' => 'Disetujui',
    'R' => 'Ditolak',
    'P' => 'Sedang Diproses',
    'S' => 'Selesai',
    'B' => 'Dibatalkan'
];

$status = $pengadaan['status'];

// ==========================================
// 9. HAK AKSES TOMBOL
// ==========================================

// Pembuat PO boleh mengedit dan mengajukan PO Draft.
// Administrator dan Master Admin juga dapat mengelola PO Draft.
$isOwner = ((int)$pengadaan['user_iduser'] === $idUser);

$canManageDraft = (
    $status === 'D'
    && (
        $isOwner
        || in_array($role, ['Administrator', 'Master Admin'], true)
    )
);

// Hanya Manajer Gudang yang melakukan approval.
$canApprove = (
    $role === 'Manajer Gudang'
    && $status === 'M'
);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detail Pengadaan - IrziMart</title>

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
            max-width: 1050px;
            margin: 32px auto;
        }

        .card {
            border-radius: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0,0,0,.08);
        }

        .card-header {
            background-color: white;
            font-weight: bold;
        }

        .table th,
        .table td {
            vertical-align: middle;
            font-size: 13px;
        }

        .info-label {
            color: #64748b;
            font-size: 11px;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 13px;
            font-weight: bold;
        }

        .payment-summary {
            background-color: #f4effa;
            border-radius: 8px;
            padding: 15px;
        }

        .total-value {
            color: #482875;
            font-size: 20px;
            font-weight: bold;
        }

        .btn-purple {
            background-color: #8655e8;
            color: white;
        }

        .btn-purple:hover {
            background-color: #7040cc;
            color: white;
        }

        footer {
            margin-top: 65px;
            text-align: center;
            font-size: 11px;
            color: #526078;
        }
    </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid px-3">

        <a class="navbar-brand" href="../../dashboard.php">
            IRZIMART
        </a>

        <div class="d-flex align-items-center gap-2">

            <span class="text-white small">
                <?= e($namaUser); ?>
            </span>

            <a href="../../dashboard.php" class="btn btn-light btn-sm">
                Dashboard
            </a>

            <a href="../../logout.php" class="btn btn-outline-light btn-sm">
                Keluar
            </a>

        </div>
    </div>
</nav>


<div class="container main-container">

    <!-- JUDUL HALAMAN -->
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-1">Detail Pengadaan</h4>

            <p class="text-muted small mb-0">
                Informasi lengkap Purchase Order dan barang yang dipesan.
            </p>
        </div>

        <a href="index.php" class="btn btn-secondary btn-sm">
            Kembali
        </a>

    </div>


    <!-- NOTIFIKASI -->
    <?php if ($pesan): ?>

        <div class="alert alert-<?= e($tipePesan); ?> alert-dismissible fade show">

            <?= e($pesan); ?>

            <button
                type="button"
                class="btn-close"
                data-bs-dismiss="alert"
            ></button>

        </div>

    <?php endif; ?>


    <!-- INFORMASI PURCHASE ORDER -->
    <div class="card mb-3">

        <div class="card-header d-flex justify-content-between align-items-center">

            Informasi Purchase Order

            <?php
            $badgeClass = [
                'D' => 'bg-secondary',
                'M' => 'bg-warning text-dark',
                'A' => 'bg-success',
                'R' => 'bg-danger',
                'P' => 'bg-primary',
                'S' => 'bg-success',
                'B' => 'bg-dark'
            ];

            $classStatus = $badgeClass[$status] ?? 'bg-secondary';
            ?>

            <span class="badge <?= e($classStatus); ?>">
                <?= e($statusPengadaan[$status] ?? 'Tidak diketahui'); ?>
            </span>

        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-4 mb-3">
                    <div class="info-label">Nomor Pengadaan</div>

                    <div class="info-value">
                        PO-<?= (int)$pengadaan['idpengadaan']; ?>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="info-label">Tanggal Pengadaan</div>

                    <div class="info-value">
                        <?= e(date('d-m-Y H:i', strtotime($pengadaan['timestamp']))); ?>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="info-label">Status Pengadaan</div>

                    <div class="info-value">
                        <?= e($statusPengadaan[$status] ?? '-'); ?>
                    </div>
                </div>

                <div class="col-md-4 mb-2">
                    <div class="info-label">Vendor</div>

                    <div class="info-value">
                        <?= e($pengadaan['nama_vendor']); ?>
                    </div>
                </div>

                <div class="col-md-4 mb-2">
                    <div class="info-label">Pembuat PO</div>

                    <div class="info-value">
                        <?= e($pengadaan['username_pembuat']); ?>
                    </div>
                </div>

                <div class="col-md-4 mb-2">
                    <div class="info-label">Badan Hukum Vendor</div>

                    <div class="info-value">
                        <?= $pengadaan['badan_hukum'] === 'Y' ? 'Ya' : 'Tidak'; ?>
                    </div>
                </div>

            </div>

        </div>
    </div>


    <!-- DAFTAR BARANG -->
    <div class="card mb-3">

        <div class="card-header">
            Daftar Barang Pengadaan
        </div>

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered table-hover mb-0">

                    <thead class="table-light">

                        <tr>
                            <th width="5%">No.</th>
                            <th>Nama Barang</th>
                            <th>Satuan</th>
                            <th class="text-end">Harga Satuan</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-end">Subtotal</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($detailBarang)): ?>

                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    Belum ada barang dalam pengadaan ini.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php
                            $nomor = 1;
                            foreach ($detailBarang as $barang):
                            ?>

                                <tr>

                                    <td><?= $nomor++; ?></td>

                                    <td>
                                        <?= e($barang['nama_barang']); ?>
                                    </td>

                                    <td>
                                        <?= e($barang['nama_satuan']); ?>
                                    </td>

                                    <td class="text-end">
                                        <?= rupiah($barang['harga_satuan']); ?>
                                    </td>

                                    <td class="text-end">
                                        <?= number_format((int)$barang['jumlah'], 0, ',', '.'); ?>
                                    </td>

                                    <td class="text-end fw-semibold">
                                        <?= rupiah($barang['sub_total']); ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>


    <!-- RINGKASAN PEMBAYARAN -->
    <div class="card mb-3">

        <div class="card-header">
            Ringkasan Pembayaran
        </div>

        <div class="card-body">

            <div class="row justify-content-end">

                <div class="col-md-5">

                    <div class="payment-summary">

                        <div class="d-flex justify-content-between mb-2">

                            <span class="small">
                                Subtotal Pengadaan
                            </span>

                            <strong class="small">
                                <?= rupiah($pengadaan['subtotal_nilai']); ?>
                            </strong>

                        </div>

                        <div class="d-flex justify-content-between mb-2">

                            <span class="small">
                                Tarif PPN
                            </span>

                            <strong class="small">
                                <?= number_format((float)$pengadaan['ppn'], 2, ',', '.'); ?>%
                            </strong>

                        </div>

                        <?php
                        $nominalPPN = round(
                            (float)$pengadaan['subtotal_nilai']
                            * (float)$pengadaan['ppn'] / 100
                        );
                        ?>

                        <div class="d-flex justify-content-between mb-2">

                            <span class="small">
                                Nominal PPN
                            </span>

                            <strong class="small">
                                <?= rupiah($nominalPPN); ?>
                            </strong>

                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center">

                            <strong class="small">
                                Total Pengadaan
                            </strong>

                            <span class="total-value">
                                <?= rupiah($pengadaan['total_nilai']); ?>
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>
    </div>


    <!-- INFORMASI APPROVAL -->
    <div class="card mb-3">

        <div class="card-header">
            Informasi Approval
        </div>

        <div class="card-body">

            <div class="row">

                <div class="col-md-4 mb-2">

                    <div class="info-label">
                        Approver
                    </div>

                    <div class="info-value">
                        <?= e($pengadaan['username_approver'] ?? '-'); ?>
                    </div>

                </div>

                <div class="col-md-4 mb-2">

                    <div class="info-label">
                        Waktu Approval
                    </div>

                    <div class="info-value">

                        <?php if (!empty($pengadaan['approval_timestamp'])): ?>

                            <?= e(date(
                                'd-m-Y H:i',
                                strtotime($pengadaan['approval_timestamp'])
                            )); ?>

                        <?php else: ?>

                            -

                        <?php endif; ?>

                    </div>

                </div>

                <div class="col-md-4 mb-2">

                    <div class="info-label">
                        Catatan Approval
                    </div>

                    <div class="info-value">
                        <?= e($pengadaan['catatan_approval'] ?? '-'); ?>
                    </div>

                </div>

            </div>

        </div>
    </div>


    <!-- TOMBOL AKSI -->
    <div class="d-flex justify-content-between align-items-center mt-3">

        <a href="index.php" class="btn btn-secondary btn-sm">
            Kembali ke Daftar
        </a>

        <div class="d-flex gap-2">

            <!-- DRAFT -->
            <?php if ($canManageDraft): ?>

                <a
                    href="edit.php?id=<?= (int)$pengadaan['idpengadaan']; ?>"
                    class="btn btn-warning btn-sm"
                >
                    Edit Pengadaan
                </a>

                <form
                    action="proses.php"
                    method="POST"
                    onsubmit="return confirm('Ajukan pengadaan ini untuk mendapatkan persetujuan Manajer Gudang?');"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e($_SESSION['csrf_token']); ?>"
                    >

                    <input
                        type="hidden"
                        name="action"
                        value="submit"
                    >

                    <input
                        type="hidden"
                        name="idpengadaan"
                        value="<?= (int)$pengadaan['idpengadaan']; ?>"
                    >

                    <button type="submit" class="btn btn-purple btn-sm">
                        Ajukan Pengadaan
                    </button>

                </form>

            <?php endif; ?>


            <!-- MENUNGGU APPROVAL -->
            <?php if ($canApprove): ?>

                <a
                    href="approval.php?id=<?= (int)$pengadaan['idpengadaan']; ?>"
                    class="btn btn-success btn-sm"
                >
                    Proses Approval
                </a>

            <?php elseif ($status === 'M'): ?>

                <span class="badge bg-warning text-dark p-2">
                    Menunggu Approval Manajer Gudang
                </span>

            <?php endif; ?>

        </div>

    </div>

</div>


<!-- FOOTER -->
<footer>
    © 2026 IrziMart. Sistem Informasi Manajemen Toko Bangunan.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
