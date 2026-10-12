<?php
include_once("bootstrap.php");

try {
 $daftar_role = Role::get_opsi_role(); // static, tanpa membentuk object Role
} catch (DatabaseException $e) {
 die("Kesalahan database: " . $e->getMessage()); 

 }
?>

<h3>Pilihan role dari database</h3>
<select name="idrole">
 <?php foreach ($daftar_role as $role) { $data = $role->get_data(); ?>
 <option value="<?= $data['idrole'] ?>"><?= $data['nama_role'] ?></option>
 <?php } ?>
</select> 