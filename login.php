<?php

include_once("bootstrap.php");

Flash::tampilkan();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Halaman Login</title>
</head>
<body>

<h1>Halaman Login</h1>

<form action="login_post.php" method="POST">

    <label>Email:</label>
    <input type="email" name="username" required>

    <br><br>

    <label>Password:</label>
    <input type="password" name="password" required>

    <br><br>

    <input type="submit" value="Login">

</form>

<br>

<a href="registrasi.php">Belum punya akun? Registrasi</a>

</body>
</html>