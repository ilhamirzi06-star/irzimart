<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

// Validasi login
if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

// Validasi role
$role = $_SESSION['user']['role'] ?? '';

if ($role !== 'Master Admin') {
    header("Location: ../../dashboard.php");
    exit;
}

// Validasi metode request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php?pesan=data_tidak_valid");
    exit;
}

// Validasi CSRF
if (
    !isset($_POST['csrf_token']) ||
    !isset($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    header("Location: index.php?pesan=csrf_invalid");
    exit;
}

// Validasi ID Role
$idrole = filter_var($_POST['idrole'] ?? null, FILTER_VALIDATE_INT);

if (!$idrole || $idrole < 1) {
    header("Location: index.php?pesan=data_tidak_valid");
    exit;
}

// Koneksi database
$database = new Database();
$koneksi = $database->connect();

// Mengambil jenis aksi
$action = $_POST['action'] ?? '';

try {

    // ==========================================
    // UPDATE ROLE
    // ==========================================
    if ($action === 'update') {

        $nama_role = trim($_POST['nama_role'] ?? '');

        // Validasi nama role
        if ($nama_role === '') {
            header("Location: index.php?pesan=nama_kosong");
            exit;
        }

        if (mb_strlen($nama_role) > 100) {
            header("Location: index.php?pesan=data_tidak_valid");
            exit;
        }

        // Memulai transaksi
        $koneksi->begin_transaction();

        // Memastikan role tersedia
        $stmtCek = $koneksi->prepare(
            "SELECT idrole FROM role WHERE idrole = ? FOR UPDATE"
        );

        $stmtCek->bind_param("i", $idrole);
        $stmtCek->execute();

        $cekRole = $stmtCek->get_result();

        if ($cekRole->num_rows === 0) {
            $stmtCek->close();
            $koneksi->rollback();

            header("Location: index.php?pesan=data_tidak_ditemukan");
            exit;
        }

        $stmtCek->close();

        // Memeriksa nama role duplikat
        $stmtDuplikat = $koneksi->prepare(
            "SELECT idrole
             FROM role
             WHERE nama_role = ?
             AND idrole != ?
             LIMIT 1"
        );

        $stmtDuplikat->bind_param("si", $nama_role, $idrole);
        $stmtDuplikat->execute();

        $duplikat = $stmtDuplikat->get_result();

        if ($duplikat->num_rows > 0) {
            $stmtDuplikat->close();
            $koneksi->rollback();

            header("Location: index.php?pesan=nama_duplikat");
            exit;
        }

        $stmtDuplikat->close();

        // Update tabel role
        $stmtUpdate = $koneksi->prepare(
            "UPDATE role SET nama_role = ? WHERE idrole = ?"
        );

        $stmtUpdate->bind_param("si", $nama_role, $idrole);
        $stmtUpdate->execute();
        $stmtUpdate->close();

        $koneksi->commit();

        header("Location: index.php?pesan=berhasil_edit");
        exit;
    }

    // ==========================================
    // DELETE ROLE
    // ==========================================
    elseif ($action === 'delete') {

        // Memulai transaksi
        $koneksi->begin_transaction();

        // Memastikan role tersedia
        $stmtCek = $koneksi->prepare(
            "SELECT idrole FROM role WHERE idrole = ? FOR UPDATE"
        );

        $stmtCek->bind_param("i", $idrole);
        $stmtCek->execute();

        $cekRole = $stmtCek->get_result();

        if ($cekRole->num_rows === 0) {
            $stmtCek->close();
            $koneksi->rollback();

            header("Location: index.php?pesan=data_tidak_ditemukan");
            exit;
        }

        $stmtCek->close();

        // Memeriksa apakah role masih digunakan oleh user
        $stmtUser = $koneksi->prepare(
            "SELECT iduser
             FROM user
             WHERE idrole = ?
             LIMIT 1"
        );

        $stmtUser->bind_param("i", $idrole);
        $stmtUser->execute();

        $userTerkait = $stmtUser->get_result();

        if ($userTerkait->num_rows > 0) {
            $stmtUser->close();
            $koneksi->rollback();

            header("Location: index.php?pesan=data_digunakan");
            exit;
        }

        $stmtUser->close();

        // Menghapus role dari tabel role
        $stmtDelete = $koneksi->prepare(
            "DELETE FROM role WHERE idrole = ?"
        );

        $stmtDelete->bind_param("i", $idrole);
        $stmtDelete->execute();
        $stmtDelete->close();

        $koneksi->commit();

        header("Location: index.php?pesan=berhasil_hapus");
        exit;
    }

    // ==========================================
    // AKSI TIDAK VALID
    // ==========================================
    else {
        $koneksi->rollback();

        header("Location: index.php?pesan=data_tidak_valid");
        exit;
    }

} catch (mysqli_sql_exception $e) {

    // Membatalkan transaksi jika terjadi kesalahan
    try {
        $koneksi->rollback();
    } catch (Throwable $rollbackError) {
        error_log('Rollback role gagal: ' . $rollbackError->getMessage());
    }

    error_log('Proses role gagal: ' . $e->getMessage());

    // Error foreign key
    if ((int) $e->getCode() === 1451) {
        header("Location: index.php?pesan=data_digunakan");
        exit;
    }

    header("Location: index.php?pesan=database_error");
    exit;
}
