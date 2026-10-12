<?php

include "../config/database.php";

$id = $_GET['id'];

mysqli_query(
    $koneksi,
    "DELETE FROM barang WHERE idbarang = '$id'"
);

header("Location: index.php");

exit;

?>