<?php
require_once 'db.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { header('Location: login.php'); exit; }

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fields = ['nim','nama_lengkap','tahun_masuk','tanggal_lulus','fakultas','prodi','kota','bidang_keilmuan',
               'linkedin','instagram','facebook','tiktok','email','no_hp',
               'tempat_bekerja','alamat_bekerja','posisi','status_pekerjaan','work_social_media'];
    $data = [];
    foreach ($fields as $f) {
        $data[$f] = trim($_POST[$f] ?? '');
        if ($data[$f] === '') $data[$f] = null;
    }
    if (empty($data['nama_lengkap'])) {
        $error = 'Nama lengkap wajib diisi!';
    } else {
        $sql = "INSERT INTO alumni (nim, nama_lengkap, tahun_masuk, tanggal_lulus, fakultas, prodi, kota, bidang_keilmuan,
                linkedin, instagram, facebook, tiktok, email, no_hp,
                tempat_bekerja, alamat_bekerja, posisi, status_pekerjaan, work_social_media,
                status_pelacakan, waktu_sekarang)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Belum Dilacak',CURDATE())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($data));
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Alumni | Alumni Tracker Pro</title>
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
            <div class="nav-item"><a href="add.php" class="nav-link active"><i class="fas fa-user-plus"></i><span>Tambah Alumni</span></a></div>
            <div class="nav-section-title">Tools</div>
            <div class="nav-item"><a href="import_alumni.php" class="nav-link"><i class="fas fa-file-import"></i><span>Import CSV</span></a></div>
            <div class="nav-item"><a href="export.php" class="nav-link"><i class="fas fa-file-export"></i><span>Export Data</span></a></div>
        </nav>
        <div class="sidebar-footer"><div class="nav-item"><a href="logout.php" class="nav-link"><i class="fas fa-right-from-bracket"></i><span>Keluar</span></a></div></div>
    </aside>

    <main class="main-content">
        <header class="animate-up">
            <div class="page-title">
                <h1>Tambah Data Alumni</h1>
                <p>Masukkan data alumni baru ke dalam sistem pelacakan.</p>
            </div>
            <a href="index.php" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a>
        </header>

        <?php if($success): ?>
        <div class="alert alert-success animate-up"><i class="fas fa-check-circle"></i> Data alumni berhasil ditambahkan!</div>
        <?php endif; ?>
        <?php if($error): ?>
        <div class="alert alert-error animate-up"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
        <?php endif; ?>

        <div class="data-card animate-up delay-1">
            <form method="POST" action="">
                <div class="form-section-title"><i class="fas fa-graduation-cap"></i> Data Akademik</div>
                <div class="form-row">
                    <div class="form-group"><label>Nama Lengkap *</label><input type="text" name="nama_lengkap" class="form-control" required placeholder="Nama lengkap alumni"></div>
                    <div class="form-group"><label>NIM</label><input type="text" name="nim" class="form-control" placeholder="Nomor Induk Mahasiswa"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Fakultas</label><input type="text" name="fakultas" class="form-control" placeholder="Contoh: Ekonomi"></div>
                    <div class="form-group"><label>Program Studi</label><input type="text" name="prodi" class="form-control" placeholder="Contoh: Akuntansi"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Tahun Masuk</label><input type="number" name="tahun_masuk" class="form-control" placeholder="2020" min="1980" max="2030"></div>
                    <div class="form-group"><label>Tanggal Lulus</label><input type="text" name="tanggal_lulus" class="form-control" placeholder="1 Juli 2024"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Kota</label><input type="text" name="kota" class="form-control" placeholder="Kota asal/domisili"></div>
                    <div class="form-group"><label>Bidang Keilmuan</label><input type="text" name="bidang_keilmuan" class="form-control" placeholder="Contoh: Software Engineering"></div>
                </div>

                <div class="form-section-title"><i class="fas fa-address-book"></i> Kontak & Sosial Media</div>
                <div class="form-row">
                    <div class="form-group"><label><i class="fas fa-envelope"></i> Email</label><input type="email" name="email" class="form-control" placeholder="email@example.com"></div>
                    <div class="form-group"><label><i class="fas fa-phone"></i> No HP</label><input type="text" name="no_hp" class="form-control" placeholder="+6281234567890"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label><i class="fab fa-linkedin"></i> LinkedIn</label><input type="url" name="linkedin" class="form-control" placeholder="https://linkedin.com/in/username"></div>
                    <div class="form-group"><label><i class="fab fa-instagram"></i> Instagram</label><input type="url" name="instagram" class="form-control" placeholder="https://instagram.com/username"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label><i class="fab fa-facebook"></i> Facebook</label><input type="url" name="facebook" class="form-control" placeholder="https://facebook.com/username"></div>
                    <div class="form-group"><label><i class="fab fa-tiktok"></i> TikTok</label><input type="url" name="tiktok" class="form-control" placeholder="https://tiktok.com/@username"></div>
                </div>

                <div class="form-section-title"><i class="fas fa-briefcase"></i> Data Pekerjaan</div>
                <div class="form-row">
                    <div class="form-group"><label>Tempat Bekerja</label><input type="text" name="tempat_bekerja" class="form-control" placeholder="Nama perusahaan/instansi"></div>
                    <div class="form-group"><label>Posisi / Jabatan</label><input type="text" name="posisi" class="form-control" placeholder="Contoh: Software Engineer"></div>
                </div>
                <div class="form-group"><label>Alamat Bekerja</label><textarea name="alamat_bekerja" class="form-control" placeholder="Alamat lengkap tempat bekerja"></textarea></div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Status Pekerjaan</label>
                        <select name="status_pekerjaan" class="form-control">
                            <option value="">-- Pilih Status --</option>
                            <option value="PNS">PNS</option>
                            <option value="Swasta">Swasta</option>
                            <option value="Wirausaha">Wirausaha</option>
                        </select>
                    </div>
                    <div class="form-group"><label><i class="fas fa-globe"></i> Sosmed Tempat Kerja</label><input type="url" name="work_social_media" class="form-control" placeholder="https://instagram.com/perusahaan"></div>
                </div>

                <div style="display:flex;gap:0.75rem;margin-top:1.5rem;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan Data Alumni</button>
                    <button type="reset" class="btn btn-outline"><i class="fas fa-undo"></i> Reset Form</button>
                </div>
            </form>
        </div>
        <div class="privacy-footer"><i class="fas fa-shield-halved"></i> Data dilindungi — Hanya untuk kepentingan pembelajaran. &copy; 2026 Alumni Tracker Pro</div>
    </main>
</div>
</body>
</html>
