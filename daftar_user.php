<?php

include_once("bootstrap.php");

try {

    $db = new DBconnection();

    $userModel = new UserModel($db);
    $pemilikModel = new PemilikModel($db);
    $dokterModel = new DokterModel($db);

    $data_user = $userModel->find_all();

    echo "<h2>Daftar Pengguna</h2>";

    foreach ($data_user as $row) {

        $iduser = (int) $row['iduser'];

        $pemilik = $pemilikModel->find_by_iduser($iduser);
        $dokter = $dokterModel->find_by_iduser($iduser);

        if ($pemilik !== null) {

            $pengguna = new Pemilik(
                $iduser,
                $row['nama'],
                $row['email'],
                $pemilik['no_wa'],
                $pemilik['alamat']
            );

        } elseif ($dokter !== null) {

            $pengguna = new Dokter(
                $iduser,
                $row['nama'],
                $row['email'],
                $dokter['no_izin'],
                $dokter['spesialisasi']
            );

        } else {

            $pengguna = new User(
                $iduser,
                $row['nama'],
                $row['email']
            );
        }

        echo "<hr>";

        echo "<strong>"
            . get_class($pengguna)
            . "</strong><br>";

        echo $pengguna . "<br>";

        echo "Role aktif: "
            . $pengguna->get_user()['role']
            . "<br>";

        if ($pengguna instanceof Pemilik) {

            echo "Status: Pemilik<br>";
            echo "No. WhatsApp: "
                . $pengguna->get_no_wa()
                . "<br>";
            echo "Alamat: "
                . $pengguna->get_alamat()
                . "<br>";

        } elseif ($pengguna instanceof Dokter) {

            echo "Status: Dokter<br>";
            echo "No. Izin: "
                . $pengguna->get_no_izin()
                . "<br>";
            echo "Spesialisasi: "
                . $pengguna->get_spesialisasi()
                . "<br>";

        } else {

            echo "Status: User biasa<br>";
        }
    }

    $db->close_connection();

} catch (DatabaseException $e) {

    echo "Kesalahan database: "
        . $e->getMessage();
}