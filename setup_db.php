<?php
/**
 * Setup Database - Jalankan sekali untuk membuat tabel dan admin default
 * Akses via browser: http://your-domain/setup_db.php
 */

// Auto-detect environment
if (strpos($_SERVER['HTTP_HOST'] ?? '', 'hstn.me') !== false) {
    $host = 'sql208.hstn.me';
    $dbname = 'mseet_41812072_alumni_trackerumm';
    $username = 'mseet_41812072';
    $password = 'ajissss18';
} else {
    $host = 'localhost';
    $dbname = 'alumni_tracker';
    $username = 'root';
    $password = '';
}

try {
    // On hosting, database is pre-created; on local, create it
    if ($host === 'localhost') {
        $pdo = new PDO("mysql:host=$host", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE $dbname");
    } else {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    // Drop & recreate tables
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS riwayat_perubahan");
    $pdo->exec("DROP TABLE IF EXISTS jejak_bukti");
    $pdo->exec("DROP TABLE IF EXISTS alumni");
    $pdo->exec("DROP TABLE IF EXISTS admins");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Alumni table
    $pdo->exec("CREATE TABLE alumni (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nim VARCHAR(50) DEFAULT NULL,
        nama_lengkap VARCHAR(255) NOT NULL,
        inisial VARCHAR(50) DEFAULT NULL,
        tahun_masuk YEAR DEFAULT NULL,
        tanggal_lulus VARCHAR(50) DEFAULT NULL,
        fakultas VARCHAR(150) DEFAULT NULL,
        prodi VARCHAR(150) DEFAULT NULL,
        tahun_lulus YEAR DEFAULT NULL,
        kota VARCHAR(100) DEFAULT NULL,
        bidang_keilmuan VARCHAR(100) DEFAULT NULL,
        status_pelacakan VARCHAR(50) DEFAULT 'Belum Dilacak',
        tanggal_update DATE DEFAULT NULL,
        waktu_sekarang DATE DEFAULT NULL,
        jadwal_pelacakan VARCHAR(20) DEFAULT '7_hari',
        linkedin VARCHAR(500) DEFAULT NULL,
        instagram VARCHAR(500) DEFAULT NULL,
        facebook VARCHAR(500) DEFAULT NULL,
        tiktok VARCHAR(500) DEFAULT NULL,
        email VARCHAR(255) DEFAULT NULL,
        no_hp VARCHAR(30) DEFAULT NULL,
        tempat_bekerja VARCHAR(255) DEFAULT NULL,
        alamat_bekerja TEXT DEFAULT NULL,
        posisi VARCHAR(150) DEFAULT NULL,
        jabatan VARCHAR(150) DEFAULT NULL,
        perusahaan VARCHAR(255) DEFAULT NULL,
        lokasi VARCHAR(150) DEFAULT NULL,
        status_pekerjaan ENUM('PNS','Swasta','Wirausaha') DEFAULT NULL,
        work_social_media VARCHAR(500) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_nama (nama_lengkap),
        INDEX idx_nim (nim),
        INDEX idx_fakultas (fakultas),
        INDEX idx_prodi (prodi),
        INDEX idx_status (status_pelacakan)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Admins
    $pdo->exec("CREATE TABLE admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        nama_lengkap VARCHAR(100) DEFAULT 'Administrator',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $hash = password_hash('password', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO admins (username, password_hash, nama_lengkap) VALUES (?, ?, ?)")
        ->execute(['admin', $hash, 'Administrator']);

    // Jejak bukti
    $pdo->exec("CREATE TABLE jejak_bukti (
        id INT AUTO_INCREMENT PRIMARY KEY,
        alumni_id INT NOT NULL,
        sumber_temuan VARCHAR(100),
        ringkasan_info TEXT,
        confidence_score INT DEFAULT 0,
        tanggal_ditemukan DATE,
        pointer_bukti TEXT,
        FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Riwayat perubahan
    $pdo->exec("CREATE TABLE riwayat_perubahan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        alumni_id INT NOT NULL,
        data_lama TEXT,
        data_baru TEXT,
        tanggal_perubahan DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    echo "<html><head><link rel='stylesheet' href='style.css'><link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'></head><body style='display:block;'>";
    echo "<div class='login-wrapper'><div class='login-card' style='max-width:500px;'>";
    echo "<div style='text-align:center;margin-bottom:1.5rem;'><div style='font-size:3rem;color:var(--secondary);margin-bottom:1rem;'><i class='fas fa-check-circle'></i></div>";
    echo "<h1 style='font-size:1.5rem;font-weight:700;margin-bottom:0.5rem;color:var(--text-primary);'>Setup Berhasil!</h1></div>";
    echo "<div class='alert alert-success'><i class='fas fa-database'></i> Database <strong>alumni_tracker</strong> berhasil dibuat.</div>";
    echo "<div class='alert alert-success'><i class='fas fa-table'></i> 4 tabel berhasil dibuat: alumni, admins, jejak_bukti, riwayat_perubahan.</div>";
    echo "<div class='alert alert-success'><i class='fas fa-user-shield'></i> Admin default: <strong>admin</strong> / <strong>password</strong></div>";
    echo "<div style='margin-top:1.5rem;'><a href='login.php' class='btn btn-primary' style='width:100%;padding:0.875rem;'><i class='fas fa-arrow-right'></i> Login ke Dashboard</a></div>";
    echo "<div style='margin-top:1rem;'><a href='import_alumni.php' class='btn btn-outline' style='width:100%;padding:0.875rem;'><i class='fas fa-file-import'></i> Import Data CSV (142K+)</a></div>";
    echo "</div></div></body></html>";

} catch (PDOException $e) {
    echo "<html><head><link rel='stylesheet' href='style.css'></head><body style='display:block;'>";
    echo "<div class='login-wrapper'><div class='login-card'>";
    echo "<div class='alert alert-error'><i class='fas fa-times-circle'></i> Error: " . $e->getMessage() . "</div>";
    echo "<p class='text-sm text-muted'>Pastikan MySQL/XAMPP sudah berjalan.</p>";
    echo "</div></div></body></html>";
}
