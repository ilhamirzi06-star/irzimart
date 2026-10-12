<?php
include_once("bootstrap.php");

Flash::tampilkan();

$daftar_role = Role::get_opsi_role();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Halaman Registrasi</title>
</head>
<body>

<h1>Halaman Registrasi</h1>

<form action="proses_registrasi.php" method="POST">

    <label>Nama:</label>
    <input type="text" name="nama" required>
    <br><br>

    <label>Email:</label>
    <input type="email" name="email" required>
    <br><br>

    <label>Password:</label>
    <input type="password" name="password" required>
    <br><br>

    <label>Retype Password:</label>
    <input type="password" name="retype_password" required>
    <br><br>

    <label>Role:</label>
    <select name="idrole" required>
        <?php foreach ($daftar_role as $role) {
            $data = $role->get_data();
        ?>
            <option value="<?= $data['idrole'] ?>">
                <?= $data['nama_role'] ?>
            </option>
        <?php } ?>
    </select>

    <br><br>

    <input type="submit" value="Daftar">

</form>

</body>
</html>