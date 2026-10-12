<?php

interface Autentikasi {

    public function verifikasi(
        string $email,
        string $password
    ): ?User;
}