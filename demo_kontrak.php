<?php

include_once("bootstrap.php");

try {

    $db = new DBconnection();

    $daftar_model = [
        new UserModel($db),
        new RoleModel($db),
        new PemilikModel($db)
    ];

    foreach ($daftar_model as $model) {

        $nama = get_class($model);

        echo "$nama : Crudable="
            . ($model instanceof Crudable ? "ya" : "tidak")
            . ", Autentikasi="
            . ($model instanceof Autentikasi ? "ya" : "tidak")
            . "<br>";
    }

    echo "<br>Model yang dapat melakukan autentikasi:<br>";

    foreach ($daftar_model as $model) {

        if ($model instanceof Autentikasi) {
            echo "- " . get_class($model) . "<br>";
        }
    }

    $db->close_connection();

} catch (DatabaseException $e) {

    echo "Kesalahan database: "
        . $e->getMessage();
}