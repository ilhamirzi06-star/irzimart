<?php
session_start();

// Hapus seluruh data sesi
$_SESSION = [];

// Hapus cookie sesi jika digunakan
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Hancurkan sesi login
session_destroy();

// Arahkan kembali ke halaman login
header('Location: login.php');
exit;