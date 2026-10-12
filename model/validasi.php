<?php

class Validasi {

    public static function email_valid(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function password_kuat(string $password): bool {
        return strlen($password) >= 8;
    }

    public static function tidak_kosong(array $data, array $wajib): bool {
        foreach ($wajib as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                return false;
            }
        }

        return true;
    }
}