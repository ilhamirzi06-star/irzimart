<?php
include_once("bootstrap.php");

$email = strtolower(trim($_POST['username'])); 
$password = $_POST['password'];
$ip = $_SERVER['REMOTE_ADDR'] ?? '-';

try {
 $db = new DBconnection();
 $respon = $db->send_query('SELECT * FROM "user" WHERE email = $1', [$email]);

 $row = $respon->data[0] ?? null;

 if ($row === null || !password_verify($password, $row['password'])) {
 $db->close_connection();
 Log::catat("LOGIN", ["email" => $email, "status" => "GAGAL", "ip" => $ip]);
 Flash::set("Email atau password tidak sesuai.");
 header("Location: login.php");
 exit();
 }

 // membentuk object User beserta role-nya
 $user = new User((int) $row['iduser'], $row['nama'], $row['email']);
 $daftar = $db->send_query(
 'SELECT r.idrole, r.nama_role, ur.status
 FROM user_role ur JOIN role r ON r.idrole = ur.idrole
 WHERE ur.iduser = $1',
 [$row['iduser']]
 );

 foreach ($daftar->data as $baris) {
 $user->set_role(new Role((int) $baris['idrole'], $baris['nama_role'],
$baris['status'] === 't'));
 }
 $db->close_connection();

 if (session_status() === PHP_SESSION_NONE) {
 session_start();
 }
 $_SESSION['user'] = $user->get_user();

 Log::catat("LOGIN", ["email" => $email, "status" => "SUKSES", "ip" => $ip]);
 header("Location: dashboard.php");
 exit();

} catch (DatabaseException $e) {
 Flash::set("Kesalahan database: " . $e->getMessage());
 header("Location: login.php");
 exit();
} 