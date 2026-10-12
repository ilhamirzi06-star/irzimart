<?php

include_once("bootstrap.php");

$wajib = [
    'nama',
    'email',
    'password',
    'retype_password',
    'idrole'
];

if (!Validasi::tidak_kosong($_POST, $wajib)) {
    Flash::set("Semua field wajib diisi.");
    header("Location: registrasi.php");
    exit();
}

$email = strtolower(trim($_POST['email']));
$password = $_POST['password'];
$retype_password = $_POST['retype_password'];

if (!Validasi::email_valid($email)) {
    Flash::set("Format email tidak valid.");
    header("Location: registrasi.php");
    exit();
}

if (!Validasi::password_kuat($password)) {
    Flash::set("Password minimal harus 8 karakter.");
    header("Location: registrasi.php");
    exit();
}

if ($password !== $retype_password) {
    Flash::set("Password dan Retype Password tidak cocok.");
    header("Location: registrasi.php");
    exit();
}

try {

    $db = new DBconnection();

    // Cek duplikasi email
    $cek = $db->send_query(
        'SELECT iduser FROM "user" WHERE email = $1',
        [$email]
    );

    if (count($cek->data) > 0) {
        Flash::set("Email tersebut sudah terdaftar.");
        $db->close_connection();

        header("Location: registrasi.php");
        exit();
    }

    
    $db->mulai_transaksi();

    $hash = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    $responUser = $db->send_query(
        'INSERT INTO "user" (nama, email, password)
         VALUES ($1, $2, $3)
         RETURNING iduser',
        [
            $_POST['nama'],
            $email,
            $hash
        ]
    );

    if ($responUser->status && !empty($responUser->data)) {

        $iduser = (int) $responUser->data[0]['iduser'];

        $responRole = $db->send_query(
            'INSERT INTO user_role (iduser, idrole, status)
             VALUES ($1, $2, TRUE)',
            [
                $iduser,
                (int) $_POST['idrole']
            ]
        );

        if ($responRole->status) {

            $db->commit();

            Log::catat(
                "REGISTRASI",
                [
                    "email" => $email,
                    "iduser" => $iduser
                ]
            );

            Flash::set(
                "Registrasi berhasil. ID pengguna: " . $iduser
            );

        } else {

            $db->rollback();

            Flash::set(
                "Gagal menyimpan role pengguna."
            );
        }

    } else {

        $db->rollback();

        Flash::set(
            "Registrasi gagal: " . $responUser->message
        );
    }

    $db->close_connection();

} catch (DatabaseException $e) {

    if (isset($db)) {
        $db->rollback();
    }

    Flash::set(
        "Kesalahan database: " . $e->getMessage()
    );
}

header("Location: registrasi.php");
exit();