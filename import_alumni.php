<?php
require_once 'db.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { header('Location: login.php'); exit; }

$message = '';
$msgType = '';
$count = 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'import_csv') {
        $csvFile = __DIR__ . '/alumni.csv';
        if (!file_exists($csvFile)) {
            $message = 'File alumni.csv tidak ditemukan di folder project!';
            $msgType = 'error';
        } else {
            set_time_limit(600);
            $handle = fopen($csvFile, 'r');
            $header = fgetcsv($handle); // skip header

            $pdo->beginTransaction();
            $sql = "INSERT INTO alumni (nama_lengkap, nim, tahun_masuk, tanggal_lulus, fakultas, prodi, status_pelacakan, waktu_sekarang)
                    VALUES (?, ?, ?, ?, ?, ?, 'Belum Dilacak', CURDATE())";
            $stmt = $pdo->prepare($sql);

            $batch = 0;
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 6) continue;
                $nama = trim($row[0]);
                if (empty($nama)) continue;
                $nim = trim($row[1]);
                $tahun_masuk = is_numeric(trim($row[2])) ? (int)trim($row[2]) : null;
                $tgl_lulus = trim($row[3]);
                $fakultas = trim($row[4]);
                $prodi = trim($row[5]);

                try {
                    $stmt->execute([$nama, $nim, $tahun_masuk, $tgl_lulus, $fakultas, $prodi]);
                    $count++;
                } catch (Exception $e) {
                    // skip duplicate or error
                }
                $batch++;
                if ($batch % 5000 === 0) {
                    $pdo->commit();
                    $pdo->beginTransaction();
                }
            }
            $pdo->commit();
            fclose($handle);
            $message = "Berhasil mengimpor $count data alumni dari CSV!";
            $msgType = 'success';
        }
    } elseif ($_POST['action'] === 'reset_db') {
        $pdo->exec("DELETE FROM riwayat_perubahan");
        $pdo->exec("DELETE FROM jejak_bukti");
        $pdo->exec("DELETE FROM alumni");
        $pdo->exec("ALTER TABLE alumni AUTO_INCREMENT = 1");
        $message = "Semua data alumni berhasil dihapus. Database siap untuk import baru.";
        $msgType = 'success';
    }
}

$currentCount = $pdo->query("SELECT COUNT(*) FROM alumni")->fetchColumn();
$csvExists = file_exists(__DIR__ . '/alumni.csv');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Data | Alumni Tracker Pro</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="app-wrapper">
    <aside class="sidebar">
        <div class="logo"><i class="fas fa-graduation-cap"></i><span>Tracker Pro</span></div>
        <nav class="nav-menu">
            <div class="nav-section-title">Menu Utama</div>
            <div class="nav-item"><a href="index.php" class="nav-link"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a></div>
            <div class="nav-item"><a href="add.php" class="nav-link"><i class="fas fa-user-plus"></i><span>Tambah Alumni</span></a></div>
            <div class="nav-section-title">Tools</div>
            <div class="nav-item"><a href="import_alumni.php" class="nav-link active"><i class="fas fa-file-import"></i><span>Import CSV</span></a></div>
            <div class="nav-item"><a href="export.php" class="nav-link"><i class="fas fa-file-export"></i><span>Export Data</span></a></div>
        </nav>
        <div class="sidebar-footer"><div class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-right-from-bracket"></i><span>Keluar</span></a></div></div>
    </aside>

    <main class="main-content">
        <header class="animate-up">
            <div class="page-title">
                <h1>Import Data Alumni</h1>
                <p>Impor data alumni dari file CSV ke database.</p>
            </div>
            <a href="index.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
        </header>

        <?php if($message): ?>
        <div class="alert alert-<?= $msgType ?> animate-up">
            <i class="fas fa-<?= $msgType=='success'?'check-circle':'exclamation-circle' ?>"></i> <?= $message ?>
        </div>
        <?php endif; ?>

        <div class="stats-grid animate-up delay-1">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(99,102,241,0.12);color:var(--primary);"><i class="fas fa-database"></i></div>
                <div class="stat-info"><h3>Data di Database</h3><div class="value"><?= number_format($currentCount, 0, ',', '.') ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,0.12);color:#34d399;"><i class="fas fa-file-csv"></i></div>
                <div class="stat-info"><h3>File CSV</h3><div class="value"><?= $csvExists ? '✓ Tersedia' : '✗ Tidak ada' ?></div></div>
            </div>
        </div>

        <div class="data-card animate-up delay-2">
            <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:1rem;"><i class="fas fa-file-import" style="color:var(--primary);"></i> Import dari alumni.csv</h2>
            <p class="text-sm text-muted" style="margin-bottom:1rem;">
                Pastikan file <code>alumni.csv</code> berada di folder project. Format kolom: Nama Lulusan, NIM, Tahun Masuk, Tanggal Lulus, Fakultas, Program Studi.
            </p>

            <div class="disclaimer" style="margin-bottom:1.5rem;">
                <i class="fas fa-info-circle"></i>
                <span>Proses import ~142.000 data mungkin memerlukan waktu beberapa menit. Jangan tutup browser selama proses berlangsung.</span>
            </div>

            <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="import_csv">
                    <button type="submit" class="btn btn-primary" <?= !$csvExists ? 'disabled' : '' ?> onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin\'></i> Mengimpor...';this.disabled=true;this.form.submit();">
                        <i class="fas fa-upload"></i> Import Data CSV
                    </button>
                </form>

                <form method="POST" style="display:inline;" onsubmit="return confirm('PERINGATAN: Semua data alumni akan DIHAPUS! Lanjutkan?')">
                    <input type="hidden" name="action" value="reset_db">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Reset Database</button>
                </form>
            </div>
        </div>

        <div class="privacy-footer"><i class="fas fa-shield-halved"></i> Data dilindungi — Hanya untuk kepentingan pembelajaran. &copy; 2026 Alumni Tracker Pro</div>
    </main>
</div>
</body>
</html>
