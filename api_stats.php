<?php
require_once 'db.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Top 8 Fakultas
$fak = $pdo->query("SELECT fakultas AS label, COUNT(*) AS count FROM alumni WHERE fakultas IS NOT NULL AND fakultas != '' GROUP BY fakultas ORDER BY count DESC LIMIT 8")->fetchAll();

// Trend lulusan per tahun masuk (last 15 years)
$trend = $pdo->query("SELECT tahun_masuk AS year, COUNT(*) AS count FROM alumni WHERE tahun_masuk IS NOT NULL GROUP BY tahun_masuk ORDER BY tahun_masuk DESC LIMIT 15")->fetchAll();
$trend = array_reverse($trend);

echo json_encode([
    'fakultas' => $fak,
    'trend' => $trend
]);
