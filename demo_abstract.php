<?php

include_once("demo/bidang.php");

$bidang = [
    new PersegiPanjang("PersegiPanjang1", 5, 10),
    new Lingkaran("Lingkaran1", 7),
];

foreach ($bidang as $b) {

    if ($b instanceof Bidang) {

        $b->luas();
        $b->keliling();

    } else {

        echo "Object bukan instance dari class Bidang<br>";
    }
}