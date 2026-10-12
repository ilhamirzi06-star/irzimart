<?php
include "../config/database.php";

$query = mysqli_query($koneksi, "
    SELECT 
        b.idbarang,
        b.jenis,
        b.nama,
        s.nama_satuan,
        b.status,
        b.harga
    FROM barang b
    JOIN satuan s ON b.idsatuan = s.idsatuan
    ORDER BY b.idbarang
");
?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>IrziMart - Data Barang</title>

    <link rel="stylesheet" href="../assets/css/style.css">

</head>

<body>

<!-- SIDEBAR -->

<aside class="sidebar">

    <div class="logo">
        Irzi<span>Mart</span>
    </div>

    <div class="menu-title">
        Menu Utama
    </div>

    <ul class="menu">

        <li>
            <a href="#">
                Dashboard
            </a>
        </li>

    </ul>

    <div class="menu-title">
        Master Data
    </div>

    <ul class="menu">

        <li>
            <a href="index.php" class="active">
                Barang
            </a>
        </li>

        <li>
            <a href="#">
                Vendor
            </a>
        </li>

        <li>
            <a href="#">
                Satuan
            </a>
        </li>

        <li>
            <a href="#">
                User
            </a>
        </li>

    </ul>

    <div class="menu-title">
        Transaksi
    </div>

    <ul class="menu">

        <li>
            <a href="#">
                Pengadaan
            </a>
        </li>

        <li>
            <a href="#">
                Penjualan
            </a>
        </li>

    </ul>

    <div class="logout">

        <ul class="menu">

            <li>
                <a href="#">
                    Logout
                </a>
            </li>

        </ul>

    </div>

</aside>


<!-- MAIN -->

<main class="main">


    <!-- NAVBAR -->

    <header class="navbar">

        <div class="navbar-title">
            Manajemen Barang
        </div>

        <div class="profile">

            <span>Administrator</span>

            <div class="profile-circle">
                A
            </div>

        </div>

    </header>


    <!-- CONTENT -->

    <section class="content">


        <!-- HEADER -->

        <div class="page-header">

            <div>

                <h1>
                    Data Barang
                </h1>

                <p>
                    Kelola data material bangunan IrziMart
                </p>

            </div>

            <a href="tambah.php"
               class="btn btn-primary">

                + Tambah Barang

            </a>

        </div>


        <!-- TABLE CARD -->

        <div class="card">

            <div class="table-wrapper">

                <table>

                    <thead>

                        <tr>

                            <th>
                                No
                            </th>

                            <th>
                                Jenis
                            </th>

                            <th>
                                Nama Barang
                            </th>

                            <th>
                                Satuan
                            </th>

                            <th>
                                Harga
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    $no = 1;

                    while ($data = mysqli_fetch_assoc($query)):

                    ?>

                        <tr>

                            <td>
                                <?= $no++; ?>
                            </td>

                            <td>
                                <?= $data['jenis']; ?>
                            </td>

                            <td>
                                <?= $data['nama']; ?>
                            </td>

                            <td>
                                <?= $data['nama_satuan']; ?>
                            </td>

                            <td>
                                Rp <?= number_format(
                                    $data['harga'],
                                    0,
                                    ',',
                                    '.'
                                ); ?>
                            </td>

                            <td>

                                <?php if ($data['status'] == 1): ?>

                                    <span class="badge badge-success">
                                        Aktif
                                    </span>

                                <?php else: ?>

                                    <span class="badge badge-danger">
                                        Nonaktif
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <div class="action">

                                    <a
                                        href="edit.php?id=<?= $data['idbarang']; ?>"
                                        class="btn btn-warning">

                                        Edit

                                    </a>

                                    <a
                                        href="hapus.php?id=<?= $data['idbarang']; ?>"
                                        class="btn btn-danger"
                                        onclick="return confirm('Yakin ingin menghapus barang ini?')">

                                        Hapus

                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </section>

</main>

</body>

</html>