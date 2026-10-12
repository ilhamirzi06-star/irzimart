<?php
class Log {
 private static int $jumlah_baris = 0;

 public static function catat(string $jenis, array $data): void {
 $baris = date('Y-m-d H:i:s') . " " . $jenis;
 foreach ($data as $key => $value) {
 $baris .= " " . $key . "=" . $value;
 }
 file_put_contents(Konfigurasi::FILE_LOG, $baris . PHP_EOL,
FILE_APPEND);
 self::$jumlah_baris++;
 }

 public static function jumlah_baris(): int {
 return self::$jumlah_baris;
 }
} 
