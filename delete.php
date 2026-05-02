<?php
require_once 'db.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { header('Location: login.php'); exit; }
if (!isset($_GET['id'])) { header('Location: index.php'); exit; }

$id = (int)$_GET['id'];
// Delete related records first (cascade should handle but be safe)
$pdo->prepare("DELETE FROM jejak_bukti WHERE alumni_id = ?")->execute([$id]);
$pdo->prepare("DELETE FROM riwayat_perubahan WHERE alumni_id = ?")->execute([$id]);
$pdo->prepare("DELETE FROM alumni WHERE id = ?")->execute([$id]);

header('Location: index.php?deleted=1');
exit;
