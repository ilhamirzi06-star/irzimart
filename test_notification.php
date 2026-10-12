<?php

include_once("bootstrap.php");

echo "<h2>Test Notification - DELETE</h2>";

try {

    $id = 29;

    // =========================
    // CARI DATA
    // =========================

    echo "<h3>1. Cari Notification</h3>";

    $notification = Notification::find($id);

    if ($notification === null) {

        echo "Notification tidak ditemukan.";
        exit;
    }

    echo "ID: " . $notification->getId() . "<br>";
    echo "Judul: " . $notification->getTitle() . "<br>";
    echo "Status: " . ($notification->isRead() ? "READ" : "UNREAD") . "<br><br>";


    // =========================
    // DELETE
    // =========================

    echo "<h3>2. Test delete()</h3>";

    $notification->delete();

    echo "DELETE berhasil!<br><br>";


    // =========================
    // VERIFIKASI
    // =========================

    echo "<h3>3. Verifikasi</h3>";

    $hasil = Notification::find($id);

    if ($hasil === null) {

        echo "<strong>Notification sudah tidak ditemukan di database.</strong>";

    } else {

        echo "Notification masih ditemukan.";
    }


} catch (DatabaseException $e) {

    echo "<strong>Error Database:</strong> ";
    echo $e->getMessage();

} catch (Exception $e) {

    echo "<strong>Error:</strong> ";
    echo $e->getMessage();
}