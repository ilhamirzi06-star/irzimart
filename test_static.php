<?php
include_once("bootstrap.php");

// Constant diakses tanpa membentuk object
echo "Aplikasi : " . Konfigurasi::APP_NAME . "<br>";
echo "Versi : " . Konfigurasi::VERSI . "<br>";
echo "File log : " . Konfigurasi::FILE_LOG . "<br><br>";

// Static method dipanggil langsung melalui nama class
Log::catat("UJI", ["keterangan" => "percobaan_static"]);
Log::catat("UJI", ["keterangan" => "percobaan_static_kedua"]);

// Static property menyimpan nilai selama script berjalan
echo "Jumlah baris log yang ditulis script ini: " . Log::jumlah_baris(); 
