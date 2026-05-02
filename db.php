<?php
session_start();

// Auto-detect environment: hosting (AeonFree) vs local
if ($_SERVER['HTTP_HOST'] !== 'localhost' && strpos($_SERVER['HTTP_HOST'] ?? '', 'hstn.me') !== false) {
    // === AeonFree Hosting ===
    $host = 'sql208.hstn.me';
    $dbname = 'mseet_41812072_alumni_trackerumm';
    $username = 'mseet_41812072';
    $password = 'ajissss18';
} else {
    // === Local Development ===
    $host = 'localhost';
    $dbname = 'alumni_tracker';
    $username = 'root';
    $password = '';
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}
?>
