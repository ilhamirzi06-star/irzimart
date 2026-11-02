<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/Database.php';

// Inisialisasi koneksi database
$database = new Database();
$koneksi = $database->connect();

// Hanya menerima request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

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
    'Staf Pengadaan',
    'Master Admin'
];

if (!in_array($role, $roleBisaMengelola, true)) {
    http_response_code(403);
    exit('Akses ditolak. Anda tidak memiliki izin mengelola vendor.');
}

// Validasi CSRF
$csrfToken = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !is_string($csrfToken) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    http_response_code(403);
    exit('Token keamanan tidak valid. Silakan muat ulang halaman.');
}

// Mengambil jenis aksi
$action = $_POST['action'] ?? '';

/*
|--------------------------------------------------------------------------
| 1. TAMBAH VENDOR
|--------------------------------------------------------------------------
*/
if ($action === 'create') {

    $namaVendor = trim($_POST['nama_vendor'] ?? '');
    $badanHukum = $_POST['badan_hukum'] ?? '';
    $status = $_POST['status'] ?? '';

    // Validasi input
    if (
        $namaVendor === '' ||
        mb_strlen($namaVendor) > 100 ||
        !in_array($badanHukum, ['Y', 'N'], true) ||
        !in_array($status, ['A', 'TA'], true)
    ) {
        header('Location: create.php?status=data_tidak_valid');
        exit;
    }

    // Periksa nama vendor duplikat
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT idvendor
         FROM vw_vendor
         WHERE LOWER(nama_vendor) = LOWER(?)
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, 's', $namaVendor);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $vendorAda = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ($vendorAda) {
        header('Location: create.php?status=vendor_sudah_ada');
        exit;
    }

    // Simpan ke tabel utama vendor
    $stmt = mysqli_prepare(
        $koneksi,
        "INSERT INTO vendor
            (nama_vendor, badan_hukum, status)
         VALUES (?, ?, ?)"
    );

    mysqli_stmt_bind_param(
        $stmt,
        'sss',
        $namaVendor,
        $badanHukum,
        $status
    );

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        header('Location: index.php?status=tambah_berhasil');
        exit;
    }

    mysqli_stmt_close($stmt);

    header('Location: create.php?status=tambah_gagal');
    exit;
}

/*
|--------------------------------------------------------------------------
| 2. EDIT VENDOR
|--------------------------------------------------------------------------
*/
elseif ($action === 'update') {

    $idvendor = filter_var(
        $_POST['idvendor'] ?? null,
        FILTER_VALIDATE_INT
    );

    $namaVendor = trim($_POST['nama_vendor'] ?? '');
    $badanHukum = $_POST['badan_hukum'] ?? '';
    $status = $_POST['status'] ?? '';

    // Validasi input
    if (
        $idvendor === false ||
        $idvendor === null ||
        $idvendor < 1 ||
        $namaVendor === '' ||
        mb_strlen($namaVendor) > 100 ||
        !in_array($badanHukum, ['Y', 'N'], true) ||
        !in_array($status, ['A', 'TA'], true)
    ) {
        header('Location: index.php?status=data_tidak_valid');
        exit;
    }

    // Periksa keberadaan vendor
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT idvendor
         FROM vw_vendor
         WHERE idvendor = ?"
    );

    mysqli_stmt_bind_param($stmt, 'i', $idvendor);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $vendorAda = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$vendorAda) {
        header('Location: index.php?status=vendor_tidak_ditemukan');
        exit;
    }

    // Periksa nama vendor duplikat
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT idvendor
         FROM vw_vendor
         WHERE LOWER(nama_vendor) = LOWER(?)
           AND idvendor <> ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param(
        $stmt,
        'si',
        $namaVendor,
        $idvendor
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $duplikat = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ($duplikat) {
        header(
            'Location: edit.php?idvendor=' . $idvendor .
            '&status=vendor_sudah_ada'
        );
        exit;
    }

    // Update tabel utama vendor
    $stmt = mysqli_prepare(
        $koneksi,
        "UPDATE vendor
         SET nama_vendor = ?,
             badan_hukum = ?,
             status = ?
         WHERE idvendor = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        'sssi',
        $namaVendor,
        $badanHukum,
        $status,
        $idvendor
    );

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        header('Location: index.php?status=edit_berhasil');
        exit;
    }

    mysqli_stmt_close($stmt);

    header(
        'Location: edit.php?idvendor=' . $idvendor .
        '&status=edit_gagal'
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| 3. HAPUS VENDOR
|--------------------------------------------------------------------------
*/
elseif ($action === 'delete') {

    $idvendor = filter_var(
        $_POST['idvendor'] ?? null,
        FILTER_VALIDATE_INT
    );

    // Validasi ID vendor
    if (
        $idvendor === false ||
        $idvendor === null ||
        $idvendor < 1
    ) {
        header('Location: index.php?status=id_tidak_valid');
        exit;
    }

    // Periksa keberadaan vendor
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT idvendor
         FROM vw_vendor
         WHERE idvendor = ?"
    );

    mysqli_stmt_bind_param($stmt, 'i', $idvendor);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $vendorAda = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$vendorAda) {
        header('Location: index.php?status=vendor_tidak_ditemukan');
        exit;
    }

    // Periksa relasi vendor dengan tabel pengadaan
    // Nama foreign key yang benar: vendor_idvendor
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT COUNT(*) AS total
         FROM pengadaan
         WHERE vendor_idvendor = ?"
    );

    mysqli_stmt_bind_param($stmt, 'i', $idvendor);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    // Tolak penghapusan jika vendor masih digunakan
    if ((int) $data['total'] > 0) {
        header('Location: index.php?status=vendor_digunakan');
        exit;
    }

    // Hapus dari tabel utama
    try {

        $stmt = mysqli_prepare(
            $koneksi,
            "DELETE FROM vendor
             WHERE idvendor = ?"
        );

        mysqli_stmt_bind_param($stmt, 'i', $idvendor);
        mysqli_stmt_execute($stmt);

        $jumlahTerhapus = mysqli_stmt_affected_rows($stmt);

        mysqli_stmt_close($stmt);

        if ($jumlahTerhapus > 0) {
            header('Location: index.php?status=hapus_berhasil');
            exit;
        }

    } catch (mysqli_sql_exception $e) {
        error_log('Hapus vendor gagal: ' . $e->getMessage());
    }

    header('Location: index.php?status=hapus_gagal');
    exit;
}

/*
|--------------------------------------------------------------------------
| 4. AKSI TIDAK DIKENAL
|--------------------------------------------------------------------------
*/
else {
    header('Location: index.php?status=aksi_tidak_valid');
    exit;
}
