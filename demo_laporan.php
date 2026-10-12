<?php

include_once("bootstrap.php");
include_once("model/laporan.php");

try {

    $db = new DBconnection();

    $daftar_laporan = [
        new LaporanUser($db),
        new LaporanPemilik($db)
    ];

    foreach ($daftar_laporan as $laporan) {

        $laporan->isi();
    }

    $db->close_connection();

} catch (DatabaseException $e) {

    echo "Kesalahan database: "
        . $e->getMessage();
}