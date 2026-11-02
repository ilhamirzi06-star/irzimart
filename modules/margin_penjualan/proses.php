
<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

$database = new Database();
$koneksi = $database->connect();

// Validasi login
if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

// Validasi role
$role = $_SESSION['user']['role'] ?? '';

$allowedRoles = [
    'Administrator',
    'Master Admin'
];

if (!in_array($role, $allowedRoles, true)) {
    header("Location: ../../dashboard.php");
    exit;
}

// Validasi metode request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php?pesan=aksi_tidak_valid");
    exit;
}

// Validasi CSRF
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    die('Token keamanan tidak valid.');
}

// Mengambil aksi
$action = $_POST['action'] ?? '';

// Helper redirect
function redirectMargin($pesan)
{
    header("Location: index.php?pesan=" . urlencode($pesan));
    exit;
}

// Helper validasi ID
function validasiId($id)
{
    return filter_var($id, FILTER_VALIDATE_INT) !== false
        && (int) $id > 0;
}

// CREATE
if ($action === 'create') {

    $iduser = $_SESSION['user']['iduser'] ?? null;
    $persenInput = trim($_POST['persen'] ?? '');
    $statusInput = $_POST['status'] ?? '';

    // Validasi user
    if (!validasiId($iduser)) {
        redirectMargin('data_tidak_valid');
    }

    // Validasi persentase
    if (
        $persenInput === '' ||
        !is_numeric($persenInput) ||
        (float) $persenInput < 0 ||
        (float) $persenInput > 100
    ) {
        redirectMargin('data_tidak_valid');
    }

    // Validasi status
    if (!in_array($statusInput, ['0', '1'], true)) {
        redirectMargin('data_tidak_valid');
    }

    $persen = (float) $persenInput;
    $status = (int) $statusInput;
    $iduser = (int) $iduser;

    $query = "
        INSERT INTO margin_penjualan
            (persen, status, iduser)
        VALUES
            (?, ?, ?)
    ";

    $stmt = mysqli_prepare($koneksi, $query);

    if (!$stmt) {
        error_log('Prepare create margin gagal: ' . mysqli_error($koneksi));
        redirectMargin('data_tidak_valid');
    }

    mysqli_stmt_bind_param(
        $stmt,
        'dii',
        $persen,
        $status,
        $iduser
    );

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        redirectMargin('tambah_berhasil');
    }

    error_log('Create margin gagal: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);

    redirectMargin('data_tidak_valid');
}

// UPDATE
elseif ($action === 'update') {

    $idmargin = $_POST['idmargin_penjualan'] ?? '';
    $persenInput = trim($_POST['persen'] ?? '');
    $statusInput = $_POST['status'] ?? '';

    // Validasi ID
    if (!validasiId($idmargin)) {
        redirectMargin('data_tidak_valid');
    }

    $idmargin = (int) $idmargin;

    // Validasi persentase
    if (
        $persenInput === '' ||
        !is_numeric($persenInput) ||
        (float) $persenInput < 0 ||
        (float) $persenInput > 100
    ) {
        redirectMargin('data_tidak_valid');
    }

    // Validasi status
    if (!in_array($statusInput, ['0', '1'], true)) {
        redirectMargin('data_tidak_valid');
    }

    $persen = (float) $persenInput;
    $status = (int) $statusInput;

    // Memastikan data margin tersedia
    $checkQuery = "
        SELECT idmargin_penjualan
        FROM margin_penjualan
        WHERE idmargin_penjualan = ?
    ";

    $checkStmt = mysqli_prepare($koneksi, $checkQuery);

    if (!$checkStmt) {
        error_log('Prepare check margin gagal: ' . mysqli_error($koneksi));
        redirectMargin('margin_tidak_ditemukan');
    }

    mysqli_stmt_bind_param($checkStmt, 'i', $idmargin);
    mysqli_stmt_execute($checkStmt);

    $checkResult = mysqli_stmt_get_result($checkStmt);
    $exists = mysqli_num_rows($checkResult) > 0;

    mysqli_stmt_close($checkStmt);

    if (!$exists) {
        redirectMargin('margin_tidak_ditemukan');
    }

    // Update data
    $query = "
        UPDATE margin_penjualan
        SET
            persen = ?,
            status = ?
        WHERE idmargin_penjualan = ?
    ";

    $stmt = mysqli_prepare($koneksi, $query);

    if (!$stmt) {
        error_log('Prepare update margin gagal: ' . mysqli_error($koneksi));
        redirectMargin('data_tidak_valid');
    }

    mysqli_stmt_bind_param(
        $stmt,
        'dii',
        $persen,
        $status,
        $idmargin
    );

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        redirectMargin('edit_berhasil');
    }

    error_log('Update margin gagal: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);

    redirectMargin('data_tidak_valid');
}

// DELETE
elseif ($action === 'delete') {

    $idmargin = $_POST['idmargin_penjualan'] ?? '';

    // Validasi ID
    if (!validasiId($idmargin)) {
        redirectMargin('data_tidak_valid');
    }

    $idmargin = (int) $idmargin;

    // Memastikan data tersedia
    $checkQuery = "
        SELECT idmargin_penjualan
        FROM margin_penjualan
        WHERE idmargin_penjualan = ?
    ";

    $checkStmt = mysqli_prepare($koneksi, $checkQuery);

    if (!$checkStmt) {
        error_log('Prepare check delete margin gagal: ' . mysqli_error($koneksi));
        redirectMargin('hapus_gagal');
    }

    mysqli_stmt_bind_param($checkStmt, 'i', $idmargin);
    mysqli_stmt_execute($checkStmt);

    $checkResult = mysqli_stmt_get_result($checkStmt);
    $exists = mysqli_num_rows($checkResult) > 0;

    mysqli_stmt_close($checkStmt);

    if (!$exists) {
        redirectMargin('margin_tidak_ditemukan');
    }

    // Hapus data
    $query = "
        DELETE FROM margin_penjualan
        WHERE idmargin_penjualan = ?
    ";

    $stmt = mysqli_prepare($koneksi, $query);

    if (!$stmt) {
        error_log('Prepare delete margin gagal: ' . mysqli_error($koneksi));
        redirectMargin('hapus_gagal');
    }

    mysqli_stmt_bind_param($stmt, 'i', $idmargin);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        redirectMargin('hapus_berhasil');
    }

    error_log('Delete margin gagal: ' . mysqli_stmt_error($stmt));
    mysqli_stmt_close($stmt);

    redirectMargin('hapus_gagal');
}

// AKSI TIDAK DIKENAL
else {
    redirectMargin('aksi_tidak_valid');
}
