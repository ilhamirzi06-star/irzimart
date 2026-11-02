<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Modul IrziMart</title>

    <!-- Bootstrap 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background: #f4effa;
            color: #171321;
            font-family: Arial, sans-serif;
        }

        .navbar-irzi {
            background: #482875;
        }

        .navbar-irzi .navbar-brand {
            color: white;
            font-weight: 800;
        }

        .content-wrapper {
            padding: 30px;
        }

        .card {
            border-radius: 14px;
        }

        .btn-primary {
            background: #8655E8;
            border-color: #8655E8;
        }

        .btn-primary:hover {
            background: #482875;
            border-color: #482875;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-irzi navbar-dark shadow-sm">
    <div class="container-fluid px-4">

        <a href="../../index.php" class="navbar-brand">
            IRZIMART
        </a>

        <div class="d-flex align-items-center gap-3 text-white">
            <span>
                <?= htmlspecialchars(
                    $_SESSION['user']['username'] ?? 'Pengguna',
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>

            <a href="../../index.php" class="btn btn-sm btn-light">
                <i class="bi bi-house"></i> Dashboard
            </a>

            <a href="../../logout.php" class="btn btn-sm btn-outline-light">
                Keluar
            </a>
        </div>

    </div>
</nav>

<main class="content-wrapper">
