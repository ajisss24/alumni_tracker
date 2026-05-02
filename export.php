<?php
require_once 'db.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { header('Location: login.php'); exit; }

$search = $_GET['q'] ?? '';
$fakultas_f = $_GET['fakultas'] ?? '';
$status_kerja = $_GET['status_kerja'] ?? '';
$status_lacak = $_GET['status_lacak'] ?? '';

$where = " WHERE 1=1 ";
$params = [];
if ($search) { $where .= " AND (nama_lengkap LIKE ? OR nim LIKE ?) "; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($fakultas_f) { $where .= " AND fakultas = ? "; $params[] = $fakultas_f; }
if ($status_kerja) { $where .= " AND status_pekerjaan = ? "; $params[] = $status_kerja; }
if ($status_lacak) { $where .= " AND status_pelacakan = ? "; $params[] = $status_lacak; }

$stmt = $pdo->prepare("SELECT * FROM alumni $where ORDER BY id ASC");
$stmt->execute($params);
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="alumni_export_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM

fputcsv($out, ['No','NIM','Nama Lengkap','Fakultas','Prodi','Tahun Masuk','Tanggal Lulus',
    'Email','No HP','LinkedIn','Instagram','Facebook','TikTok',
    'Tempat Bekerja','Alamat Bekerja','Posisi','Status Pekerjaan','Sosmed Tempat Kerja',
    'Status Pelacakan']);

$no = 0;
foreach ($rows as $r) {
    $no++;
    fputcsv($out, [
        $no, $r['nim'], $r['nama_lengkap'], $r['fakultas'], $r['prodi'],
        $r['tahun_masuk'], $r['tanggal_lulus'], $r['email'], $r['no_hp'],
        $r['linkedin'], $r['instagram'], $r['facebook'], $r['tiktok'],
        $r['tempat_bekerja'], $r['alamat_bekerja'], $r['posisi'],
        $r['status_pekerjaan'], $r['work_social_media'], $r['status_pelacakan']
    ]);
}
fclose($out);
exit;
