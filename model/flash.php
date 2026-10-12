<?php

class Flash {
    private static function mulai_session(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set(string $pesan): void {
        self::mulai_session();
        $_SESSION['flash_msg'] = $pesan;
    }

    public static function tampilkan(): void {
        self::mulai_session();

        if (isset($_SESSION['flash_msg'])) {
            echo "<p style='color:red;'>" . $_SESSION['flash_msg'] . "</p>";
            unset($_SESSION['flash_msg']);
        }
    }
}