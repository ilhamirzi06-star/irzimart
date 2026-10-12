<?php

include_once("bootstrap.php");

echo "<h2>Test Method User</h2>";

$user = new User(
    1,
    "Ilham Test",
    "ilham@test.com"
);

$admin = new Role(1, "Admin", true);
$dokter = new Role(2, "Dokter", false);
$paramedis = new Role(3, "Paramedis", false);

$user->set_role($admin);
$user->set_role($dokter);
$user->set_role($paramedis);

echo "<h3>Role aktif awal:</h3>";

print_r($user->get_user());

echo "<br><br>";

echo "<h3>Set role aktif menjadi Dokter:</h3>";

$user->set_role_aktif(2);

print_r($user->get_user());

echo "<br><br>";

echo "<h3>Hapus role Dokter:</h3>";

$user->hapus_role(2);

print_r($user->get_user());