<?php
session_start();
require_once '../../config/Database.php';

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
    'Kasir / Staf Penjualan',
    'Auditor'
];

if (!in_array($role, $roleDiizinkan, true)) {
    header('Location: ../../index.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$namaUser = $_SESSION['user']['username'] ?? 'User';

// 3. Ambil seluruh margin aktif melalui VIEW
$marginResult = $conn->query("
    SELECT *
    FROM vw_margin_penjualan_aktif
    WHERE status = 1
    ORDER BY persen ASC, idmargin_penjualan ASC
");

$daftarMargin = [];

while ($row = $marginResult->fetch_assoc()) {
    $daftarMargin[] = $row;
}

if (empty($daftarMargin)) {
    exit('Belum ada margin penjualan aktif. Aktifkan margin terlebih dahulu.');
}

// 4. Ambil barang dan stok melalui VIEW
$barangResult = $conn->query("
    SELECT *
    FROM vw_barang_penjualan
    WHERE status = 1
    ORDER BY nama ASC
");

$barang = [];

while ($row = $barangResult->fetch_assoc()) {
    $barang[] = $row;
}

// 5. Tentukan margin default
$marginDefault = $daftarMargin[0]['idmargin_penjualan'];

foreach ($daftarMargin as $m) {
    if ((float) $m['persen'] === 10.0) {
        $marginDefault = $m['idmargin_penjualan'];
        break;
    }
}

// 6. CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 7. Pesan notifikasi
$pesan = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Transaksi Penjualan - IrziMart</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f4effa;
            font-family: Arial, sans-serif;
        }

        .navbar {
            background: #482875;
        }

        .navbar-brand {
            color: white !important;
            font-weight: bold;
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
            background: #8655e8;
            color: white;
        }

        .btn-purple:hover {
            background: #7040cc;
            color: white;
        }

        table th,
        table td {
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

<!-- Navbar -->
<nav class="navbar">
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

    <!-- Judul -->
    <div class="mb-3">
        <h4 class="fw-bold mb-1">
            Transaksi Penjualan
        </h4>

        <p class="text-muted mb-0">
            Masukkan barang dan jumlah yang dijual.
        </p>
    </div>

    <!-- Pesan error -->
    <?php if ($pesan): ?>
        <div class="alert alert-danger">
            <?= e($pesan); ?>
        </div>
    <?php endif; ?>

    <form action="proses.php" method="POST" id="formPenjualan">

        <input type="hidden"
               name="csrf_token"
               value="<?= e($_SESSION['csrf_token']); ?>">

        <input type="hidden"
               name="action"
               value="create">

        <!-- Margin dan PPN -->
        <div class="card mb-3">
            <div class="card-body">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label">
                            Margin Penjualan
                        </label>

                        <select name="idmargin_penjualan"
                                id="margin"
                                class="form-select"
                                required>

                            <?php foreach ($daftarMargin as $m): ?>

                                <option
                                    value="<?= (int) $m['idmargin_penjualan']; ?>"
                                    data-persen="<?= e($m['persen']); ?>"
                                    <?= (int) $m['idmargin_penjualan'] === (int) $marginDefault ? 'selected' : ''; ?>>

                                    <?= e($m['persen']); ?>%

                                </option>

                            <?php endforeach; ?>

                        </select>

                        <small class="text-muted">
                            Pilih margin penjualan untuk transaksi ini.
                        </small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">
                            PPN (%)
                        </label>

                        <input type="number"
                               name="ppn"
                               id="ppn"
                               class="form-control"
                               min="0"
                               max="100"
                               step="1"
                               value="11"
                               required>

                        <small class="text-muted">
                            Masukkan persentase PPN transaksi.
                        </small>
                    </div>

                </div>

            </div>
        </div>

        <!-- Daftar barang -->
        <div class="card mb-3">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <h5 class="fw-bold mb-0">
                        Daftar Barang
                    </h5>

                    <button type="button"
                            class="btn btn-purple btn-sm"
                            id="tambahBaris">
                        + Tambah Barang
                    </button>

                </div>

                <div class="table-responsive">

                    <table class="table table-bordered">

                        <thead class="table-light">
                            <tr>
                                <th style="min-width:240px">
                                    Barang
                                </th>

                                <th>Stok</th>

                                <th>Harga Jual</th>

                                <th style="width:110px">
                                    Jumlah
                                </th>

                                <th>Subtotal</th>

                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody id="daftarBaris"></tbody>

                    </table>

                </div>

            </div>
        </div>

        <!-- Ringkasan pembayaran -->
        <div class="card mb-3">
            <div class="card-body">

                <div class="row justify-content-end">

                    <div class="col-md-5">

                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <strong id="labelSubtotal">
                                Rp 0
                            </strong>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Nominal PPN</span>
                            <strong id="labelNominalPpn">
                                Rp 0
                            </strong>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between fs-5">
                            <strong>Total</strong>
                            <strong id="labelTotal">
                                Rp 0
                            </strong>
                        </div>

                    </div>

                </div>

            </div>
        </div>

        <!-- Tombol -->
        <div class="d-flex justify-content-between">

            <a href="index.php" class="btn btn-secondary">
                Kembali
            </a>

            <button type="submit" class="btn btn-purple">
                Simpan Transaksi
            </button>

        </div>

    </form>

</div>

<footer>
    © 2026 IrziMart. Sistem Informasi Manajemen Toko Bangunan.
</footer>

<script>

// Data barang dari database
const daftarBarang = <?= json_encode(
    $barang,
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
); ?>;

const tbody = document.getElementById('daftarBaris');

const marginSelect = document.getElementById('margin');

const ppnInput = document.getElementById('ppn');

// Format rupiah
function rupiah(nilai) {
    return 'Rp ' + Math.round(nilai).toLocaleString('id-ID');
}

// Ambil margin terpilih
function getMargin() {
    return Number(
        marginSelect.selectedOptions[0]?.dataset.persen ?? 0
    );
}

// Hitung harga jual
function hitungHargaJual(hargaDasar) {
    return Math.round(
        hargaDasar * (1 + getMargin() / 100)
    );
}

// Tambah baris barang
function tambahBaris() {

    const tr = document.createElement('tr');

    const tdBarang = document.createElement('td');

    const select = document.createElement('select');

    select.name = 'idbarang[]';
    select.className = 'form-select pilih-barang';
    select.required = true;

    select.add(new Option('Pilih Barang', ''));

    daftarBarang.forEach(b => {

        const option = new Option(
            b.nama,
            b.idbarang
        );

        option.dataset.stok = b.stok;
        option.dataset.harga = b.harga_dasar;

        select.add(option);

    });

    tdBarang.appendChild(select);

    tr.innerHTML = `
        <td></td>

        <td class="stok">-</td>

        <td class="harga">-</td>

        <td>
            <input
                type="number"
                name="jumlah[]"
                class="form-control jumlah"
                min="1"
                value="1"
                required
            >
        </td>

        <td class="subtotal">Rp 0</td>

        <td>
            <button
                type="button"
                class="btn btn-danger btn-sm hapus">
                Hapus
            </button>
        </td>
    `;

    tr.children[0].replaceWith(tdBarang);

    tbody.appendChild(tr);

    hitungTotal();
}

// Hitung setiap baris
function hitungBaris(tr) {

    const select = tr.querySelector('.pilih-barang');

    const option = select.selectedOptions[0];

    const stok = option?.dataset.stok ?? '';

    const hargaDasar = Number(
        option?.dataset.harga ?? 0
    );

    const hargaJual = hitungHargaJual(hargaDasar);

    const jumlahInput = tr.querySelector('.jumlah');

    const jumlah = Number(jumlahInput.value || 0);

    tr.querySelector('.stok').textContent =
        stok === '' ? '-' : stok;

    tr.querySelector('.harga').textContent =
        hargaDasar ? rupiah(hargaJual) : '-';

    tr.querySelector('.subtotal').textContent =
        rupiah(hargaJual * jumlah);

    jumlahInput.max = stok || '';

    hitungTotal();
}

// Hitung keseluruhan transaksi
function hitungTotal() {

    let subtotal = 0;

    tbody.querySelectorAll('tr').forEach(tr => {

        const option = tr.querySelector('.pilih-barang').selectedOptions[0];

        const hargaDasar = Number(
            option?.dataset.harga ?? 0
        );

        const hargaJual = hitungHargaJual(hargaDasar);

        const jumlah = Number(
            tr.querySelector('.jumlah').value || 0
        );

        subtotal += hargaJual * jumlah;

    });

    const ppn = Number(ppnInput.value || 0);

    const nominalPpn = Math.round(
        subtotal * ppn / 100
    );

    document.getElementById('labelSubtotal').textContent =
        rupiah(subtotal);

    document.getElementById('labelNominalPpn').textContent =
        rupiah(nominalPpn);

    document.getElementById('labelTotal').textContent =
        rupiah(subtotal + nominalPpn);
}

// Event tambah barang
document.getElementById('tambahBaris')
    .addEventListener('click', tambahBaris);

// Event perubahan margin
marginSelect.addEventListener('change', function() {

    tbody.querySelectorAll('tr').forEach(tr => {
        hitungBaris(tr);
    });

});

// Event perubahan pilihan barang
tbody.addEventListener('change', function(event) {

    if (event.target.classList.contains('pilih-barang')) {

        hitungBaris(event.target.closest('tr'));

    }

});

// Event perubahan jumlah
tbody.addEventListener('input', function(event) {

    if (event.target.classList.contains('jumlah')) {

        hitungBaris(event.target.closest('tr'));

    }

});

// Event hapus barang
tbody.addEventListener('click', function(event) {

    if (event.target.classList.contains('hapus')) {

        event.target.closest('tr').remove();

        hitungTotal();

    }

});

// Event perubahan PPN
ppnInput.addEventListener('input', hitungTotal);

// Validasi sebelum transaksi dikirim
document.getElementById('formPenjualan')
    .addEventListener('submit', function(event) {

        const baris = tbody.querySelectorAll('tr');

        if (baris.length === 0) {

            event.preventDefault();

            alert('Tambahkan minimal satu barang.');

            return;
        }

        for (const tr of baris) {

            const select = tr.querySelector('.pilih-barang');

            const option = select.selectedOptions[0];

            const jumlah = Number(
                tr.querySelector('.jumlah').value
            );

            const stok = Number(
                option?.dataset.stok ?? 0
            );

            if (
                !select.value ||
                jumlah < 1 ||
                jumlah > stok
            ) {

                event.preventDefault();

                alert(
                    'Pastikan barang dipilih dan jumlah tidak melebihi stok.'
                );

                return;
            }

        }

    });

// Baris pertama otomatis ditambahkan
tambahBaris();

</script>

</body>
</html>
