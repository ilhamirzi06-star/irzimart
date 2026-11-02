<?php
session_start();

require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/Auth.php';

// Jika sudah login, arahkan ke dashboard.
if (isset($_SESSION['user']['iduser'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$username = '';

try {
    $database = new Database();
    $db = $database->connect();

    $userModel = new User($db);
    $auth = new Auth($userModel);

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? '';

        if (
            !is_string($csrfToken) ||
            !hash_equals($_SESSION['csrf_token'], $csrfToken)
        ) {
            $error = 'Sesi tidak valid. Silakan muat ulang halaman.';
        } else {
            $success = $auth->register(
                $username,
                $password,
                $confirmPassword
            );

            if ($success) {
                // Token baru setelah registrasi berhasil.
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                header('Location: login.php?registered=1');
                exit;
            }

            $error = 'Registrasi gagal. Silakan coba kembali.';
        }
    }
} catch (InvalidArgumentException $e) {
    $error = $e->getMessage();
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
    <title>Daftar Akun | IrziMart</title>

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
            padding: 24px;
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--dark);
            background: #f4f1fa;
        }

        .card {
            width: 100%;
            max-width: 1000px;
            min-height: 610px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #fff;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 22px 65px rgba(44, 25, 75, .13);
        }

        .form-panel {
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 35px;
        }

        .logo {
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            background: var(--primary);
            color: white;
            border-radius: 12px;
            font-size: 25px;
            font-weight: 800;
        }

        .brand-name {
            font-size: 24px;
            font-weight: 800;
        }

        .brand-name span {
            color: var(--primary);
        }

        h1 {
            font-size: 30px;
            letter-spacing: -.7px;
            margin-bottom: 10px;
        }

        .subtitle {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.7;
            margin-bottom: 25px;
        }

        .alert {
            padding: 12px 14px;
            margin-bottom: 18px;
            border-radius: 10px;
            background: #fff0f0;
            border: 1px solid #ffd4d4;
            color: #b42318;
            font-size: 13px;
            line-height: 1.5;
        }

        .field {
            margin-bottom: 17px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 700;
        }

        input {
            width: 100%;
            padding: 13px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            outline: none;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(134, 85, 232, .12);
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap input {
            padding-right: 70px;
        }

        .toggle {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            border: none;
            background: none;
            color: var(--primary);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .role-note {
            padding: 11px 13px;
            margin-bottom: 20px;
            border-radius: 10px;
            background: #f5f0ff;
            color: var(--secondary);
            font-size: 12px;
            line-height: 1.6;
        }

        .btn {
            width: 100%;
            display: flex;
            justify-content: center;
            padding: 14px;
            border: 0;
            border-radius: 10px;
            background: var(--primary);
            color: white;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: .2s;
        }

        .btn:hover {
            background: var(--secondary);
        }

        .login-text {
            text-align: center;
            margin-top: 22px;
            color: var(--muted);
            font-size: 13px;
        }

        .login-text a {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
        }

        .copyright {
            text-align: center;
            color: #aaa5b2;
            font-size: 11px;
            margin-top: 28px;
        }

        .welcome-panel {
            padding: 48px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: white;
            background:
                radial-gradient(
                    circle at 85% 15%,
                    rgba(200,245,47,.22),
                    transparent 28%
                ),
                linear-gradient(145deg, #8655E8, #482875 65%, #241638);
        }

        .tag {
            align-self: flex-start;
            padding: 9px 13px;
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 30px;
            font-size: 11px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .content {
            margin: 45px 0;
        }

        .eyebrow {
            color: var(--lime);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 18px;
        }

        h2 {
            font-size: 43px;
            line-height: 1.15;
            letter-spacing: -1px;
            margin-bottom: 20px;
        }

        h2 span {
            color: var(--lime);
        }

        .description {
            color: rgba(255,255,255,.8);
            font-size: 14px;
            line-height: 1.9;
        }

        .benefit {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 16px;
            font-size: 13px;
        }

        .check {
            width: 32px;
            height: 32px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            background: rgba(255,255,255,.13);
            color: var(--lime);
        }

        @media (max-width: 760px) {
            body {
                padding: 14px;
            }

            .card {
                max-width: 460px;
                grid-template-columns: 1fr;
            }

            .form-panel {
                padding: 32px 25px;
            }

            .welcome-panel {
                padding: 32px 25px;
                min-height: 380px;
            }

            h2 {
                font-size: 35px;
            }
        }
    </style>
</head>

<body>
    <main class="card">
        <section class="form-panel">
            <div class="brand">
                <div class="logo">I</div>
                <div class="brand-name">Irzi<span>Mart</span></div>
            </div>

            <h1>Buat akun baru</h1>
            <p class="subtitle">
                Bergabung dengan IrziMart dan mulai akses sistem
                pengelolaan toko bangunan.
            </p>

            <?php if ($error !== ''): ?>
                <div class="alert" role="alert">
                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
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
                        maxlength="45"
                        value="<?= htmlspecialchars(
                            $username,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        placeholder="Buat username"
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
                            minlength="8"
                            placeholder="Minimal 8 karakter"
                            autocomplete="new-password"
                            required
                        >
                        <button
                            class="toggle"
                            type="button"
                            data-target="password"
                        >Lihat</button>
                    </div>
                </div>

                <div class="field">
                    <label for="confirm_password">
                        Konfirmasi Password
                    </label>
                    <div class="password-wrap">
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            minlength="8"
                            placeholder="Ulangi password"
                            autocomplete="new-password"
                            required
                        >
                        <button
                            class="toggle"
                            type="button"
                            data-target="confirm_password"
                        >Lihat</button>
                    </div>
                </div>

                <div class="role-note">
                    <strong>Role akun: Auditor</strong><br>
                    Akun baru hanya memiliki akses baca. Hak akses
                    lainnya diberikan oleh administrator.
                </div>

                <button class="btn" type="submit">
                    Daftar Sekarang
                </button>
            </form>

            <p class="login-text">
                Sudah memiliki akun?
                <a href="login.php">Masuk di sini</a>
            </p>

            <p class="copyright">
                &copy; <?= date('Y') ?> IrziMart.
                All rights reserved.
            </p>
        </section>

        <aside class="welcome-panel">
            <div class="tag">Toko Bangunan Terpercaya</div>

            <div class="content">
                <p class="eyebrow">BUILD YOUR BETTER FUTURE</p>

                <h2>
                    Bangun impianmu bersama
                    <span>IrziMart.</span>
                </h2>

                <p class="description">
                    IrziMart hadir untuk membantu kebutuhan material
                    konstruksi, renovasi rumah, dan berbagai proyek
                    pembangunan. Kelola informasi barang dan data
                    toko melalui sistem yang praktis dan terorganisasi.
                </p>
            </div>

            <div>
                <div class="benefit">
                    <div class="check">&#10003;</div>
                    <span>Informasi barang terorganisasi</span>
                </div>

                <div class="benefit">
                    <div class="check">&#10003;</div>
                    <span>Akses sistem sesuai hak pengguna</span>
                </div>

                <div class="benefit">
                    <div class="check">&#10003;</div>
                    <span>Pengelolaan data yang lebih praktis</span>
                </div>
            </div>
        </aside>
    </main>

    <script>
        document.querySelectorAll('.toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                const input = document.getElementById(
                    button.dataset.target
                );

                const hidden = input.type === 'password';

                input.type = hidden ? 'text' : 'password';
                button.textContent = hidden ? 'Sembunyikan' : 'Lihat';
            });
        });
    </script>
</body>
</html>
