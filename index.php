<?php
require_once 'db.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}

// Pagination & filters
$limit = 25;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$search = $_GET['q'] ?? '';
$fakultas_f = $_GET['fakultas'] ?? '';
$prodi_f = $_GET['prodi'] ?? '';
$status_kerja = $_GET['status_kerja'] ?? '';
$status_lacak = $_GET['status_lacak'] ?? '';
$tahun_f = $_GET['tahun'] ?? '';

$where = " WHERE 1=1 ";
$params = [];
if ($search) { $where .= " AND (nama_lengkap LIKE ? OR nim LIKE ?) "; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($fakultas_f) { $where .= " AND fakultas = ? "; $params[] = $fakultas_f; }
if ($prodi_f) { $where .= " AND prodi = ? "; $params[] = $prodi_f; }
if ($status_kerja) { $where .= " AND status_pekerjaan = ? "; $params[] = $status_kerja; }
if ($status_lacak) { $where .= " AND status_pelacakan = ? "; $params[] = $status_lacak; }
if ($tahun_f) { $where .= " AND tahun_masuk = ? "; $params[] = $tahun_f; }

// Stats
$total_alumni = $pdo->query("SELECT COUNT(*) FROM alumni")->fetchColumn();
$total_tracked = $pdo->query("SELECT COUNT(*) FROM alumni WHERE status_pelacakan IN ('Teridentifikasi','Selesai')")->fetchColumn();
$total_belum = $pdo->query("SELECT COUNT(*) FROM alumni WHERE status_pelacakan = 'Belum Dilacak'")->fetchColumn();
$total_pns = $pdo->query("SELECT COUNT(*) FROM alumni WHERE status_pekerjaan = 'PNS'")->fetchColumn();
$total_swasta = $pdo->query("SELECT COUNT(*) FROM alumni WHERE status_pekerjaan = 'Swasta'")->fetchColumn();
$total_wira = $pdo->query("SELECT COUNT(*) FROM alumni WHERE status_pekerjaan = 'Wirausaha'")->fetchColumn();
$filled_data = $pdo->query("SELECT COUNT(*) FROM alumni WHERE linkedin IS NOT NULL OR instagram IS NOT NULL OR email IS NOT NULL OR tempat_bekerja IS NOT NULL")->fetchColumn();
$coverage_score = $total_alumni > 0 ? round(($filled_data / $total_alumni) * 100, 1) : 0;
$verified_count = $pdo->query("SELECT COUNT(DISTINCT alumni_id) FROM jejak_bukti")->fetchColumn();
$accuracy_score = $filled_data > 0 ? round(($verified_count / $filled_data) * 100, 1) : 0;

// Filtered count
$stmtC = $pdo->prepare("SELECT COUNT(*) FROM alumni $where");
$stmtC->execute($params);
$total_rows = $stmtC->fetchColumn();
$total_pages = max(1, ceil($total_rows / $limit));

// Alumni list
$stmt = $pdo->prepare("SELECT * FROM alumni $where ORDER BY id ASC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$alumni_list = $stmt->fetchAll();

// Fakultas list for filter
$fakultas_list = $pdo->query("SELECT DISTINCT fakultas FROM alumni WHERE fakultas IS NOT NULL AND fakultas != '' ORDER BY fakultas")->fetchAll(PDO::FETCH_COLUMN);

// Build query string helper
function buildQS($overrides = []) {
    $p = $_GET;
    foreach ($overrides as $k => $v) $p[$k] = $v;
    return http_build_query($p);
}
$qs = buildQS(['page' => '']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Alumni Tracker Pro</title>
    <meta name="description" content="Dashboard sistem pelacakan alumni - pantau dan kelola data alumni secara real-time.">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="app-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <span>Tracker Pro</span>
        </div>
        <nav class="nav-menu">
            <div class="nav-section-title">Menu Utama</div>
            <div class="nav-item"><a href="index.php" class="nav-link active"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a></div>
            <div class="nav-item"><a href="add.php" class="nav-link"><i class="fas fa-user-plus"></i><span>Tambah Alumni</span></a></div>
            <div class="nav-section-title">Tools</div>
            <div class="nav-item"><a href="import_alumni.php" class="nav-link"><i class="fas fa-file-import"></i><span>Import CSV</span></a></div>
            <div class="nav-item"><a href="populate_data.php" class="nav-link"><i class="fas fa-robot"></i><span>Populate Data</span></a></div>
            <div class="nav-item">
                <a href="export.php?<?= http_build_query(array_filter($_GET, fn($v) => $v !== '', ARRAY_FILTER_USE_BOTH)) ?>" class="nav-link">
                    <i class="fas fa-file-export"></i><span>Export Data</span>
                </a>
            </div>
        </nav>
        <div class="sidebar-footer">
            <div class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-right-from-bracket"></i><span>Keluar</span></a></div>
        </div>
    </aside>

    <!-- Main -->
    <main class="main-content">
        <header class="animate-up">
            <div class="page-title">
                <h1>Dashboard Pelacakan</h1>
                <p>Selamat datang, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>. Pantau progress pelacakan alumni.</p>
            </div>
            <div style="display:flex;gap:0.5rem;">
                <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Tambah Alumni</a>
            </div>
        </header>

        <!-- Stats -->
        <div class="stats-grid animate-up delay-1">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(99,102,241,0.12);color:var(--primary);"><i class="fas fa-users"></i></div>
                <div class="stat-info"><h3>Total Alumni</h3><div class="value"><?= number_format($total_alumni, 0, ',', '.') ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,0.12);color:#34d399;"><i class="fas fa-check-double"></i></div>
                <div class="stat-info"><h3>Teridentifikasi</h3><div class="value"><?= number_format($total_tracked, 0, ',', '.') ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(148,163,184,0.12);color:#94a3b8;"><i class="fas fa-clock"></i></div>
                <div class="stat-info"><h3>Belum Dilacak</h3><div class="value"><?= number_format($total_belum, 0, ',', '.') ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,0.12);color:#34d399;"><i class="fas fa-building-columns"></i></div>
                <div class="stat-info"><h3>PNS</h3><div class="value"><?= number_format($total_pns, 0, ',', '.') ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(99,102,241,0.12);color:#818cf8;"><i class="fas fa-briefcase"></i></div>
                <div class="stat-info"><h3>Swasta</h3><div class="value"><?= number_format($total_swasta, 0, ',', '.') ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,0.12);color:#fbbf24;"><i class="fas fa-store"></i></div>
                <div class="stat-info"><h3>Wirausaha</h3><div class="value"><?= number_format($total_wira, 0, ',', '.') ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,0.12);color:#fbbf24;"><i class="fas fa-percentage"></i></div>
                <div class="stat-info"><h3>Coverage Score</h3><div class="value"><?= $coverage_score ?></div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(139,92,246,0.12);color:#a78bfa;"><i class="fas fa-shield-halved"></i></div>
                <div class="stat-info"><h3>Accuracy</h3><div class="value"><?= $accuracy_score ?>%</div></div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-grid animate-up delay-2">
            <div class="chart-container">
                <h3><i class="fas fa-chart-doughnut"></i> Distribusi Fakultas (Top 8)</h3>
                <canvas id="chartFakultas" height="220"></canvas>
            </div>
            <div class="chart-container">
                <h3><i class="fas fa-chart-bar"></i> Trend Lulusan per Tahun</h3>
                <canvas id="chartTrend" height="220"></canvas>
            </div>
        </div>

        <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Data <strong><?= htmlspecialchars($_GET['nama'] ?? '') ?></strong> berhasil diproses.</div>
        <?php endif; ?>
        <?php if(isset($_GET['deleted'])): ?>
        <div class="alert alert-success"><i class="fas fa-trash"></i> Data alumni berhasil dihapus.</div>
        <?php endif; ?>

        <!-- Filter Bar -->
        <div class="data-card animate-up delay-3" style="padding:1rem 1.25rem;">
            <form action="" method="GET" class="filter-bar">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" class="form-control" placeholder="Cari nama atau NIM..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <select name="fakultas" class="form-control">
                    <option value="">Semua Fakultas</option>
                    <?php foreach($fakultas_list as $f): ?>
                    <option value="<?= htmlspecialchars($f) ?>" <?= $fakultas_f == $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="status_kerja" class="form-control">
                    <option value="">Status Kerja</option>
                    <option value="PNS" <?= $status_kerja=='PNS'?'selected':'' ?>>PNS</option>
                    <option value="Swasta" <?= $status_kerja=='Swasta'?'selected':'' ?>>Swasta</option>
                    <option value="Wirausaha" <?= $status_kerja=='Wirausaha'?'selected':'' ?>>Wirausaha</option>
                </select>
                <select name="status_lacak" class="form-control">
                    <option value="">Status Lacak</option>
                    <option value="Belum Dilacak" <?= $status_lacak=='Belum Dilacak'?'selected':'' ?>>Belum Dilacak</option>
                    <option value="Teridentifikasi" <?= $status_lacak=='Teridentifikasi'?'selected':'' ?>>Teridentifikasi</option>
                    <option value="Selesai" <?= $status_lacak=='Selesai'?'selected':'' ?>>Selesai</option>
                    <option value="Perlu Verifikasi Manual" <?= $status_lacak=='Perlu Verifikasi Manual'?'selected':'' ?>>Perlu Verifikasi</option>
                    <option value="Belum Ditemukan" <?= $status_lacak=='Belum Ditemukan'?'selected':'' ?>>Belum Ditemukan</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
                <?php if($search || $fakultas_f || $status_kerja || $status_lacak || $tahun_f || $prodi_f): ?>
                <a href="index.php" class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Data Table -->
        <div class="data-card animate-up delay-4">
            <div class="card-header">
                <h2><?= $search ? 'Pencarian: "'.htmlspecialchars($search).'"' : 'Daftar Alumni' ?>
                    <span class="text-xs text-muted" style="margin-left:0.5rem;">(<?= number_format($total_rows,0,',','.') ?> records)</span>
                </h2>
            </div>
            <div class="data-table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th style="width:55px;text-align:center;">No.</th>
                            <th>Alumni</th>
                            <th>Fakultas / Prodi</th>
                            <th>Tahun</th>
                            <th>Pekerjaan</th>
                            <th>Status Lacak</th>
                            <th style="width:120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(empty($alumni_list)): ?>
                        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-search"></i><p>Tidak ada data ditemukan.</p></div></td></tr>
                    <?php endif; ?>
                    <?php foreach($alumni_list as $i => $al): ?>
                        <tr>
                            <td style="text-align:center;color:var(--text-muted);font-size:0.8rem;font-weight:600;">
                                <?= number_format($offset + $i + 1, 0, ',', '.') ?>
                            </td>
                            <td>
                                <div class="cell-name"><?= htmlspecialchars($al['nama_lengkap']) ?></div>
                                <div class="cell-sub">NIM: <?= htmlspecialchars($al['nim'] ?? '-') ?></div>
                            </td>
                            <td>
                                <div style="font-size:0.8125rem;"><?= htmlspecialchars($al['fakultas'] ?: ($al['prodi'] ?: '-')) ?></div>
                                <?php if($al['fakultas'] && $al['prodi']): ?>
                                <div class="cell-sub"><?= htmlspecialchars($al['prodi']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size:0.8125rem;"><?= $al['tahun_masuk'] ?: '-' ?></div>
                                <div class="cell-sub"><?= $al['tanggal_lulus'] ? 'Lulus: '.$al['tanggal_lulus'] : '' ?></div>
                            </td>
                            <td>
                                <?php if($al['posisi'] || $al['tempat_bekerja']): ?>
                                    <div style="font-size:0.8125rem;font-weight:500;"><?= htmlspecialchars($al['posisi'] ?: $al['jabatan'] ?: '-') ?></div>
                                    <div class="cell-sub"><?= htmlspecialchars($al['tempat_bekerja'] ?: $al['perusahaan'] ?: '') ?></div>
                                    <?php if($al['status_pekerjaan']): ?>
                                        <span class="badge badge-<?= strtolower($al['status_pekerjaan']) ?>" style="margin-top:0.25rem;"><?= $al['status_pekerjaan'] ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-xs text-muted">Belum ada data</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                    $bClass = 'badge-neutral'; $bIcon = 'fa-clock';
                                    if (in_array($al['status_pelacakan'], ['Teridentifikasi','Selesai'])) { $bClass = 'badge-success'; $bIcon = 'fa-check-circle'; }
                                    elseif ($al['status_pelacakan'] == 'Perlu Verifikasi Manual') { $bClass = 'badge-warning'; $bIcon = 'fa-exclamation-triangle'; }
                                    elseif ($al['status_pelacakan'] == 'Belum Ditemukan') { $bClass = 'badge-danger'; $bIcon = 'fa-times-circle'; }
                                ?>
                                <span class="badge <?= $bClass ?>"><i class="fas <?= $bIcon ?>"></i> <?= $al['status_pelacakan'] ?></span>
                            </td>
                            <td>
                                <div style="display:flex;gap:0.375rem;">
                                    <a href="detail.php?id=<?= $al['id'] ?>" class="btn btn-outline btn-icon" title="Detail"><i class="fas fa-eye" style="font-size:0.8rem;"></i></a>
                                    <a href="edit.php?id=<?= $al['id'] ?>" class="btn btn-outline btn-icon" title="Edit"><i class="fas fa-pen" style="font-size:0.8rem;"></i></a>
                                    <a href="tracker.php?id=<?= $al['id'] ?>" class="btn btn-outline btn-icon" title="Lacak"><i class="fas fa-satellite-dish" style="font-size:0.8rem;color:var(--primary);"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?<?= buildQS(['page'=>1]) ?>" class="btn btn-outline btn-sm"><i class="fas fa-angles-left"></i></a>
                    <a href="?<?= buildQS(['page'=>$page-1]) ?>" class="btn btn-outline btn-sm"><i class="fas fa-angle-left"></i></a>
                <?php endif; ?>

                <?php
                $start_loop = max(1, $page - 2);
                $end_loop = min($total_pages, $page + 2);
                for ($i = $start_loop; $i <= $end_loop; $i++): ?>
                    <a href="?<?= buildQS(['page'=>$i]) ?>" class="btn <?= ($i==$page)?'btn-primary':'btn-outline' ?> btn-sm"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?= buildQS(['page'=>$page+1]) ?>" class="btn btn-outline btn-sm"><i class="fas fa-angle-right"></i></a>
                    <a href="?<?= buildQS(['page'=>$total_pages]) ?>" class="btn btn-outline btn-sm"><i class="fas fa-angles-right"></i></a>
                <?php endif; ?>
                <span class="page-info">Hal <?= $page ?> / <?= number_format($total_pages,0,',','.') ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="privacy-footer">
            <i class="fas fa-shield-halved"></i> Data dilindungi — Hanya untuk kepentingan pembelajaran. &copy; 2026 Alumni Tracker Pro
        </div>
    </main>
</div>

<script>
// Chart data from PHP
fetch('api_stats.php')
    .then(r => r.json())
    .then(data => {
        // Fakultas Donut
        new Chart(document.getElementById('chartFakultas'), {
            type: 'doughnut',
            data: {
                labels: data.fakultas.map(f => f.label),
                datasets: [{
                    data: data.fakultas.map(f => f.count),
                    backgroundColor: ['#6366f1','#10b981','#f59e0b','#f43f5e','#8b5cf6','#06b6d4','#ec4899','#14b8a6'],
                    borderWidth: 0,
                    borderRadius: 3
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#94a3b8', font: { size: 11, family: 'Inter' }, padding: 12, boxWidth: 12 } }
                },
                cutout: '65%'
            }
        });
        // Trend Bar
        new Chart(document.getElementById('chartTrend'), {
            type: 'bar',
            data: {
                labels: data.trend.map(t => t.year),
                datasets: [{
                    label: 'Jumlah Lulusan',
                    data: data.trend.map(t => t.count),
                    backgroundColor: 'rgba(99,102,241,0.6)',
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#64748b', font: { size: 10 } }, grid: { display: false } },
                    y: { ticks: { color: '#64748b', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.04)' } }
                }
            }
        });
    });
</script>
</body>
</html>
