<?php

$koneksi = mysqli_connect("localhost", "root", "", "db_manajemen_irzimart");

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>