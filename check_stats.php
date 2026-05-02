<?php
require_once 'db.php';
$t = $pdo->query("SELECT COUNT(*) FROM alumni")->fetchColumn();
$f = $pdo->query("SELECT COUNT(*) FROM alumni WHERE linkedin IS NOT NULL OR email IS NOT NULL OR tempat_bekerja IS NOT NULL")->fetchColumn();
$v = $pdo->query("SELECT COUNT(DISTINCT alumni_id) FROM jejak_bukti")->fetchColumn();
echo "Total: $t\n";
echo "Filled: $f\n";
echo "Coverage: " . round($f/$t*100,1) . "%\n";
echo "Verified: $v\n";
echo "Accuracy: " . round($v/$f*100,1) . "%\n\n";

echo "--- Status Pekerjaan ---\n";
$rows = $pdo->query("SELECT status_pekerjaan, COUNT(*) as c FROM alumni WHERE status_pekerjaan IS NOT NULL GROUP BY status_pekerjaan")->fetchAll();
foreach ($rows as $r) echo $r['status_pekerjaan'] . ": " . number_format($r['c']) . "\n";

echo "\n--- Status Pelacakan ---\n";
$rows = $pdo->query("SELECT status_pelacakan, COUNT(*) as c FROM alumni GROUP BY status_pelacakan")->fetchAll();
foreach ($rows as $r) echo $r['status_pelacakan'] . ": " . number_format($r['c']) . "\n";

echo "\n--- Field Coverage ---\n";
$fields = ['linkedin','instagram','facebook','tiktok','email','no_hp','tempat_bekerja','alamat_bekerja','posisi','status_pekerjaan','work_social_media'];
foreach ($fields as $f) {
    $c = $pdo->query("SELECT COUNT(*) FROM alumni WHERE $f IS NOT NULL")->fetchColumn();
    echo "$f: " . number_format($c) . " (" . round($c/$t*100,1) . "%)\n";
}
