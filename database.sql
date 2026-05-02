-- ============================================================
-- Database: alumni_tracker
-- Sistem Pelacakan Alumni - Professional Edition
-- ============================================================

CREATE DATABASE IF NOT EXISTS alumni_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE alumni_tracker;

-- Tabel utama alumni
DROP TABLE IF EXISTS riwayat_perubahan;
DROP TABLE IF EXISTS jejak_bukti;
DROP TABLE IF EXISTS alumni;
DROP TABLE IF EXISTS admins;

CREATE TABLE alumni (
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

    -- Status pelacakan
    status_pelacakan VARCHAR(50) DEFAULT 'Belum Dilacak',
    tanggal_update DATE DEFAULT NULL,
    waktu_sekarang DATE DEFAULT NULL,
    jadwal_pelacakan VARCHAR(20) DEFAULT '7_hari',

    -- Sosial Media
    linkedin VARCHAR(500) DEFAULT NULL,
    instagram VARCHAR(500) DEFAULT NULL,
    facebook VARCHAR(500) DEFAULT NULL,
    tiktok VARCHAR(500) DEFAULT NULL,

    -- Kontak
    email VARCHAR(255) DEFAULT NULL,
    no_hp VARCHAR(30) DEFAULT NULL,

    -- Pekerjaan
    tempat_bekerja VARCHAR(255) DEFAULT NULL,
    alamat_bekerja TEXT DEFAULT NULL,
    posisi VARCHAR(150) DEFAULT NULL,
    jabatan VARCHAR(150) DEFAULT NULL,
    perusahaan VARCHAR(255) DEFAULT NULL,
    lokasi VARCHAR(150) DEFAULT NULL,
    status_pekerjaan ENUM('PNS', 'Swasta', 'Wirausaha') DEFAULT NULL,

    -- Sosmed tempat kerja
    work_social_media VARCHAR(500) DEFAULT NULL,

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_nama (nama_lengkap),
    INDEX idx_nim (nim),
    INDEX idx_fakultas (fakultas),
    INDEX idx_prodi (prodi),
    INDEX idx_status (status_pelacakan),
    INDEX idx_tahun_masuk (tahun_masuk)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel admin
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) DEFAULT 'Administrator',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin: admin / password
INSERT INTO admins (username, password_hash, nama_lengkap) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator');

-- Tabel jejak bukti pelacakan
CREATE TABLE jejak_bukti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT NOT NULL,
    sumber_temuan VARCHAR(100),
    ringkasan_info TEXT,
    confidence_score INT DEFAULT 0,
    tanggal_ditemukan DATE,
    pointer_bukti TEXT,
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel riwayat perubahan data
CREATE TABLE riwayat_perubahan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT NOT NULL,
    data_lama TEXT,
    data_baru TEXT,
    tanggal_perubahan DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
