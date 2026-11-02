<?php
session_start();

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/Auth.php';

// Jika sudah login, langsung menuju dashboard
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

// Membuat objek OOP
try {
    $database = new Database();
    $db = $database->connect();

    $userModel = new User($db);
    $auth = new Auth($userModel);

    // Membuat token CSRF
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';

        if (
            empty($csrfToken) ||
            !hash_equals($_SESSION['csrf_token'], $csrfToken)
        ) {
            $error = 'Sesi tidak valid. Silakan muat ulang halaman.';
        } elseif ($username === '' || $password === '') {
            $error = 'Username dan password wajib diisi.';
        } else {
            // Proses login ditangani oleh class Auth
            if ($auth->login($username, $password)) {
                // Mencegah session fixation
                session_regenerate_id(true);

                // Token baru setelah login
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header('Location: index.php');
                exit;
            }

            $error = 'Username atau password salah.';
        }
    }
} catch (Throwable $e) {
    error_log($e->getMessage());
    $error = 'Terjadi kesalahan pada sistem. Silakan coba kembali.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | IrziMart</title>

    <style>
        :root {
            --primary: #8655E8;
            --secondary: #482875;
            --lime: #C8F52F;
            --dark: #171321;
            --muted: #777383;
            --border: #e9e5f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 28px;
            background: #f4f1fa;
            color: var(--dark);
            font-family: "Segoe UI", Arial, sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 1020px;
            min-height: 610px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #fff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 22px 65px rgba(44, 25, 75, .13);
        }

        .login-form {
            padding: 54px 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 44px;
        }

        .brand-logo {
            width: 45px;
            height: 45px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: var(--primary);
            color: #fff;
            font-size: 25px;
            font-weight: 800;
        }

        .brand-name {
            font-size: 25px;
            font-weight: 800;
            letter-spacing: -.8px;
        }

        .brand-name span {
            color: var(--primary);
        }

        h1 {
            font-size: 31px;
            letter-spacing: -.8px;
            margin-bottom: 10px;
        }

        .subtitle {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 30px;
        }

        .alert {
            background: #fff0f0;
            color: #b42318;
            border: 1px solid #ffd4d4;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 13px;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .field {
            margin-bottom: 20px;
        }

        .field label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 9px;
        }

        .field input {
            width: 100%;
            padding: 14px 15px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: .2s;
        }

        .field input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(134, 85, 232, .12);
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap input {
            padding-right: 70px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: var(--primary);
            cursor: pointer;
            font-weight: 700;
            font-size: 12px;
        }

        .forgot {
            display: block;
            text-align: right;
            margin-top: -7px;
            margin-bottom: 24px;
            color: var(--primary);
            font-size: 12px;
            text-decoration: none;
        }

        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: .2s;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary);
            transform: translateY(-1px);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 13px;
            color: #9a95a5;
            font-size: 12px;
            margin: 23px 0;
        }

        .divider::before,
        .divider::after {
            content: "";
            flex: 1;
            height: 1px;
            background: var(--border);
        }

        .btn-google {
            gap: 10px;
            color: #33303b;
            background: white;
            border: 1px solid var(--border);
        }

        .btn-google:hover {
            background: #f9f7fc;
            border-color: #c9bfdc;
        }

        .google-g {
            font-size: 19px;
            font-weight: 800;
            color: #4285F4;
        }

        .register-text {
            margin-top: 27px;
            text-align: center;
            color: var(--muted);
            font-size: 13px;
        }

        .register-text a {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
        }

        .copyright {
            margin-top: 30px;
            color: #aaa5b2;
            font-size: 11px;
            text-align: center;
        }

        .welcome-panel {
            position: relative;
            overflow: hidden;
            padding: 52px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: white;
            background:
                radial-gradient(
                    circle at 85% 15%,
                    rgba(200, 245, 47, .22),
                    transparent 28%
                ),
                linear-gradient(145deg, #8655E8 0%, #482875 65%, #241638 100%);
        }

        .welcome-panel::before {
            content: "";
            position: absolute;
            width: 310px;
            height: 310px;
            right: -125px;
            bottom: -90px;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 50%;
            box-shadow:
                0 0 0 35px rgba(255,255,255,.035),
                0 0 0 75px rgba(255,255,255,.025);
        }

        .panel-label {
            position: relative;
            display: inline-flex;
            align-self: flex-start;
            align-items: center;
            gap: 9px;
            padding: 9px 13px;
            border-radius: 30px;
            border: 1px solid rgba(255,255,255,.24);
            background: rgba(255,255,255,.09);
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .panel-label span {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: var(--lime);
        }

        .welcome-content {
            position: relative;
            z-index: 1;
            margin: 50px 0;
        }

        .welcome-content .eyebrow {
            color: var(--lime);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 18px;
        }

        .welcome-content h2 {
            max-width: 390px;
            font-size: clamp(34px, 4vw, 48px);
            line-height: 1.15;
            letter-spacing: -1.5px;
            margin-bottom: 22px;
        }

        .welcome-content h2 span {
            color: var(--lime);
        }

        .welcome-content p {
            max-width: 370px;
            color: rgba(255,255,255,.78);
            font-size: 14px;
            line-height: 1.9;
        }

        .features {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 14px;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 13px;
            font-size: 13px;
            color: rgba(255,255,255,.9);
        }

        .feature-icon {
            width: 35px;
            height: 35px;
            display: grid;
            place-items: center;
            border-radius: 10px;
            background: rgba(255,255,255,.12);
            color: var(--lime);
            font-size: 17px;
        }

        @media (max-width: 760px) {
            body {
                padding: 15px;
            }

            .login-card {
                max-width: 460px;
                grid-template-columns: 1fr;
            }

            .login-form {
                padding: 35px 26px;
            }

            .brand {
                margin-bottom: 32px;
            }

            .welcome-panel {
                padding: 35px 28px;
                min-height: 390px;
            }

            .welcome-content {
                margin: 35px 0;
            }

            .welcome-content h2 {
                font-size: 35px;
            }
        }
    </style>
</head>

<body>
    <main class="login-card">

        <!-- BAGIAN FORM LOGIN -->
        <section class="login-form">
            <div class="brand">
                <div class="brand-logo">I</div>
                <div class="brand-name">Irzi<span>Mart</span></div>
            </div>

            <h1>Selamat datang!</h1>
            <p class="subtitle">
                Masuk ke akunmu untuk mengelola kebutuhan dan
                informasi toko bangunan IrziMart.
            </p>

            <?php if ($error !== ''): ?>
                <div class="alert" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION['csrf_token'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                <div class="field">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Masukkan username"
                        value="<?= htmlspecialchars(
                            $username,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Masukkan password"
                            autocomplete="current-password"
                            required
                        >
                        <button
                            class="toggle-password"
                            type="button"
                            id="togglePassword"
                        >Lihat</button>
                    </div>
                </div>

                <a class="forgot" href="forgot-password.php">
                    Lupa password?
                </a>

                <button type="submit" class="btn btn-primary">
                    Masuk ke IrziMart
                </button>
            </form>

            <div class="divider">atau masuk menggunakan</div>

            <!-- Memulai proses OAuth Google -->
            <a class="btn btn-google" href="google-login.php">
                <span class="google-g">G</span>
                Masuk dengan Google
            </a>

            <p class="register-text">
                Belum memiliki akun?
                <a href="register.php">Daftar sekarang</a>
            </p>

            <p class="copyright">
                &copy; <?= date('Y') ?> IrziMart. All rights reserved.
            </p>
        </section>

        <!-- PANEL SAMBUTAN TOKO -->
        <aside class="welcome-panel">
            <div class="panel-label">
                <span></span>
                Toko Bangunan Terpercaya
            </div>

            <div class="welcome-content">
                <p class="eyebrow">Build Your Better Future</p>

                <h2>
                    Bangun impianmu bersama
                    <span>IrziMart.</span>
                </h2>

                <p>
                    Selamat datang di IrziMart, toko bangunan yang
                    membantu memenuhi kebutuhan material konstruksi
                    untuk rumah, renovasi, dan berbagai proyek
                    pembangunanmu. Temukan kebutuhan bangunan dan
                    kelola persediaan dengan lebih mudah melalui
                    satu sistem.
                </p>
            </div>

            <div class="features">
                <div class="feature">
                    <div class="feature-icon">&#10003;</div>
                    <span>Informasi barang lebih terorganisasi</span>
                </div>

                <div class="feature">
                    <div class="feature-icon">&#9638;</div>
                    <span>Pengelolaan data toko yang praktis</span>
                </div>

                <div class="feature">
                    <div class="feature-icon">&#8594;</div>
                    <span>Dukung pembangunan dengan lebih mudah</span>
                </div>
            </div>
        </aside>

    </main>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';

            passwordInput.type = isHidden ? 'text' : 'password';
            togglePassword.textContent = isHidden ? 'Sembunyikan' : 'Lihat';
        });
    </script>
</body>
</html>
