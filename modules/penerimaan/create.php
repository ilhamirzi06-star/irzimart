<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../../login.php');
    exit;
}

$allowedRoles = [
    'Administrator',
    'Manajer Gudang',
    'Master Admin'
];

if (!in_array($_SESSION['user']['role'] ?? '', $allowedRoles, true)) {
    http_response_code(403);
    exit('Anda tidak memiliki akses ke halaman ini.');
}

$database = new Database();
$koneksi = $database->connect();

$namaUser = $_SESSION['user']['username'] ?? 'User';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$idpengadaan = filter_input(INPUT_GET, 'idpengadaan', FILTER_VALIDATE_INT);

$pesan = $_SESSION['flash_message'] ?? '';
$tipePesan = $_SESSION['flash_type'] ?? 'danger';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Mengambil daftar PO yang sudah disetujui atau sedang diproses.
$queryPO = "
    SELECT *
    FROM vw_pengadaan
    WHERE status IN ('A', 'P')
    ORDER BY idpengadaan DESC
";

$resultPO = $koneksi->query($queryPO);

$daftarPO = [];
while ($row = $resultPO->fetch_assoc()) {
    $daftarPO[] = $row;
}

// Mengambil detail barang yang masih memiliki sisa pesanan.
$detailPO = [];

if ($idpengadaan) {
    $stmt = $koneksi->prepare("
        SELECT *
        FROM vw_sisa_pengadaan
        WHERE idpengadaan = ?
          AND sisa_pesanan > 0
        ORDER BY nama_barang ASC
    ");

    $stmt->bind_param('i', $idpengadaan);
    $stmt->execute();

    $resultDetail = $stmt->get_result();

    while ($row = $resultDetail->fetch_assoc()) {
        $detailPO[] = $row;
    }

    $stmt->close();
}

// Validasi apakah PO yang dipilih memang tersedia.
$poTerpilih = null;

foreach ($daftarPO as $po) {
    if ((int)$po['idpengadaan'] === (int)$idpengadaan) {
        $poTerpilih = $po;
        break;
    }
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Penerimaan - IrziMart</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

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

        .btn-purple {
            background-color: #8655e8;
            color: white;
        }

        .btn-purple:hover {
            background-color: #7040cc;
            color: white;
        }

        .table th,
        .table td {
            vertical-align: middle;
            font-size: 13px;
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

<nav class="navbar navbar-expand-lg">
    <div class="container-fluid px-3">
        <a class="navbar-brand" href="../../dashboard.php">IRZIMART</a>

        <div class="d-flex align-items-center gap-2">
            <span class="text-white small"><?= e($namaUser); ?></span>
            <a href="../../dashboard.php" class="btn btn-light btn-sm">Dashboard</a>
            <a href="../../logout.php" class="btn btn-outline-light btn-sm">Keluar</a>
        </div>
    </div>
</nav>

<div class="container main-container">

    <div class="mb-4">
        <h4 class="fw-bold">Buat Penerimaan Barang</h4>
        <p class="text-muted small mb-0">
            Catat barang yang diterima berdasarkan pesanan pengadaan.
        </p>
    </div>

    <?php if ($pesan): ?>
        <div class="alert alert-<?= e($tipePesan); ?>">
            <?= e($pesan); ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3">1. Pilih Pengadaan</h6>

            <form method="GET" action="create.php">
                <div class="row align-items-end">
                    <div class="col-md-9">
                        <label class="form-label">Nomor Pengadaan</label>

                        <select name="idpengadaan" class="form-select" required>
                            <option value="">-- Pilih Pengadaan --</option>

                            <?php foreach ($daftarPO as $po): ?>
                                <option
                                    value="<?= (int)$po['idpengadaan']; ?>"
                                    <?= ((int)$po['idpengadaan'] === (int)$idpengadaan) ? 'selected' : ''; ?>
                                >
                                    PO #<?= (int)$po['idpengadaan']; ?>
                                    - <?= e($po['nama_vendor']); ?>
                                    - <?= e($po['timestamp']); ?>
                                </option>
                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="col-md-3 mt-3 mt-md-0">
                        <button type="submit" class="btn btn-purple w-100">
                            Tampilkan Barang
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($idpengadaan && !$poTerpilih): ?>

        <div class="alert alert-danger">
            Data pengadaan tidak ditemukan atau tidak dapat digunakan untuk penerimaan.
        </div>

    <?php elseif ($poTerpilih): ?>

        <div class="card mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-3">2. Informasi Pengadaan</h6>

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <small class="text-muted">Nomor Pengadaan</small>
                        <div class="fw-semibold">
                            PO #<?= (int)$poTerpilih['idpengadaan']; ?>
                        </div>
                    </div>

                    <div class="col-md-6 mb-2">
                        <small class="text-muted">Vendor</small>
                        <div class="fw-semibold">
                            <?= e($poTerpilih['nama_vendor']); ?>
                        </div>
                    </div>

                    <div class="col-md-6 mb-2">
                        <small class="text-muted">Tanggal Pengadaan</small>
                        <div class="fw-semibold">
                            <?= e($poTerpilih['timestamp']); ?>
                        </div>
                    </div>

                    <div class="col-md-6 mb-2">
                        <small class="text-muted">Status Pengadaan</small>
                        <div>
                            <?php if ($poTerpilih['status'] === 'A'): ?>
                                <span class="badge bg-success">Disetujui</span>
                            <?php else: ?>
                                <span class="badge bg-primary">Sedang Diproses</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-3">3. Input Barang Diterima</h6>

                <?php if (empty($detailPO)): ?>

                    <div class="alert alert-info mb-0">
                        Seluruh barang pada pengadaan ini sudah diterima.
                    </div>

                <?php else: ?>

                    <form method="POST" action="proses.php">

                        <input type="hidden" name="action" value="create">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION['csrf_token']); ?>"
                        >

                        <input
                            type="hidden"
                            name="idpengadaan"
                            value="<?= (int)$idpengadaan; ?>"
                        >

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">No.</th>
                                        <th>Nama Barang</th>
                                        <th>Satuan</th>
                                        <th>Jumlah Pesanan</th>
                                        <th>Sudah Diterima</th>
                                        <th>Sisa Pesanan</th>
                                        <th width="17%">Jumlah Diterima</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php foreach ($detailPO as $index => $detail): ?>
                                        <tr>
                                            <td><?= $index + 1; ?></td>

                                            <td>
                                                <?= e($detail['nama_barang']); ?>
                                            </td>

                                            <td>
                                                <?= e($detail['nama_satuan']); ?>
                                            </td>

                                            <td class="text-end">
                                                <?= number_format((int)$detail['jumlah_pesanan'], 0, ',', '.'); ?>
                                            </td>

                                            <td class="text-end">
                                                <?= number_format((int)$detail['jumlah_diterima'], 0, ',', '.'); ?>
                                            </td>

                                            <td class="text-end fw-semibold">
                                                <?= number_format((int)$detail['sisa_pesanan'], 0, ',', '.'); ?>
                                            </td>

                                            <td>
                                                <input
                                                    type="hidden"
                                                    name="iddetail_pengadaan[]"
                                                    value="<?= (int)$detail['iddetail_pengadaan']; ?>"
                                                >

                                                <input
                                                    type="number"
                                                    name="jumlah_terima[]"
                                                    class="form-control form-control-sm jumlah-terima"
                                                    min="0"
                                                    max="<?= (int)$detail['sisa_pesanan']; ?>"
                                                    value="0"
                                                    required
                                                >
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-warning small">
                            <strong>Perhatian:</strong>
                            Masukkan jumlah barang yang benar-benar diterima.
                            Isi 0 jika barang belum diterima. Penerimaan masih
                            berstatus Draft sampai dikonfirmasi.
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">
                                Kembali
                            </a>

                            <button type="submit" class="btn btn-purple">
                                Simpan Draft Penerimaan
                            </button>
                        </div>

                    </form>

                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>

</div>

<footer>
    © 2026 IrziMart. Sistem Informasi Manajemen Toko Bangunan.
</footer>

</body>
</html>