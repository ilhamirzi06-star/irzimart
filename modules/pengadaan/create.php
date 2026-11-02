
<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

// ==========================================
// 1. VALIDASI SESSION DAN ROLE
// ==========================================

if (!isset($_SESSION['user'])) {
    header('Location: ../../login.php');
    exit;
}

$role = $_SESSION['user']['role'] ?? '';
$namaUser = $_SESSION['user']['username'] ?? 'User';

$roleDiizinkan = [
    'Administrator',
    'Staf Pengadaan',
    'Master Admin'
];

if (!in_array($role, $roleDiizinkan, true)) {
    header('Location: ../../dashboard.php');
    exit;
}

// ==========================================
// 2. FUNGSI BANTU
// ==========================================

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// ==========================================
// 3. CSRF TOKEN
// ==========================================

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ==========================================
// 4. KONEKSI DATABASE
// ==========================================

$database = new Database();
$koneksi = $database->connect();

$daftarVendor = [];
$daftarBarang = [];
$error = '';

// ==========================================
// 5. AMBIL VENDOR AKTIF
// ==========================================

try {
    $queryVendor = "
        SELECT *
        FROM vw_vendor
        WHERE status = 'A'
        ORDER BY nama_vendor ASC
    ";

    $resultVendor = $koneksi->query($queryVendor);

    while ($row = $resultVendor->fetch_assoc()) {
        $daftarVendor[] = $row;
    }

} catch (mysqli_sql_exception $e) {
    error_log('Load vendor error: ' . $e->getMessage());
    $error = 'Data vendor gagal dimuat.';
}

// ==========================================
// 6. AMBIL BARANG AKTIF
// ==========================================

try {
    $queryBarang = "
        SELECT *
        FROM vw_barang
        WHERE status = 1
        ORDER BY nama ASC
    ";

    $resultBarang = $koneksi->query($queryBarang);

    while ($row = $resultBarang->fetch_assoc()) {
        $daftarBarang[] = $row;
    }

} catch (mysqli_sql_exception $e) {
    error_log('Load barang error: ' . $e->getMessage());
    $error = 'Data barang gagal dimuat.';
}

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tambah Pengadaan | IrziMart</title>

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
            padding: 0 15px;
        }

        .card {
            border-radius: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0, 0, 0, .08);
        }

        .btn-purple {
            background-color: #8655e8;
            color: white;
        }

        .btn-purple:hover {
            background-color: #7040cc;
            color: white;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
        }

        .form-control,
        .form-select {
            font-size: 13px;
        }

        .table th,
        .table td {
            vertical-align: middle;
            font-size: 13px;
        }

        .total-box {
            background-color: #f4effa;
            border-radius: 8px;
            padding: 15px;
        }

        .total-value {
            color: #482875;
            font-size: 20px;
            font-weight: bold;
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

<!-- KONTEN UTAMA -->

<div class="main-container">

    <div class="mb-3">

        <h4 class="mb-1">Tambah Pengadaan</h4>

        <small class="text-muted">
            Buat Purchase Order baru dan tambahkan barang yang akan dipesan.
        </small>

    </div>

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">
            <?= e($error); ?>
        </div>

    <?php endif; ?>

    <?php if (count($daftarVendor) === 0): ?>

        <div class="alert alert-warning">
            Belum ada vendor aktif. Tambahkan atau aktifkan vendor terlebih dahulu.
        </div>

    <?php elseif (count($daftarBarang) === 0): ?>

        <div class="alert alert-warning">
            Belum ada barang aktif. Tambahkan atau aktifkan barang terlebih dahulu.
        </div>

    <?php else: ?>

        <form action="proses.php" method="POST" id="formPengadaan">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($_SESSION['csrf_token']); ?>"
            >

            <input
                type="hidden"
                name="action"
                value="create"
            >

            <!-- INFORMASI PO -->

            <div class="card mb-3">

                <div class="card-header bg-white">
                    <strong>Informasi Purchase Order</strong>
                </div>

                <div class="card-body">

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Vendor <span class="text-danger">*</span>
                            </label>

                            <select
                                name="vendor_idvendor"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    -- Pilih Vendor --
                                </option>

                                <?php foreach ($daftarVendor as $vendor): ?>

                                    <option value="<?= (int) $vendor['idvendor']; ?>">
                                        <?= e($vendor['nama_vendor']); ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Pembuat PO
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                value="<?= e($namaUser); ?>"
                                readonly
                            >

                            <small class="text-muted">
                                Pembuat PO diambil otomatis dari akun login.
                            </small>

                        </div>

                    </div>

                </div>

            </div>

            <!-- DETAIL BARANG -->

            <div class="card mb-3">

                <div class="card-header bg-white d-flex justify-content-between align-items-center">

                    <strong>Detail Barang Pengadaan</strong>

                    <button
                        type="button"
                        class="btn btn-purple btn-sm"
                        id="tambahBarang"
                    >
                        + Tambah Barang
                    </button>

                </div>

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-bordered table-sm">

                            <thead class="table-light">

                                <tr>
                                    <th width="35">No.</th>
                                    <th width="30%">Barang</th>
                                    <th width="18%">Harga Satuan (Rp)</th>
                                    <th width="15%">Jumlah</th>
                                    <th width="20%">Subtotal (Rp)</th>
                                    <th width="60">Aksi</th>
                                </tr>

                            </thead>

                            <tbody id="daftarBarang">
                            </tbody>

                        </table>

                    </div>

                    <small class="text-muted">
                        Subtotal dihitung otomatis berdasarkan harga satuan dan jumlah barang.
                    </small>

                </div>

            </div>

            <!-- RINGKASAN PEMBAYARAN -->

            <div class="card mb-3">

                <div class="card-header bg-white">
                    <strong>Ringkasan Pembayaran</strong>
                </div>

                <div class="card-body">

                    <div class="row justify-content-end">

                        <div class="col-md-5">

                            <div class="mb-3">

                                <label class="form-label">
                                    Subtotal Pengadaan
                                </label>

                                <input
                                    type="text"
                                    id="subtotalTampilan"
                                    class="form-control text-end"
                                    value="Rp 0"
                                    readonly
                                >

                            </div>

                            <div class="mb-3">

                                <label class="form-label">
                                    Tarif PPN (%)
                                </label>

                                <input
                                    type="number"
                                    name="ppn"
                                    id="ppn"
                                    class="form-control"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    value="0"
                                    required
                                >

                                <small class="text-muted">
                                    Masukkan tarif PPN dalam persentase.
                                </small>

                            </div>

                            <div class="total-box">

                                <div class="d-flex justify-content-between mb-2">

                                    <span>Subtotal</span>

                                    <strong id="subtotalRingkasan">
                                        Rp 0
                                    </strong>

                                </div>

                                <div class="d-flex justify-content-between mb-2">

                                    <span>Tarif PPN</span>

                                    <strong id="tarifPPNRingkasan">
                                        0%
                                    </strong>

                                </div>

                                <div class="d-flex justify-content-between mb-2">

                                    <span>Nominal PPN</span>

                                    <strong id="ppnRingkasan">
                                        Rp 0
                                    </strong>

                                </div>

                                <hr>

                                <div class="d-flex justify-content-between align-items-center">

                                    <strong>Total Pengadaan</strong>

                                    <span class="total-value" id="totalRingkasan">
                                        Rp 0
                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- TOMBOL AKSI -->

            <div class="d-flex justify-content-between mb-4">

                <a href="index.php" class="btn btn-secondary btn-sm">
                    Kembali
                </a>

                <button
                    type="submit"
                    class="btn btn-purple btn-sm"
                >
                    Simpan Draft PO
                </button>

            </div>

        </form>

    <?php endif; ?>

    <footer>
        © 2026 IrziMart. Sistem Informasi Manajemen Toko Bangunan.
    </footer>

</div>

<!-- TEMPLATE BARIS BARANG -->

<template id="templateBarang">

    <tr class="baris-barang">

        <td class="nomor-baris text-center"></td>

        <td>

            <select
                name="barang_id[]"
                class="form-select form-select-sm pilih-barang"
                required
            >

                <option value="">
                    -- Pilih Barang --
                </option>

                <?php foreach ($daftarBarang as $barang): ?>

                    <option
                        value="<?= (int) $barang['idbarang']; ?>"
                        data-harga="<?= (int) $barang['harga']; ?>"
                    >
                        <?= e($barang['nama']); ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </td>

        <td>

            <input
                type="number"
                name="harga_satuan[]"
                class="form-control form-control-sm harga-barang"
                min="1"
                step="1"
                required
            >

        </td>

        <td>

            <input
                type="number"
                name="jumlah[]"
                class="form-control form-control-sm jumlah-barang"
                min="1"
                step="1"
                value="1"
                required
            >

        </td>

        <td>

            <input
                type="text"
                class="form-control form-control-sm subtotal-barang"
                value="Rp 0"
                readonly
            >

        </td>

        <td class="text-center">

            <button
                type="button"
                class="btn btn-danger btn-sm hapus-barang"
            >
                Hapus
            </button>

        </td>

    </tr>

</template>

<script>

// ==========================================
// ELEMEN FORM
// ==========================================

const daftarBarang = document.getElementById('daftarBarang');
const templateBarang = document.getElementById('templateBarang');
const tombolTambah = document.getElementById('tambahBarang');
const inputPPN = document.getElementById('ppn');

// ==========================================
// FORMAT RUPIAH
// ==========================================

const formatRupiah = (angka) => {

    return 'Rp ' + Number(angka).toLocaleString('id-ID', {
        maximumFractionDigits: 0
    });

};

// ==========================================
// TAMBAH BARIS BARANG
// ==========================================

function tambahBarisBarang() {

    const baris = templateBarang.content.cloneNode(true);

    daftarBarang.appendChild(baris);

    hitungSemua();

}

// ==========================================
// NOMOR URUT BARANG
// ==========================================

function perbaruiNomor() {

    const semuaBaris = daftarBarang.querySelectorAll('.baris-barang');

    semuaBaris.forEach((baris, index) => {

        baris.querySelector('.nomor-baris').textContent = index + 1;

    });

}

// ==========================================
// HITUNG SUBTOTAL PER BARANG
// ==========================================

function hitungBaris(baris) {

    const harga = Number(
        baris.querySelector('.harga-barang').value
    ) || 0;

    const jumlah = Number(
        baris.querySelector('.jumlah-barang').value
    ) || 0;

    const subtotal = harga * jumlah;

    baris.querySelector('.subtotal-barang').value =
        formatRupiah(subtotal);

    return subtotal;

}

// ==========================================
// HITUNG TOTAL PENGADAAN
// ==========================================

function hitungSemua() {

    let subtotalKeseluruhan = 0;

    const semuaBaris = daftarBarang.querySelectorAll('.baris-barang');

    semuaBaris.forEach((baris) => {

        subtotalKeseluruhan += hitungBaris(baris);

    });

    const ppn = Number(inputPPN.value) || 0;

    const nominalPPN = Math.round(
        subtotalKeseluruhan * (ppn / 100)
    );

    const total = subtotalKeseluruhan + nominalPPN;

    document.getElementById('subtotalTampilan').value =
        formatRupiah(subtotalKeseluruhan);

    document.getElementById('subtotalRingkasan').textContent =
        formatRupiah(subtotalKeseluruhan);

    document.getElementById('tarifPPNRingkasan').textContent =
        ppn.toLocaleString('id-ID') + '%';

    document.getElementById('ppnRingkasan').textContent =
        formatRupiah(nominalPPN);

    document.getElementById('totalRingkasan').textContent =
        formatRupiah(total);

    perbaruiNomor();

}

// ==========================================
// EVENT TAMBAH BARANG
// ==========================================

tombolTambah?.addEventListener('click', tambahBarisBarang);

// ==========================================
// EVENT PILIH BARANG
// ==========================================

daftarBarang?.addEventListener('change', function(event) {

    const target = event.target;

    if (target.classList.contains('pilih-barang')) {

        const baris = target.closest('.baris-barang');

        const opsi = target.options[target.selectedIndex];

        const harga = opsi.dataset.harga || 0;

        baris.querySelector('.harga-barang').value = harga;

        hitungSemua();

    }

});

// ==========================================
// EVENT INPUT HARGA DAN JUMLAH
// ==========================================

daftarBarang?.addEventListener('input', function(event) {

    if (
        event.target.classList.contains('harga-barang') ||
        event.target.classList.contains('jumlah-barang')
    ) {

        hitungSemua();

    }

});

// ==========================================
// EVENT PERUBAHAN PPN
// ==========================================

inputPPN?.addEventListener('input', function() {

    const nilai = Number(inputPPN.value);

    if (nilai < 0 || nilai > 100) {
        inputPPN.setCustomValidity('Tarif PPN harus antara 0 sampai 100%.');
    } else {
        inputPPN.setCustomValidity('');
    }

    hitungSemua();

});

// ==========================================
// HAPUS BARIS BARANG
// ==========================================

daftarBarang?.addEventListener('click', function(event) {

    if (event.target.classList.contains('hapus-barang')) {

        const semuaBaris = daftarBarang.querySelectorAll('.baris-barang');

        if (semuaBaris.length <= 1) {

            alert('Minimal harus ada satu barang dalam pengadaan.');

            return;

        }

        event.target.closest('.baris-barang').remove();

        hitungSemua();

    }

});

// ==========================================
// VALIDASI SEBELUM SUBMIT
// ==========================================

document.getElementById('formPengadaan')?.addEventListener('submit', function(event) {

    const semuaBaris = daftarBarang.querySelectorAll('.baris-barang');

    if (semuaBaris.length === 0) {

        event.preventDefault();

        alert('Tambahkan minimal satu barang.');

        return;

    }

    for (const baris of semuaBaris) {

        const barang = baris.querySelector('.pilih-barang').value;

        const harga = Number(
            baris.querySelector('.harga-barang').value
        );

        const jumlah = Number(
            baris.querySelector('.jumlah-barang').value
        );

        if (!barang || harga <= 0 || jumlah <= 0) {

            event.preventDefault();

            alert('Pastikan seluruh barang, harga, dan jumlah telah diisi dengan benar.');

            return;

        }

    }

    const ppn = Number(inputPPN.value);

    if (!Number.isFinite(ppn) || ppn < 0 || ppn > 100) {

        event.preventDefault();

        alert('Tarif PPN harus antara 0 sampai 100%.');

    }

});

// ==========================================
// BARIS PERTAMA OTOMATIS
// ==========================================

if (daftarBarang && templateBarang) {

    tambahBarisBarang();

}

</script>

</body>
</html>
