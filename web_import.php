<?php
/**
 * Web-based CSV Import - Jalankan via browser untuk import alumni.csv
 * Processes data in batches to avoid timeout on shared hosting
 * Usage: http://your-domain/web_import.php
 */
require_once 'db.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php'); exit;
}

set_time_limit(120);

$batch_size = 2000;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$action = $_GET['action'] ?? 'import_csv';

if ($action === 'import_csv') {
    $csv_file = __DIR__ . '/alumni.csv';
    
    if (!file_exists($csv_file)) {
        echo "<html><head><link rel='stylesheet' href='style.css'><link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'></head><body style='display:block;'>";
        echo "<div class='login-wrapper'><div class='login-card' style='max-width:600px;'>";
        echo "<div class='alert alert-error'><i class='fas fa-times-circle'></i> File <strong>alumni.csv</strong> tidak ditemukan!</div>";
        echo "<p class='text-sm text-muted'>Upload file alumni.csv ke folder htdocs via File Manager terlebih dahulu.</p>";
        echo "<a href='index.php' class='btn btn-primary' style='width:100%;padding:0.875rem;margin-top:1rem;'><i class='fas fa-arrow-left'></i> Kembali</a>";
        echo "</div></div></body></html>";
        exit;
    }

    $handle = fopen($csv_file, 'r');
    if (!$handle) { die("Gagal membuka CSV"); }

    // Skip header
    $header = fgetcsv($handle);
    
    // Count total lines (first time only)
    if ($offset === 0) {
        $total = 0;
        while (fgetcsv($handle) !== false) $total++;
        rewind($handle);
        fgetcsv($handle); // skip header again
        $_SESSION['csv_total'] = $total;
    }
    $total = $_SESSION['csv_total'] ?? 142292;

    // Skip to offset
    for ($i = 0; $i < $offset; $i++) {
        if (fgetcsv($handle) === false) break;
    }

    // Prepare insert
    $sql = "INSERT INTO alumni (nim, nama_lengkap, tahun_masuk, tanggal_lulus, fakultas, prodi) 
            VALUES (?, ?, ?, ?, ?, ?) 
            ON DUPLICATE KEY UPDATE nama_lengkap = VALUES(nama_lengkap)";
    $stmt = $pdo->prepare($sql);

    $inserted = 0;
    $pdo->beginTransaction();
    
    for ($i = 0; $i < $batch_size; $i++) {
        $row = fgetcsv($handle);
        if ($row === false) break;
        
        // CSV format: NIM, Nama, Tahun Masuk, Tanggal Lulus, Fakultas, Prodi (adjust as needed)
        $nim = trim($row[0] ?? '');
        $nama = trim($row[1] ?? '');
        $tahun_masuk = !empty($row[2]) ? (int)$row[2] : null;
        $tanggal_lulus = trim($row[3] ?? '') ?: null;
        $fakultas = trim($row[4] ?? '') ?: null;
        $prodi = trim($row[5] ?? '') ?: null;
        
        if (empty($nama)) continue;
        
        try {
            $stmt->execute([$nim, $nama, $tahun_masuk, $tanggal_lulus, $fakultas, $prodi]);
            $inserted++;
        } catch (Exception $e) {
            // Skip errors, continue importing
        }
    }
    
    $pdo->commit();
    fclose($handle);

    $new_offset = $offset + $batch_size;
    $progress = min(100, round(($new_offset / $total) * 100, 1));
    $done = $new_offset >= $total;

    echo "<html><head><link rel='stylesheet' href='style.css'><link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'>";
    if (!$done) {
        echo "<meta http-equiv='refresh' content='1;url=web_import.php?action=import_csv&offset=$new_offset'>";
    }
    echo "</head><body style='display:block;'>";
    echo "<div class='login-wrapper'><div class='login-card' style='max-width:600px;'>";
    echo "<div style='text-align:center;margin-bottom:1.5rem;'>";
    echo "<div style='font-size:3rem;color:var(--primary);margin-bottom:1rem;'><i class='fas fa-" . ($done ? 'check-circle' : 'spinner fa-spin') . "'></i></div>";
    echo "<h1 style='font-size:1.5rem;font-weight:700;color:var(--text-primary);'>" . ($done ? 'Import Selesai!' : 'Mengimpor Data...') . "</h1></div>";
    
    // Progress bar
    echo "<div style='background:var(--card-border);border-radius:8px;height:24px;margin:1rem 0;overflow:hidden;'>";
    echo "<div style='background:linear-gradient(90deg,var(--primary),var(--secondary));height:100%;width:{$progress}%;border-radius:8px;transition:width 0.3s;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.75rem;font-weight:600;'>{$progress}%</div></div>";
    
    echo "<div class='alert alert-success'><i class='fas fa-database'></i> Batch: " . number_format($offset) . " - " . number_format(min($new_offset, $total)) . " / " . number_format($total) . " | Inserted: $inserted</div>";
    
    if ($done) {
        $actual = $pdo->query("SELECT COUNT(*) FROM alumni")->fetchColumn();
        echo "<div class='alert alert-success'><i class='fas fa-check'></i> Total records di database: <strong>" . number_format($actual) . "</strong></div>";
        echo "<div style='margin-top:1rem;display:flex;gap:0.5rem;'>";
        echo "<a href='web_import.php?action=apply_updates&offset=0' class='btn btn-primary' style='flex:1;padding:0.875rem;'><i class='fas fa-magic'></i> Populate Data Lengkap</a>";
        echo "<a href='index.php' class='btn btn-outline' style='flex:1;padding:0.875rem;'><i class='fas fa-home'></i> Dashboard</a>";
        echo "</div>";
    } else {
        echo "<p class='text-sm text-muted' style='text-align:center;'>Jangan tutup halaman ini. Redirect otomatis...</p>";
    }
    echo "</div></div></body></html>";

} elseif ($action === 'apply_updates') {
    // Apply update_alumni_0.sql in chunks
    $sql_file = __DIR__ . '/update_alumni_0.sql';
    
    if (!file_exists($sql_file)) {
        echo "<html><head><link rel='stylesheet' href='style.css'><link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'></head><body style='display:block;'>";
        echo "<div class='login-wrapper'><div class='login-card' style='max-width:600px;'>";
        echo "<div class='alert alert-error'><i class='fas fa-times-circle'></i> File <strong>update_alumni_0.sql</strong> tidak ditemukan!</div>";
        echo "<p class='text-sm text-muted'>Upload file update_alumni_0.sql ke folder htdocs via File Manager terlebih dahulu.</p>";
        echo "<a href='index.php' class='btn btn-primary' style='width:100%;padding:0.875rem;margin-top:1rem;'><i class='fas fa-arrow-left'></i> Dashboard</a>";
        echo "</div></div></body></html>";
        exit;
    }

    $handle = fopen($sql_file, 'r');
    if (!$handle) { die("Gagal membuka SQL file"); }

    // Count total lines first time
    if ($offset === 0) {
        $total = 0;
        while (fgets($handle) !== false) $total++;
        rewind($handle);
        $_SESSION['sql_total'] = $total;
    }
    $total = $_SESSION['sql_total'] ?? 142292;

    // Skip to offset
    for ($i = 0; $i < $offset; $i++) {
        if (fgets($handle) === false) break;
    }

    $executed = 0;
    $errors = 0;
    $pdo->beginTransaction();

    for ($i = 0; $i < $batch_size; $i++) {
        $line = fgets($handle);
        if ($line === false) break;
        
        $line = trim($line);
        if (empty($line) || strpos($line, '--') === 0) continue;
        
        try {
            $pdo->exec($line);
            $executed++;
        } catch (Exception $e) {
            $errors++;
        }
    }

    $pdo->commit();
    fclose($handle);

    $new_offset = $offset + $batch_size;
    $progress = min(100, round(($new_offset / $total) * 100, 1));
    $done = $new_offset >= $total;

    echo "<html><head><link rel='stylesheet' href='style.css'><link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'>";
    if (!$done) {
        echo "<meta http-equiv='refresh' content='1;url=web_import.php?action=apply_updates&offset=$new_offset'>";
    }
    echo "</head><body style='display:block;'>";
    echo "<div class='login-wrapper'><div class='login-card' style='max-width:600px;'>";
    echo "<div style='text-align:center;margin-bottom:1.5rem;'>";
    echo "<div style='font-size:3rem;color:" . ($done ? 'var(--secondary)' : 'var(--primary)') . ";margin-bottom:1rem;'><i class='fas fa-" . ($done ? 'check-circle' : 'spinner fa-spin') . "'></i></div>";
    echo "<h1 style='font-size:1.5rem;font-weight:700;color:var(--text-primary);'>" . ($done ? 'Update Selesai!' : 'Applying Updates...') . "</h1></div>";
    
    echo "<div style='background:var(--card-border);border-radius:8px;height:24px;margin:1rem 0;overflow:hidden;'>";
    echo "<div style='background:linear-gradient(90deg,#f59e0b,#10b981);height:100%;width:{$progress}%;border-radius:8px;transition:width 0.3s;display:flex;align-items:center;justify-content:center;color:#fff;font-size:0.75rem;font-weight:600;'>{$progress}%</div></div>";
    
    echo "<div class='alert alert-success'><i class='fas fa-play'></i> Lines: " . number_format($offset) . " - " . number_format(min($new_offset, $total)) . " / " . number_format($total) . " | OK: $executed | Errors: $errors</div>";
    
    if ($done) {
        echo "<a href='index.php' class='btn btn-primary' style='width:100%;padding:0.875rem;margin-top:1rem;'><i class='fas fa-home'></i> Buka Dashboard</a>";
    } else {
        echo "<p class='text-sm text-muted' style='text-align:center;'>Jangan tutup halaman ini. Redirect otomatis...</p>";
    }
    echo "</div></div></body></html>";
}
?>
