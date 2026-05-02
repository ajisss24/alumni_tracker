<?php
/**
 * Populate Data Alumni - Real Data Search & Import
 * Uses search results from batch_search.py and verified web searches
 */
require_once 'db.php';
if (!isset($_SESSION['admin_logged_in'])) { header('Location: login.php'); exit; }

set_time_limit(900);
ini_set('memory_limit', '512M');

$message = '';
$msgType = '';

// Handle re-import action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'reimport') {
        // Re-run import from search results
        $jsonFile = 'search_results_0.json';
        if (file_exists($jsonFile)) {
            $json = file_get_contents($jsonFile);
            $results = json_decode($json, true);
            
            if ($results && count($results) > 0) {
                $message = "Data search results tersedia: " . number_format(count($results)) . " alumni. Gunakan command line: php import_search_results.php untuk re-import.";
                $msgType = 'info';
            } else {
                $message = "File search results kosong. Jalankan: python batch_search.py";
                $msgType = 'warning';
            }
        } else {
            $message = "File search results tidak ditemukan. Jalankan: python batch_search.py";
            $msgType = 'warning';
        }
    }
}

// Stats
$total = $pdo->query("SELECT COUNT(*) FROM alumni")->fetchColumn();
$filled = $pdo->query("SELECT COUNT(*) FROM alumni WHERE linkedin IS NOT NULL OR instagram IS NOT NULL OR email IS NOT NULL OR tempat_bekerja IS NOT NULL")->fetchColumn();
$coverage = $total > 0 ? round(($filled / $total) * 100, 1) : 0;
$verified = $pdo->query("SELECT COUNT(DISTINCT alumni_id) FROM jejak_bukti")->fetchColumn();
$accuracy = $filled > 0 ? round(($verified / $filled) * 100, 1) : 0;
$remaining = $total - $filled;
$selesai = $pdo->query("SELECT COUNT(*) FROM alumni WHERE status_pelacakan='Selesai'")->fetchColumn();
$teridentifikasi = $pdo->query("SELECT COUNT(*) FROM alumni WHERE status_pelacakan='Teridentifikasi'")->fetchColumn();
$belum = $pdo->query("SELECT COUNT(*) FROM alumni WHERE status_pelacakan='Belum Dilacak'")->fetchColumn();

// Field coverage
$f1 = $pdo->query("SELECT COUNT(*) FROM alumni WHERE linkedin IS NOT NULL")->fetchColumn();
$f2 = $pdo->query("SELECT COUNT(*) FROM alumni WHERE instagram IS NOT NULL")->fetchColumn();
$f3 = $pdo->query("SELECT COUNT(*) FROM alumni WHERE facebook IS NOT NULL")->fetchColumn();
$f4 = $pdo->query("SELECT COUNT(*) FROM alumni WHERE tiktok IS NOT NULL")->fetchColumn();
$f5 = $pdo->query("SELECT COUNT(*) FROM alumni WHERE email IS NOT NULL")->fetchColumn();
$f6 = $pdo->query("SELECT COUNT(*) FROM alumni WHERE no_hp IS NOT NULL")->fetchColumn();
$f7 = $pdo->query("SELECT COUNT(*) FROM alumni WHERE tempat_bekerja IS NOT NULL")->fetchColumn();
$f8 = $pdo->query("SELECT COUNT(*) FROM alumni WHERE alamat_bekerja IS NOT NULL")->fetchColumn();
$f9 = $pdo->query("SELECT COUNT(*) FROM alumni WHERE posisi IS NOT NULL")->fetchColumn();
$f10 = $pdo->query("SELECT COUNT(*) FROM alumni WHERE status_pekerjaan IS NOT NULL")->fetchColumn();
$f11 = $pdo->query("SELECT COUNT(*) FROM alumni WHERE work_social_media IS NOT NULL")->fetchColumn();

// Verified alumni sample
$verifiedSample = $pdo->query("SELECT a.nama_lengkap, a.nim, a.tempat_bekerja, a.posisi, a.status_pekerjaan, j.sumber_temuan, j.confidence_score, j.ringkasan_info
    FROM alumni a JOIN jejak_bukti j ON a.id = j.alumni_id 
    WHERE a.status_pelacakan = 'Selesai'
    ORDER BY j.confidence_score DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Populate Data | Alumni Tracker Pro</title>
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
            <div class="nav-item"><a href="import_alumni.php" class="nav-link"><i class="fas fa-file-import"></i><span>Import CSV</span></a></div>
            <div class="nav-item"><a href="populate_data.php" class="nav-link active"><i class="fas fa-robot"></i><span>Populate Data</span></a></div>
            <div class="nav-item"><a href="export.php" class="nav-link"><i class="fas fa-file-export"></i><span>Export Data</span></a></div>
        </nav>
        <div class="sidebar-footer"><div class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-right-from-bracket"></i><span>Keluar</span></a></div></div>
    </aside>

    <main class="main-content">
        <header class="animate-up">
            <div class="page-title">
                <h1>Real Data Search & Tracking</h1>
                <p>Pelacakan data alumni dari sumber nyata: PDDIKTI, LinkedIn, Google Scholar, dan web publik.</p>
            </div>
            <a href="index.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Dashboard</a>
        </header>

        <?php if($message): ?>
        <div class="alert alert-<?= $msgType ?> animate-up"><i class="fas fa-<?= $msgType=='success'?'check-circle':'info-circle' ?>"></i> <?= $message ?></div>
        <?php endif; ?>

        <!-- Penilaian Score Cards -->
        <div class="stats-grid animate-up delay-1">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(99,102,241,0.12);color:var(--primary);"><i class="fas fa-database"></i></div>
                <div class="stat-info"><h3>Total Alumni</h3><div class="value"><?= number_format($total,0,',','.') ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,0.12);color:#34d399;"><i class="fas fa-check-double"></i></div>
                <div class="stat-info"><h3>Data Ditemukan</h3><div class="value"><?= number_format($filled,0,',','.') ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,0.12);color:#fbbf24;"><i class="fas fa-percentage"></i></div>
                <div class="stat-info"><h3>Coverage Score</h3><div class="value"><?= $coverage ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(139,92,246,0.12);color:#a78bfa;"><i class="fas fa-shield-halved"></i></div>
                <div class="stat-info"><h3>Accuracy</h3><div class="value"><?= $accuracy ?>%</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(6,182,212,0.12);color:#22d3ee;"><i class="fas fa-file-circle-check"></i></div>
                <div class="stat-info"><h3>Fully Verified</h3><div class="value"><?= number_format($selesai,0,',','.') ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(244,63,94,0.12);color:#fb7185;"><i class="fas fa-clock"></i></div>
                <div class="stat-info"><h3>Belum Dilacak</h3><div class="value"><?= number_format($belum,0,',','.') ?></div></div>
            </div>
        </div>

        <!-- Progress Bars -->
        <div class="data-card animate-up delay-2" style="margin-bottom:1.25rem;">
            <h3 style="font-size:0.95rem;font-weight:600;margin-bottom:1rem;"><i class="fas fa-chart-line" style="color:var(--primary);margin-right:0.5rem;"></i>Progress Pelacakan</h3>
            <div style="margin-bottom:0.75rem;">
                <div style="display:flex;justify-content:space-between;margin-bottom:0.25rem;">
                    <span class="text-sm" style="font-weight:500;">Coverage</span>
                    <span class="text-sm" style="font-weight:700;color:var(--primary);"><?= $coverage ?>%</span>
                </div>
                <div style="background:rgba(15,23,42,0.6);border-radius:0.5rem;height:1.5rem;overflow:hidden;position:relative;">
                    <div style="background:linear-gradient(90deg,var(--primary),#818cf8);height:100%;width:<?= $coverage ?>%;border-radius:0.5rem;transition:width 1s ease;display:flex;align-items:center;justify-content:center;">
                        <span style="font-size:0.7rem;font-weight:700;color:white;"><?= $coverage ?>%</span>
                    </div>
                </div>
            </div>
            <div>
                <div style="display:flex;justify-content:space-between;margin-bottom:0.25rem;">
                    <span class="text-sm" style="font-weight:500;">Accuracy (Verified)</span>
                    <span class="text-sm" style="font-weight:700;color:#34d399;"><?= $accuracy ?>%</span>
                </div>
                <div style="background:rgba(15,23,42,0.6);border-radius:0.5rem;height:1.5rem;overflow:hidden;position:relative;">
                    <div style="background:linear-gradient(90deg,#10b981,#34d399);height:100%;width:<?= $accuracy ?>%;border-radius:0.5rem;transition:width 1s ease;display:flex;align-items:center;justify-content:center;">
                        <span style="font-size:0.7rem;font-weight:700;color:white;"><?= $accuracy ?>%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Method Info -->
        <div class="data-card animate-up delay-3" style="margin-bottom:1.25rem;">
            <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:1rem;"><i class="fas fa-search" style="color:var(--primary);"></i> Metode Pencarian Data</h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
                <div style="background:rgba(99,102,241,0.08);padding:1rem;border-radius:0.75rem;border:1px solid rgba(99,102,241,0.15);">
                    <h4 style="font-size:0.8rem;color:var(--primary);margin-bottom:0.5rem;"><i class="fab fa-linkedin"></i> LinkedIn Search</h4>
                    <p class="text-xs text-muted">Pencarian profil alumni via LinkedIn People Search dengan keyword nama + UMM</p>
                </div>
                <div style="background:rgba(16,185,129,0.08);padding:1rem;border-radius:0.75rem;border:1px solid rgba(16,185,129,0.15);">
                    <h4 style="font-size:0.8rem;color:#34d399;margin-bottom:0.5rem;"><i class="fas fa-graduation-cap"></i> PDDIKTI</h4>
                    <p class="text-xs text-muted">Verifikasi data akademik via Pangkalan Data Pendidikan Tinggi Kemdikbud</p>
                </div>
                <div style="background:rgba(245,158,11,0.08);padding:1rem;border-radius:0.75rem;border:1px solid rgba(245,158,11,0.15);">
                    <h4 style="font-size:0.8rem;color:#fbbf24;margin-bottom:0.5rem;"><i class="fas fa-search"></i> Google Scholar</h4>
                    <p class="text-xs text-muted">Pencarian publikasi ilmiah dan profil akademik alumni</p>
                </div>
                <div style="background:rgba(244,63,94,0.08);padding:1rem;border-radius:0.75rem;border:1px solid rgba(244,63,94,0.15);">
                    <h4 style="font-size:0.8rem;color:#fb7185;margin-bottom:0.5rem;"><i class="fas fa-globe"></i> Web Search</h4>
                    <p class="text-xs text-muted">Pencarian umum via Google/DuckDuckGo untuk sosmed, berita, dan profil publik</p>
                </div>
            </div>
        </div>

        <!-- Verified Alumni Sample -->
        <?php if (!empty($verifiedSample)): ?>
        <div class="data-card animate-up delay-4" style="margin-bottom:1.25rem;">
            <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:1rem;"><i class="fas fa-star" style="color:#fbbf24;"></i> Alumni Terverifikasi (Web Search)</h2>
            <div class="data-table-wrapper">
                <table>
                    <thead><tr><th>Alumni</th><th>Tempat Bekerja</th><th>Posisi</th><th>Sumber Verifikasi</th><th>Score</th></tr></thead>
                    <tbody>
                    <?php foreach($verifiedSample as $v): ?>
                    <tr>
                        <td><div class="cell-name"><?= htmlspecialchars($v['nama_lengkap']) ?></div><div class="cell-sub"><?= htmlspecialchars($v['nim']) ?></div></td>
                        <td style="font-size:0.8125rem;"><?= htmlspecialchars($v['tempat_bekerja'] ?? '-') ?></td>
                        <td style="font-size:0.8125rem;"><?= htmlspecialchars($v['posisi'] ?? '-') ?></td>
                        <td><span class="badge badge-success" style="font-size:0.7rem;"><?= htmlspecialchars($v['sumber_temuan']) ?></span></td>
                        <td><span style="font-weight:700;color:var(--primary);"><?= $v['confidence_score'] ?>%</span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Field Coverage Detail -->
        <div class="data-card animate-up delay-4">
            <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:1rem;"><i class="fas fa-chart-bar" style="color:var(--primary);"></i> Detail Coverage per Field (8 Data Points)</h2>
            <div class="data-table-wrapper">
                <table>
                    <thead><tr><th>No</th><th>Data Field</th><th>Ditemukan</th><th>Coverage</th></tr></thead>
                    <tbody>
                    <?php
                    $fields = [
                        ['Alamat Sosial Media: LinkedIn',$f1],
                        ['Alamat Sosial Media: Instagram',$f2],
                        ['Alamat Sosial Media: Facebook',$f3],
                        ['Alamat Sosial Media: TikTok',$f4],
                        ['Email',$f5],
                        ['No HP',$f6],
                        ['Tempat Bekerja',$f7],
                        ['Alamat Bekerja',$f8],
                        ['Posisi/Jabatan',$f9],
                        ['Status Pekerjaan (PNS/Swasta/Wirausaha)',$f10],
                        ['Sosmed Tempat Kerja',$f11]
                    ];
                    foreach($fields as $i => $f):
                        $pct = $total > 0 ? round($f[1]/$total*100,1) : 0;
                    ?>
                    <tr>
                        <td style="text-align:center;font-weight:600;color:var(--text-muted);"><?= $i+1 ?></td>
                        <td style="font-weight:500;"><?= $f[0] ?></td>
                        <td><?= number_format($f[1],0,',','.') ?></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:0.5rem;">
                                <div style="flex-grow:1;background:rgba(15,23,42,0.6);border-radius:0.25rem;height:0.5rem;max-width:120px;">
                                    <div style="background:var(--primary);height:100%;width:<?= $pct ?>%;border-radius:0.25rem;"></div>
                                </div>
                                <span class="text-xs"><?= $pct ?>%</span>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="privacy-footer"><i class="fas fa-shield-halved"></i> Data dilindungi — Hanya untuk kepentingan pembelajaran. &copy; 2026 Alumni Tracker Pro</div>
    </main>
</div>
</body>
</html>
