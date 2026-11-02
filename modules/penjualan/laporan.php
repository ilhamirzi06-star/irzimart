<?php
require_once '../../config/Database.php';

$db = new Database();
$conn = $db->connect();

$tanggalAwal = '2026-11-01';
$tanggalAkhir = '2026-11-30';

$stmt = $conn->prepare(
    "CALL sp_laporan_penjualan(?, ?)"
);

$stmt->bind_param(
    "ss",
    $tanggalAwal,
    $tanggalAkhir
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo $row['idpenjualan'] . ' - ';
    echo $row['username'] . ' - ';
    echo 'Rp ' . number_format($row['total_nilai'], 0, ',', '.');
    echo '<br>';
}

$stmt->close();
$conn->close();