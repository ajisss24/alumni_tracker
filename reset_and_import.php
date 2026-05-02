<?php
require_once 'db.php';

// Set time limit to infinity for large import
set_time_limit(0);
ini_set('memory_limit', '512M');

echo "Memulai pembersihan database...\n";

try {
    // Matikan foreign key check untuk mempermudah truncate
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE jejak_bukti");
    $pdo->exec("TRUNCATE TABLE riwayat_perubahan");
    $pdo->exec("TRUNCATE TABLE alumni");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Database telah dikosongkan.\n";
} catch (Exception $e) {
    die("Gagal mengosongkan database: " . $e->getMessage());
}

$csvFile = 'alumni.csv';
$handle = fopen($csvFile, 'r');

if ($handle === false) {
    die("Gagal membuka file CSV.");
}

// Skip header
$header = fgetcsv($handle);

$batchSize = 5000;
$count = 0;
$totalImported = 0;

$pdo->beginTransaction();

$sql = "INSERT INTO alumni (nama_lengkap, nim, tahun_lulus, prodi, status_pelacakan) VALUES (?, ?, ?, ?, 'Belum Dilacak')";
$stmt = $pdo->prepare($sql);

echo "Memulai impor dari $csvFile...\n";

while (($data = fgetcsv($handle)) !== false) {
    if (count($data) < 6) continue;

    $nama = $data[0];
    $nim = $data[1];
    $tanggalLulus = $data[3];
    $prodi = $data[5];

    // Extract year from "1 Juli 2000" or similar
    $year = 0;
    if (preg_match('/\b(19|20)\d{2}\b/', $tanggalLulus, $matches)) {
        $year = $matches[0];
    }

    try {
        $stmt->execute([$nama, $nim, $year, $prodi]);
        $count++;
        $totalImported++;
    } catch (Exception $e) {
        echo "Error at line $totalImported: " . $e->getMessage() . "\n";
    }

    if ($count >= $batchSize) {
        $pdo->commit();
        echo "Telah mengimpor $totalImported data...\n";
        $pdo->beginTransaction();
        $count = 0;
    }
}

$pdo->commit();
fclose($handle);

echo "Selesai! Total $totalImported data alumni (tepat sesuai CSV) telah dimasukkan ke database.\n";
?>
