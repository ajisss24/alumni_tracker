<?php
require_once 'db.php';
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { header('Location: login.php'); exit; }
if (!isset($_GET['id'])) { header("Location: index.php"); exit; }

$alumni_id_to_track = $_GET['id'];

function mock_scrape_internet($nama_lengkap, $prodi, $kota, $bidang) {
    $hasil = [];
    $nama_lower = strtolower(str_replace(' ', '', $nama_lengkap));
    // Generate realistic mock results based on name
    $hash = crc32($nama_lengkap);
    $sources = ['LinkedIn', 'Google Scholar', 'Instagram', 'Facebook', 'Google Search', 'GitHub'];
    $src = $sources[abs($hash) % count($sources)];

    $hasil[] = [
        'sumber' => $src,
        'link' => strtolower($src) == 'linkedin' ? "linkedin.com/in/$nama_lower" : "$nama_lower",
        'judul' => "$nama_lengkap - Profesional",
        'snippet' => "Lulusan $prodi, kini berkarir di bidang $bidang.",
        'tanggal' => date('Y-m-d', abs($hash) % (time() - 946684800) + 946684800)
    ];

    if (abs($hash) % 3 === 0) {
        $hasil[] = [
            'sumber' => 'Google Scholar',
            'link' => "scholar.google.com/?q=" . urlencode($nama_lengkap),
            'judul' => "Publikasi oleh $nama_lengkap",
            'snippet' => "Universitas Muhammadiyah Malang. Peneliti aktif.",
            'tanggal' => date('Y-m-d')
        ];
    }
    return $hasil;
}

function Simpan_Jejak_Bukti($pdo, $alumni_id, $kandidat_terpilih) {
    $stmt = $pdo->prepare("INSERT INTO jejak_bukti (alumni_id, sumber_temuan, ringkasan_info, confidence_score, tanggal_ditemukan, pointer_bukti) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($kandidat_terpilih as $k) {
        if ($k['status_kandidat'] == 'Tidak Cocok') continue;
        $ringkasan = json_encode(['jabatan' => $k['jabatan'], 'instansi' => $k['instansi'], 'lokasi' => $k['lokasi']], JSON_UNESCAPED_SLASHES);
        $pointer = json_encode(['judul' => $k['judul_asli'], 'link' => $k['link_profil'], 'snippet' => $k['snippet_asli']], JSON_UNESCAPED_SLASHES);
        $stmt->execute([$alumni_id, $k['sumber'], $ringkasan, $k['skor'], date('Y-m-d'), $pointer]);
    }
}

function Simpan_Riwayat_Perubahan($pdo, $alumni_id, $data_lama, $data_baru) {
    $lama_json = json_encode($data_lama);
    $baru_json = json_encode($data_baru);
    if ($lama_json !== $baru_json) {
        $pdo->prepare("INSERT INTO riwayat_perubahan (alumni_id, data_lama, data_baru, tanggal_perubahan) VALUES (?, ?, ?, NOW())")
            ->execute([$alumni_id, $lama_json, $baru_json]);
    }
}

$stmt_get = $pdo->prepare("SELECT * FROM alumni WHERE id = ?");
$stmt_get->execute([$alumni_id_to_track]);
$database_alumni = $stmt_get->fetchAll();

foreach ($database_alumni as $data_alumni) {
    $alumni_id = $data_alumni['id'];
    $nama = $data_alumni['nama_lengkap'];
    $prodi = $data_alumni['prodi'] ?? '';
    $kota = $data_alumni['kota'] ?? '';
    $bidang = $data_alumni['bidang_keilmuan'] ?? $prodi;

    $kandidat_hasil = mock_scrape_internet($nama, $prodi, $kota, $bidang);

    $sinyal_identitas = [];
    foreach ($kandidat_hasil as $hasil) {
        $sinyal = [
            'nama' => $nama,
            'instansi' => (stripos($hasil['snippet'], 'UMM') !== false || stripos($hasil['snippet'], 'Universitas') !== false) ? 'UMM / Instansi' : 'Instansi Lain',
            'jabatan' => $bidang,
            'lokasi' => $kota,
            'bidang_keahlian' => $bidang,
            'tahun_aktivitas' => date('Y', strtotime($hasil['tanggal'])),
            'link_profil' => $hasil['link'],
            'sumber' => $hasil['sumber'],
            'judul_asli' => $hasil['judul'],
            'snippet_asli' => $hasil['snippet']
        ];
        $sinyal_identitas[] = $sinyal;
    }

    foreach ($sinyal_identitas as &$kandidat) {
        $skor = 0;
        if (stripos($kandidat['nama'], $data_alumni['nama_lengkap']) !== false) $skor += 40;
        if (stripos($kandidat['instansi'], 'UMM') !== false) $skor += 30;
        if ($kandidat['tahun_aktivitas'] >= ($data_alumni['tahun_masuk'] ?? 2000)) $skor += 15;
        if ($bidang && stripos($kandidat['bidang_keahlian'], $bidang) !== false) $skor += 15;

        $kandidat['status_kandidat'] = $skor >= 70 ? "Kemungkinan Kuat" : ($skor >= 40 ? "Perlu Verifikasi" : "Tidak Cocok");
        $kandidat['skor'] = min(100, $skor);
    }

    $status_baru = "Belum Ditemukan";
    $data_update = [];
    $kandidat_terpilih = [];
    $ada_kuat = false;

    foreach ($sinyal_identitas as $kandidat) {
        if ($kandidat['status_kandidat'] == 'Kemungkinan Kuat') {
            $ada_kuat = true;
            $kandidat_terpilih[] = $kandidat;
            $data_update = ['jabatan' => $kandidat['jabatan'], 'perusahaan' => 'Tracked Analytics', 'lokasi' => $kandidat['lokasi'] ?: $kota];
        } elseif ($kandidat['status_kandidat'] == 'Perlu Verifikasi' && !$ada_kuat) {
            $kandidat_terpilih[] = $kandidat;
        }
    }

    $status_baru = $ada_kuat ? "Teridentifikasi" : (!empty($kandidat_terpilih) ? "Perlu Verifikasi Manual" : "Belum Ditemukan");

    $data_lama = ['status' => $data_alumni['status_pelacakan'], 'jabatan' => $data_alumni['jabatan'], 'perusahaan' => $data_alumni['perusahaan']];
    $data_baru_snapshot = ['status' => $status_baru, 'jabatan' => $data_update['jabatan'] ?? $data_alumni['jabatan'], 'perusahaan' => $data_update['perusahaan'] ?? $data_alumni['perusahaan']];

    if (count($kandidat_terpilih) > 0) {
        Simpan_Jejak_Bukti($pdo, $alumni_id, $kandidat_terpilih);
        Simpan_Riwayat_Perubahan($pdo, $alumni_id, $data_lama, $data_baru_snapshot);
    }

    $q_update = "UPDATE alumni SET status_pelacakan = ?, tanggal_update = NOW()";
    $params = [$status_baru];
    if (!empty($data_update)) {
        $q_update .= ", jabatan = ?, perusahaan = ?, lokasi = ?";
        $params[] = $data_update['jabatan'];
        $params[] = $data_update['perusahaan'];
        $params[] = $data_update['lokasi'];
    }
    $q_update .= " WHERE id = ?";
    $params[] = $alumni_id;
    $pdo->prepare($q_update)->execute($params);
}

$nama_enc = urlencode($data_alumni['nama_lengkap']);
header("Location: index.php?status=success&nama=" . $nama_enc);
exit;
