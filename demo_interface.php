<?php

include_once("demo/petelur.php");

$hewan = [
    new KucingTernak("Kitty"),
    new Ayam("Jago"),
];

echo "Siapakah yang bisa bertelur?<br>";

foreach ($hewan as $h) {

    if ($h instanceof Petelur) {
        $h->bertelur();
    }
}