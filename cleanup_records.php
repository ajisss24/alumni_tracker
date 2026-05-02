<?php
require_once 'db.php';

// Set time limit for large operation
set_time_limit(0);
ini_set('memory_limit', '1024M');

echo "Memulai proses pembersihan data...\n";

// 1. Buat tabel temporary
$pdo->exec("CREATE TEMPORARY TABLE temp_nims (nim VARCHAR(50) PRIMARY KEY)");

// 2. Baca CSV dan masukkan NIM ke tabel temporary
$csvFile = 'alumni.csv';
$handle = fopen($csvFile, 'r');
if ($handle === false) {
    die("Gagal membuka file CSV.");
}

// Skip header
fgetcsv($handle);

$count = 0;
$batchSize = 5000;
$pdo->beginTransaction();
$stmt = $pdo->prepare("INSERT IGNORE INTO temp_nims (nim) VALUES (?)");

while (($data = fgetcsv($handle)) !== false) {
    if (isset($data[1])) {
        $stmt->execute([$data[1]]);
        $count++;
    }
    
    if ($count % $batchSize == 0) {
        $pdo->commit();
        echo "Telah memproses $count NIM dari CSV...\n";
        $pdo->beginTransaction();
    }
}
$pdo->commit();
fclose($handle);

echo "Selesai membaca CSV. Total NIM unik: $count\n";

// 3. Hapus data di tabel alumni yang tidak ada di temp_nims
echo "Menghapus records yang tidak ada di CSV...\n";
$deleted = $pdo->exec("DELETE FROM alumni WHERE nim NOT IN (SELECT nim FROM temp_nims)");
echo "Berhasil menghapus $deleted records.\n";

// 4. Verifikasi jumlah akhir
$finalCount = $pdo->query("SELECT COUNT(*) FROM alumni")->fetchColumn();
echo "Jumlah data alumni sekarang: $finalCount\n";

if ($finalCount > 142292) {
    echo "Peringatan: Jumlah data masih di atas 142.292. Mungkin ada duplikat NIM di CSV?\n";
}

echo "Proses pembersihan selesai!\n";
?>
