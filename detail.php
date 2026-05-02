<?php
require_once 'db.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { header('Location: login.php'); exit; }
if (!isset($_GET['id'])) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM alumni WHERE id = ?");
$stmt->execute([$_GET['id']]);
$al = $stmt->fetch();
if (!$al) { header('Location: index.php'); exit; }

// Get tracking history
$hist = $pdo->prepare("SELECT * FROM jejak_bukti WHERE alumni_id = ? ORDER BY tanggal_ditemukan DESC");
$hist->execute([$al['id']]);
$bukti = $hist->fetchAll();

$changes = $pdo->prepare("SELECT * FROM riwayat_perubahan WHERE alumni_id = ? ORDER BY tanggal_perubahan DESC LIMIT 10");
$changes->execute([$al['id']]);
$riwayat = $changes->fetchAll();

$initials = '';
$parts = explode(' ', $al['nama_lengkap']);
foreach (array_slice($parts, 0, 2) as $p) $initials .= mb_strtoupper(mb_substr($p, 0, 1));

$bClass = 'badge-neutral';
if (in_array($al['status_pelacakan'], ['Teridentifikasi','Selesai'])) $bClass = 'badge-success';
elseif ($al['status_pelacakan'] == 'Perlu Verifikasi Manual') $bClass = 'badge-warning';
elseif ($al['status_pelacakan'] == 'Belum Ditemukan') $bClass = 'badge-danger';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($al['nama_lengkap']) ?> | Alumni Tracker Pro</title>
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
            <div class="nav-item"><a href="export.php" class="nav-link"><i class="fas fa-file-export"></i><span>Export Data</span></a></div>
        </nav>
        <div class="sidebar-footer"><div class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-right-from-bracket"></i><span>Keluar</span></a></div></div>
    </aside>

    <main class="main-content">
        <header class="animate-up">
            <div class="page-title">
                <p><a href="index.php" style="color:var(--primary);"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a></p>
            </div>
            <div style="display:flex;gap:0.5rem;">
                <a href="edit.php?id=<?= $al['id'] ?>" class="btn btn-primary btn-sm"><i class="fas fa-pen"></i> Edit Data</a>
                <a href="tracker.php?id=<?= $al['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-satellite-dish"></i> Lacak</a>
                <a href="delete.php?id=<?= $al['id'] ?>" class="btn btn-outline btn-sm" style="color:var(--accent);" onclick="return confirm('Hapus data alumni ini?')"><i class="fas fa-trash"></i></a>
            </div>
        </header>

        <div class="data-card animate-up delay-1">
            <div class="profile-header">
                <div class="profile-avatar"><?= $initials ?></div>
                <div class="profile-info">
                    <h1><?= htmlspecialchars($al['nama_lengkap']) ?></h1>
                    <div class="profile-meta">
                        <span><i class="fas fa-id-card"></i> <?= htmlspecialchars($al['nim'] ?? '-') ?></span>
                        <span><i class="fas fa-university"></i> <?= htmlspecialchars($al['fakultas'] ?? $al['prodi'] ?? '-') ?></span>
                        <span><i class="fas fa-calendar"></i> Angkatan <?= $al['tahun_masuk'] ?: '-' ?></span>
                        <span class="badge <?= $bClass ?>"><?= $al['status_pelacakan'] ?></span>
                    </div>
                </div>
            </div>

            <!-- Informasi Akademik -->
            <div class="section-title"><i class="fas fa-graduation-cap"></i> Informasi Akademik</div>
            <div class="info-grid">
                <div class="info-item"><div class="info-label"><i class="fas fa-id-badge"></i> NIM</div><div class="info-value"><?= htmlspecialchars($al['nim'] ?? '-') ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-university"></i> Fakultas</div><div class="info-value"><?= htmlspecialchars($al['fakultas'] ?? '-') ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-book"></i> Program Studi</div><div class="info-value"><?= htmlspecialchars($al['prodi'] ?? '-') ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-calendar-check"></i> Tahun Masuk</div><div class="info-value"><?= $al['tahun_masuk'] ?: '-' ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-award"></i> Tanggal Lulus</div><div class="info-value"><?= htmlspecialchars($al['tanggal_lulus'] ?? '-') ?></div></div>
            </div>

            <!-- Kontak & Sosial Media -->
            <div class="section-title"><i class="fas fa-address-book"></i> Kontak & Sosial Media</div>
            <div class="info-grid">
                <div class="info-item"><div class="info-label"><i class="fas fa-envelope"></i> Email</div><div class="info-value"><?= $al['email'] ? '<a href="mailto:'.$al['email'].'">'.$al['email'].'</a>' : '-' ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-phone"></i> No HP</div><div class="info-value"><?= htmlspecialchars($al['no_hp'] ?? '-') ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fab fa-linkedin"></i> LinkedIn</div><div class="info-value"><?= $al['linkedin'] ? '<a href="'.$al['linkedin'].'" target="_blank">'.$al['linkedin'].'</a>' : '-' ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fab fa-instagram"></i> Instagram</div><div class="info-value"><?= $al['instagram'] ? '<a href="'.$al['instagram'].'" target="_blank">'.$al['instagram'].'</a>' : '-' ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fab fa-facebook"></i> Facebook</div><div class="info-value"><?= $al['facebook'] ? '<a href="'.$al['facebook'].'" target="_blank">'.$al['facebook'].'</a>' : '-' ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fab fa-tiktok"></i> TikTok</div><div class="info-value"><?= $al['tiktok'] ? '<a href="'.$al['tiktok'].'" target="_blank">'.$al['tiktok'].'</a>' : '-' ?></div></div>
            </div>

            <!-- Data Pekerjaan -->
            <div class="section-title"><i class="fas fa-briefcase"></i> Data Pekerjaan</div>
            <div class="info-grid">
                <div class="info-item"><div class="info-label"><i class="fas fa-building"></i> Tempat Bekerja</div><div class="info-value"><?= htmlspecialchars($al['tempat_bekerja'] ?? $al['perusahaan'] ?? '-') ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-map-marker-alt"></i> Alamat Bekerja</div><div class="info-value"><?= htmlspecialchars($al['alamat_bekerja'] ?? $al['lokasi'] ?? '-') ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-user-tie"></i> Posisi / Jabatan</div><div class="info-value"><?= htmlspecialchars($al['posisi'] ?? $al['jabatan'] ?? '-') ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-tag"></i> Status Pekerjaan</div>
                    <div class="info-value">
                        <?php if($al['status_pekerjaan']): ?>
                            <span class="badge badge-<?= strtolower($al['status_pekerjaan']) ?>"><?= $al['status_pekerjaan'] ?></span>
                        <?php else: ?> - <?php endif; ?>
                    </div>
                </div>
                <div class="info-item"><div class="info-label"><i class="fas fa-globe"></i> Sosmed Tempat Kerja</div><div class="info-value"><?= $al['work_social_media'] ? '<a href="'.$al['work_social_media'].'" target="_blank">'.$al['work_social_media'].'</a>' : '-' ?></div></div>
            </div>

            <!-- Riwayat Pelacakan -->
            <?php if(!empty($bukti)): ?>
            <div class="section-title"><i class="fas fa-history"></i> Jejak Bukti Pelacakan</div>
            <div class="data-table-wrapper">
                <table>
                    <thead><tr><th>Sumber</th><th>Info</th><th>Skor</th><th>Tanggal</th></tr></thead>
                    <tbody>
                    <?php foreach($bukti as $b): ?>
                        <tr>
                            <td><span class="badge badge-info"><?= htmlspecialchars($b['sumber_temuan']) ?></span></td>
                            <td style="font-size:0.8125rem;"><?= htmlspecialchars($b['ringkasan_info']) ?></td>
                            <td><strong><?= $b['confidence_score'] ?>%</strong></td>
                            <td class="text-xs text-muted"><?= $b['tanggal_ditemukan'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <?php if(!empty($riwayat)): ?>
            <div class="section-title"><i class="fas fa-clock-rotate-left"></i> Riwayat Perubahan</div>
            <div class="data-table-wrapper">
                <table>
                    <thead><tr><th>Tanggal</th><th>Data Lama</th><th>Data Baru</th></tr></thead>
                    <tbody>
                    <?php foreach($riwayat as $r): ?>
                        <tr>
                            <td class="text-xs"><?= $r['tanggal_perubahan'] ?></td>
                            <td style="font-size:0.75rem;color:var(--accent);"><?= htmlspecialchars(mb_substr($r['data_lama'],0,100)) ?></td>
                            <td style="font-size:0.75rem;color:var(--secondary);"><?= htmlspecialchars(mb_substr($r['data_baru'],0,100)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div class="privacy-footer"><i class="fas fa-shield-halved"></i> Data dilindungi — Hanya untuk kepentingan pembelajaran. &copy; 2026 Alumni Tracker Pro</div>
    </main>
</div>
</body>
</html>
