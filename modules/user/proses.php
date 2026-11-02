<?php
session_start();

require_once __DIR__ . '/../../config/Database.php';

// Fungsi keamanan output
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Pastikan user sudah login
if (!isset($_SESSION['user'])) {
    header("Location: ../../login.php");
    exit;
}

// Batasi akses hanya untuk Master Admin
if (($_SESSION['user']['role'] ?? '') !== 'Master Admin') {
    header("Location: ../../index.php?pesan=akses_ditolak");
    exit;
}

// Proses hanya menerima POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// Validasi CSRF Token
$csrfToken = $_POST['csrf_token'] ?? '';

if (
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $csrfToken)
) {
    header("Location: index.php?pesan=csrf_invalid");
    exit;
}

// Ambil action dan ID user
$action = $_POST['action'] ?? '';
$iduser = filter_input(INPUT_POST, 'iduser', FILTER_VALIDATE_INT);

if (!$iduser || $iduser <= 0) {
    header("Location: index.php?pesan=data_tidak_valid");
    exit;
}

$currentUserId = (int) ($_SESSION['user']['iduser'] ?? 0);

try {
    $database = new Database();
    $koneksi = $database->connect();

    // ==========================================
    // UPDATE USER
    // ==========================================
    if ($action === 'update') {

        $username = trim($_POST['username'] ?? '');
        $idrole = filter_input(INPUT_POST, 'idrole', FILTER_VALIDATE_INT);

        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // Validasi username
        if (
            $username === '' ||
            strlen($username) > 45 ||
            !preg_match('/\A[A-Za-z0-9_.-]+\z/', $username)
        ) {
            header("Location: index.php?pesan=data_tidak_valid");
            exit;
        }

        // Validasi role
        if (!$idrole || $idrole <= 0) {
            header("Location: index.php?pesan=data_tidak_valid");
            exit;
        }

        // Pastikan user yang akan diubah tersedia
        $stmt = $koneksi->prepare("
            SELECT iduser, idrole
            FROM vw_user
            WHERE iduser = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $iduser);
        $stmt->execute();

        $result = $stmt->get_result();
        $existingUser = $result->fetch_assoc();

        $stmt->close();

        if (!$existingUser) {
            header("Location: index.php?pesan=data_tidak_ditemukan");
            exit;
        }

        // Pastikan role tersedia
        $stmt = $koneksi->prepare("
            SELECT idrole
            FROM vw_role
            WHERE idrole = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $idrole);
        $stmt->execute();

        $roleExists = $stmt->get_result()->num_rows > 0;

        $stmt->close();

        if (!$roleExists) {
            header("Location: index.php?pesan=data_tidak_valid");
            exit;
        }

        // Cegah perubahan role akun sendiri
        if (
            $iduser === $currentUserId &&
            $idrole !== (int) $existingUser['idrole']
        ) {
            header("Location: index.php?pesan=role_akun_sendiri");
            exit;
        }

        // Periksa username duplikat
        $stmt = $koneksi->prepare("
            SELECT iduser
            FROM vw_user
            WHERE username = ?
              AND iduser != ?
            LIMIT 1
        ");

        $stmt->bind_param("si", $username, $iduser);
        $stmt->execute();

        $usernameExists = $stmt->get_result()->num_rows > 0;

        $stmt->close();

        if ($usernameExists) {
            header("Location: index.php?pesan=username_sudah_digunakan");
            exit;
        }

        // Jika password diisi, validasi password baru
        if ($password !== '' || $passwordConfirm !== '') {

            if (
                strlen($password) < 8 ||
                strlen($password) > 72
            ) {
                header("Location: index.php?pesan=password_tidak_valid");
                exit;
            }

            if ($password !== $passwordConfirm) {
                header("Location: index.php?pesan=password_tidak_sesuai");
                exit;
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $koneksi->prepare("
                UPDATE user
                SET username = ?, password = ?, idrole = ?
                WHERE iduser = ?
            ");

            $stmt->bind_param(
                "ssii",
                $username,
                $passwordHash,
                $idrole,
                $iduser
            );

        } else {

            // Update tanpa mengubah password
            $stmt = $koneksi->prepare("
                UPDATE user
                SET username = ?, idrole = ?
                WHERE iduser = ?
            ");

            $stmt->bind_param(
                "sii",
                $username,
                $idrole,
                $iduser
            );
        }

        $stmt->execute();
        $stmt->close();

        header("Location: index.php?pesan=berhasil");
        exit;
    }

    // ==========================================
    // DELETE USER
    // ==========================================
    elseif ($action === 'delete') {

        // Cegah penghapusan akun sendiri
        if ($iduser === $currentUserId) {
            header("Location: index.php?pesan=hapus_akun_sendiri");
            exit;
        }

        // Pastikan user tersedia
        $stmt = $koneksi->prepare("
            SELECT iduser
            FROM vw_user
            WHERE iduser = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $iduser);
        $stmt->execute();

        $userExists = $stmt->get_result()->num_rows > 0;

        $stmt->close();

        if (!$userExists) {
            header("Location: index.php?pesan=data_tidak_ditemukan");
            exit;
        }

        // Hapus dari tabel utama
        $stmt = $koneksi->prepare("
            DELETE FROM user
            WHERE iduser = ?
        ");

        $stmt->bind_param("i", $iduser);
        $stmt->execute();

        $stmt->close();

        header("Location: index.php?pesan=berhasil");
        exit;
    }

    // ==========================================
    // ACTION TIDAK DIKENALI
    // ==========================================
    else {
        header("Location: index.php?pesan=data_tidak_valid");
        exit;
    }

} catch (mysqli_sql_exception $e) {

    error_log("User process database error: " . $e->getMessage());

    // Kemungkinan user masih direferensikan oleh tabel lain
    if ($e->getCode() === 1451 || $e->getCode() === 1452) {
        header("Location: index.php?pesan=data_digunakan");
        exit;
    }

    header("Location: index.php?pesan=database_error");
    exit;

} catch (Throwable $e) {

    error_log("User process error: " . $e->getMessage());

    header("Location: index.php?pesan=database_error");
    exit;
}
