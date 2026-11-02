
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/Database.php';

// Inisialisasi koneksi database
$database = new Database();
$koneksi = $database->connect();

// Hanya menerima POST
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
    'Master Admin'
];

if (!in_array($role, $roleBisaMengelola, true)) {
    http_response_code(403);
    exit('Akses ditolak. Anda tidak memiliki izin mengelola satuan.');
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

// Ambil jenis aksi
$action = $_POST['action'] ?? '';

/*
|--------------------------------------------------------------------------
| 1. TAMBAH SATUAN
|--------------------------------------------------------------------------
*/
if ($action === 'create') {

    $namaSatuan = trim($_POST['nama_satuan'] ?? '');

    // Validasi input
    if (
        $namaSatuan === '' ||
        mb_strlen($namaSatuan) > 45
    ) {
        header('Location: create.php?status=data_tidak_valid');
        exit;
    }

    // Periksa nama satuan yang sudah terdaftar
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT idsatuan
         FROM vw_satuan
         WHERE LOWER(nama_satuan) = LOWER(?)
         LIMIT 1"
    );

    mysqli_stmt_bind_param($stmt, 's', $namaSatuan);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $satuanAda = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ($satuanAda) {
        header('Location: create.php?status=satuan_sudah_ada');
        exit;
    }

    // Simpan ke tabel utama
    $stmt = mysqli_prepare(
        $koneksi,
        "INSERT INTO satuan (nama_satuan, status)
         VALUES (?, 1)"
    );

    mysqli_stmt_bind_param($stmt, 's', $namaSatuan);

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
| 2. EDIT SATUAN
|--------------------------------------------------------------------------
*/
elseif ($action === 'update') {

    $idsatuan = filter_var(
        $_POST['idsatuan'] ?? null,
        FILTER_VALIDATE_INT
    );

    $namaSatuan = trim($_POST['nama_satuan'] ?? '');

    // Validasi input
    if (
        $idsatuan === false ||
        $idsatuan === null ||
        $idsatuan < 1 ||
        $namaSatuan === '' ||
        mb_strlen($namaSatuan) > 45
    ) {
        header('Location: index.php?status=data_tidak_valid');
        exit;
    }

    // Periksa apakah satuan tersedia
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT idsatuan
         FROM vw_satuan
         WHERE idsatuan = ?"
    );

    mysqli_stmt_bind_param($stmt, 'i', $idsatuan);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $satuanAda = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if (!$satuanAda) {
        header('Location: index.php?status=satuan_tidak_ditemukan');
        exit;
    }

    // Periksa nama satuan duplikat
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT idsatuan
         FROM vw_satuan
         WHERE LOWER(nama_satuan) = LOWER(?)
         AND idsatuan <> ?
         LIMIT 1"
    );

    mysqli_stmt_bind_param(
        $stmt,
        'si',
        $namaSatuan,
        $idsatuan
    );

    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $duplikat = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ($duplikat) {
        header(
            'Location: edit.php?idsatuan=' . $idsatuan .
            '&status=satuan_sudah_ada'
        );
        exit;
    }

    // Update tabel utama
    $stmt = mysqli_prepare(
        $koneksi,
        "UPDATE satuan
         SET nama_satuan = ?
         WHERE idsatuan = ?"
    );

    mysqli_stmt_bind_param(
        $stmt,
        'si',
        $namaSatuan,
        $idsatuan
    );

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        header('Location: index.php?status=edit_berhasil');
        exit;
    }

    mysqli_stmt_close($stmt);

    header(
        'Location: edit.php?idsatuan=' . $idsatuan .
        '&status=edit_gagal'
    );
    exit;
}

/*
|--------------------------------------------------------------------------
| 3. HAPUS SATUAN
|--------------------------------------------------------------------------
*/
elseif ($action === 'delete') {

    $idsatuan = filter_var(
        $_POST['idsatuan'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        $idsatuan === false ||
        $idsatuan === null ||
        $idsatuan < 1
    ) {
        header('Location: index.php?status=id_tidak_valid');
        exit;
    }

    // Periksa apakah satuan masih digunakan oleh barang
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT COUNT(*) AS total
         FROM barang
         WHERE idsatuan = ?"
    );

    mysqli_stmt_bind_param($stmt, 'i', $idsatuan);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    if ((int) $data['total'] > 0) {
        header('Location: index.php?status=satuan_digunakan');
        exit;
    }

    // Hapus dari tabel utama
    try {
        $stmt = mysqli_prepare(
            $koneksi,
            "DELETE FROM satuan
             WHERE idsatuan = ?"
        );

        mysqli_stmt_bind_param($stmt, 'i', $idsatuan);
        mysqli_stmt_execute($stmt);

        $jumlahTerhapus = mysqli_stmt_affected_rows($stmt);

        mysqli_stmt_close($stmt);

        if ($jumlahTerhapus > 0) {
            header('Location: index.php?status=hapus_berhasil');
            exit;
        }

    } catch (mysqli_sql_exception $e) {
        error_log('Hapus satuan gagal: ' . $e->getMessage());
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
