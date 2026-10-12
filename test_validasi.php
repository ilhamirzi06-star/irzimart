<?php

include_once("bootstrap.php");

echo "<h2>Test Class Validasi</h2>";

$email = "ilham@gmail.com";
$password = "12345678";

echo "Email: " . $email . "<br>";

if (Validasi::email_valid($email)) {
    echo "Email valid<br>";
} else {
    echo "Email tidak valid<br>";
}

echo "Password: " . $password . "<br>";

if (Validasi::password_kuat($password)) {
    echo "Password kuat<br>";
} else {
    echo "Password terlalu pendek<br>";
}

$data = [
    'nama' => 'Ilham',
    'email' => 'ilham@gmail.com'
];

$wajib = [
    'nama',
    'email'
];

if (Validasi::tidak_kosong($data, $wajib)) {
    echo "Semua field wajib terisi<br>";
} else {
    echo "Ada field yang kosong<br>";
}