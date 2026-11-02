<?php

require_once __DIR__ . '/User.php';

class Auth
{
    private User $userModel;

    public function __construct(User $userModel)
    {
        $this->userModel = $userModel;
    }

    /**
     * Login pengguna menggunakan username dan password.
     */
    public function login(
        string $username,
        string $password
    ): bool {
        $user = $this->userModel->findByUsername($username);

        // Akun tidak ditemukan.
        if ($user === null) {
            return false;
        }

        // Verifikasi password hash.
        if (!password_verify($password, $user['password'])) {
            return false;
        }

        // Regenerasi ID session setelah autentikasi berhasil.
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        session_regenerate_id(true);

        // Simpan informasi pengguna untuk halaman aplikasi.
        $_SESSION['user'] = [
            'iduser'   => (int) $user['iduser'],
            'username' => $user['username'],
            'nama'     => $user['username'],
            'role'     => $user['nama_role']
        ];

        // Buat token CSRF untuk form selanjutnya.
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        return true;
    }

    /**
     * Registrasi pengguna baru.
     * Role tidak diterima dari input form.
     */
    public function register(
        string $username,
        string $password,
        string $confirmPassword
    ): bool {
        $username = trim($username);

        if ($username === '' || $password === '') {
            throw new InvalidArgumentException(
                'Username dan password wajib diisi.'
            );
        }

        if (strlen($username) > 45) {
            throw new InvalidArgumentException(
                'Username maksimal 45 karakter.'
            );
        }

        if (strlen($password) < 8) {
            throw new InvalidArgumentException(
                'Password minimal 8 karakter.'
            );
        }

        if ($password !== $confirmPassword) {
            throw new InvalidArgumentException(
                'Konfirmasi password tidak cocok.'
            );
        }

        if ($this->userModel->findByUsername($username) !== null) {
            throw new InvalidArgumentException(
                'Username sudah digunakan.'
            );
        }

        // User::create menetapkan role Auditor di sisi server.
        return $this->userModel->create($username, $password);
    }

    /**
     * Memeriksa apakah pengguna sudah login.
     */
    public function check(): bool
    {
        return isset($_SESSION['user']['iduser']);
    }

    /**
     * Mengambil informasi pengguna yang sedang login.
     */
    public function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Logout dan hapus session.
     */
    public function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires'  => time() - 42000,
                    'path'     => $params['path'],
                    'domain'   => $params['domain'],
                    'secure'   => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'] ?? 'Lax'
                ]
            );
        }

        session_destroy();
    }
}
