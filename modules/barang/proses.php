
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';

/*
|--------------------------------------------------------------------------
| 1. VALIDASI LOGIN DAN HAK AKSES
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user']['iduser'])) {
    header('Location: ../../login.php');
    exit;
}

$role = $_SESSION['user']['role'] ?? '';

$roleBisaMengubah = [
    'Administrator',
    'Master Admin',
    'Manajer Gudang'
];

if (!in_array($role, $roleBisaMengubah, true)) {
    http_response_code(403);
    exit('Akses ditolak. Anda tidak memiliki izin mengelola barang.');
}

/*
|--------------------------------------------------------------------------
| 2. VALIDASI METODE REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| 3. VALIDASI CSRF TOKEN
|--------------------------------------------------------------------------
*/

$tokenSession = $_SESSION['csrf_token'] ?? '';
$tokenForm = $_POST['csrf_token'] ?? '';

if (
    !is_string($tokenSession) ||
    !is_string($tokenForm) ||
    $tokenSession === '' ||
    !hash_equals($tokenSession, $tokenForm)
) {
    http_response_code(403);
    exit('Permintaan tidak valid. Silakan muat ulang halaman.');
}

/*
|--------------------------------------------------------------------------
| 4. KONEKSI DATABASE
|--------------------------------------------------------------------------
*/

try {
    $database = new Database();
    $db = $database->connect();

    if (!$db instanceof mysqli) {
        throw new RuntimeException('Koneksi database tidak valid.');
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    exit('Koneksi database gagal. Silakan periksa konfigurasi database.');
}

/*
|--------------------------------------------------------------------------
| 5. AMBIL AKSI
|--------------------------------------------------------------------------
*/

$aksi = $_POST['action'] ?? '';

if (!in_array($aksi, ['create', 'update', 'delete'], true)) {
    header('Location: index.php?status=aksi_tidak_valid');
    exit;
}

/*
|--------------------------------------------------------------------------
| 6. PROSES TAMBAH BARANG
|--------------------------------------------------------------------------
*/

if ($aksi === 'create') {
    $nama = trim($_POST['nama'] ?? '');
    $jenis = trim($_POST['jenis'] ?? '');

    $idsatuan = filter_var(
        $_POST['idsatuan'] ?? null,
        FILTER_VALIDATE_INT
    );

    $harga = filter_var(
        $_POST['harga'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        $nama === '' ||
        strlen($nama) > 45 ||
        $jenis === '' ||
        $idsatuan === false ||
        $idsatuan === null ||
        $idsatuan <= 0 ||
        $harga === false ||
        $harga === null ||
        $harga < 0
    ) {
        header('Location: create.php?status=input_tidak_valid');
        exit;
    }

    try {
        $stmt = $db->prepare(
            "INSERT INTO barang (nama, jenis, idsatuan, harga)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssii",
            $nama,
            $jenis,
            $idsatuan,
            $harga
        );

        $stmt->execute();
        $stmt->close();

        header('Location: index.php?status=tambah_berhasil');
        exit;

    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());

        header('Location: create.php?status=tambah_gagal');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| 7. PROSES EDIT BARANG
|--------------------------------------------------------------------------
*/

if ($aksi === 'update') {
    $idbarang = filter_var(
        $_POST['idbarang'] ?? null,
        FILTER_VALIDATE_INT
    );

    $nama = trim($_POST['nama'] ?? '');
    $jenis = trim($_POST['jenis'] ?? '');

    $idsatuan = filter_var(
        $_POST['idsatuan'] ?? null,
        FILTER_VALIDATE_INT
    );

    $harga = filter_var(
        $_POST['harga'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        $idbarang === false ||
        $idbarang === null ||
        $idbarang <= 0 ||
        $nama === '' ||
        strlen($nama) > 45 ||
        $jenis === '' ||
        $idsatuan === false ||
        $idsatuan === null ||
        $idsatuan <= 0 ||
        $harga === false ||
        $harga === null ||
        $harga < 0
    ) {
        header('Location: index.php?status=input_tidak_valid');
        exit;
    }

    try {
        $stmt = $db->prepare(
            "UPDATE barang
             SET nama = ?, jenis = ?, idsatuan = ?, harga = ?
             WHERE idbarang = ?"
        );

        $stmt->bind_param(
            "ssiii",
            $nama,
            $jenis,
            $idsatuan,
            $harga,
            $idbarang
        );

        $stmt->execute();

        if ($stmt->affected_rows >= 0) {
            $stmt->close();

            header('Location: index.php?status=edit_berhasil');
            exit;
        }

        $stmt->close();

        header('Location: index.php?status=edit_gagal');
        exit;

    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());

        header('Location: index.php?status=edit_gagal');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| 8. PROSES HAPUS BARANG
|--------------------------------------------------------------------------
*/

if ($aksi === 'delete') {
    $idbarang = filter_var(
        $_POST['idbarang'] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        $idbarang === false ||
        $idbarang === null ||
        $idbarang <= 0
    ) {
        header('Location: index.php?status=id_tidak_valid');
        exit;
    }

    try {
        $stmt = $db->prepare(
            "DELETE FROM barang WHERE idbarang = ?"
        );

        $stmt->bind_param("i", $idbarang);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $stmt->close();

            header('Location: index.php?status=hapus_berhasil');
            exit;
        }

        $stmt->close();

        header('Location: index.php?status=data_tidak_ditemukan');
        exit;

    } catch (mysqli_sql_exception $e) {
        error_log($e->getMessage());

        header('Location: index.php?status=hapus_gagal');
        exit;
    }
}
