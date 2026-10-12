<?php
    include_once("fungsi_lib.php");
    mulai_session();

    if (isset($_SESSION['user'])) {
        catat_log("LOGOUT", ["email" => $_SESSION['user']['email']]);
    }

    $_SESSION = array();            // mengosongkan seluruh data session
    session_regenerate_id(true);    // mengganti session id dan menghapus data lama

    set_flash("Anda telah logout.");
    header("Location: login.php");
    exit();
?>