<?php
/**
 * Alumni Search Engine - Real Data Discovery
 * Searches multiple sources to find real alumni data
 * Sources: PDDIKTI, LinkedIn (via Google), Google Scholar, General Web
 */

class AlumniSearchEngine {
    
    private $pdo;
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
    private $searchDelay = 2; // seconds between searches to avoid rate limiting
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Search for an alumni across multiple sources
     */
    public function searchAlumni($nama, $nim = '', $prodi = '', $fakultas = '') {
        $results = [
            'linkedin' => null,
            'instagram' => null,
            'facebook' => null,
            'tiktok' => null,
            'email' => null,
            'tempat_bekerja' => null,
            'alamat_bekerja' => null,
            'posisi' => null,
            'status_pekerjaan' => null,
            'work_social_media' => null,
            'sources' => [],
            'confidence' => 0
        ];
        
        // Search PDDIKTI
        $pddiktiData = $this->searchPDDIKTI($nama);
        if ($pddiktiData) {
            $results['sources'][] = ['type' => 'PDDIKTI', 'url' => 'https://pddikti.kemdikbud.go.id/', 'data' => $pddiktiData];
            $results['confidence'] += 20;
        }
        
        // Search Google for LinkedIn profile
        $linkedinData = $this->searchLinkedIn($nama, $prodi);
        if ($linkedinData) {
            $results['linkedin'] = $linkedinData['url'];
            if (isset($linkedinData['position'])) $results['posisi'] = $linkedinData['position'];
            if (isset($linkedinData['company'])) $results['tempat_bekerja'] = $linkedinData['company'];
            $results['sources'][] = ['type' => 'LinkedIn', 'url' => $linkedinData['url'], 'data' => $linkedinData];
            $results['confidence'] += 40;
        }
        
        // Search Google Scholar
        $scholarData = $this->searchGoogleScholar($nama);
        if ($scholarData) {
            $results['sources'][] = ['type' => 'Google Scholar', 'url' => $scholarData['url'] ?? '', 'data' => $scholarData];
            if (!$results['tempat_bekerja'] && isset($scholarData['affiliation'])) {
                $results['tempat_bekerja'] = $scholarData['affiliation'];
            }
            $results['confidence'] += 20;
        }
        
        return $results;
    }
    
    /**
     * Search PDDIKTI for student data
     */
    private function searchPDDIKTI($nama) {
        $url = 'https://api-frontend.kemdikbud.go.id/hit_mhs/' . urlencode($nama);
        $data = $this->httpGet($url);
        if ($data) {
            $json = json_decode($data, true);
            if ($json && isset($json['mahasiswa']) && count($json['mahasiswa']) > 0) {
                foreach ($json['mahasiswa'] as $mhs) {
                    if (stripos($mhs['nama'] ?? '', $nama) !== false) {
                        return $mhs;
                    }
                }
                return $json['mahasiswa'][0];
            }
        }
        return null;
    }
    
    /**
     * Search for LinkedIn profile via DuckDuckGo
     */
    private function searchLinkedIn($nama, $prodi = '') {
        $query = '"' . $nama . '" "Universitas Muhammadiyah Malang" site:linkedin.com/in';
        $url = 'https://html.duckduckgo.com/html/?q=' . urlencode($query);
        
        $html = $this->httpGet($url);
        if (!$html) return null;
        
        // Parse LinkedIn URLs from results
        if (preg_match_all('/https?:\/\/[a-z]+\.linkedin\.com\/in\/[a-zA-Z0-9\-_%]+/', $html, $matches)) {
            $linkedinUrl = $matches[0][0];
            
            // Try to extract headline from search snippet
            $position = null;
            $company = null;
            
            // Parse snippet for job info
            if (preg_match('/' . preg_quote($nama, '/') . '\s*[-–]\s*([^|<"]+)/i', $html, $headlineMatch)) {
                $headline = trim($headlineMatch[1]);
                // Try to extract position and company from "Position at Company" pattern
                if (preg_match('/^(.+?)\s+(?:at|di|pada)\s+(.+)$/i', $headline, $parts)) {
                    $position = trim($parts[1]);
                    $company = trim($parts[2]);
                }
            }
            
            return [
                'url' => $linkedinUrl,
                'position' => $position,
                'company' => $company
            ];
        }
        
        return null;
    }
    
    /**
     * Search Google Scholar
     */
    private function searchGoogleScholar($nama) {
        $url = 'https://scholar.google.com/scholar?q=' . urlencode('"' . $nama . '" "Muhammadiyah Malang"');
        $html = $this->httpGet($url);
        if (!$html) return null;
        
        // Check if any results found
        if (stripos($html, $nama) !== false) {
            return [
                'url' => 'https://scholar.google.com/scholar?q=' . urlencode($nama),
                'found' => true,
                'affiliation' => 'Universitas Muhammadiyah Malang'
            ];
        }
        
        return null;
    }
    
    /**
     * HTTP GET request with proper headers
     */
    private function httpGet($url) {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: {$this->userAgent}\r\nAccept: text/html,application/json\r\nAccept-Language: id,en\r\n",
                'timeout' => 10,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $result = @file_get_contents($url, false, $ctx);
        sleep($this->searchDelay);
        return $result;
    }
    
    /**
     * Batch search alumni and update database
     */
    public function batchSearch($limit = 100, $offset = 0) {
        $stmt = $this->pdo->prepare("SELECT id, nama_lengkap, nim, prodi, fakultas FROM alumni 
            WHERE status_pelacakan = 'Belum Dilacak' 
            AND nama_lengkap != '' 
            AND LENGTH(nama_lengkap) > 5
            ORDER BY id ASC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $alumni = $stmt->fetchAll();
        
        $found = 0;
        $results = [];
        
        foreach ($alumni as $al) {
            $searchResult = $this->searchAlumni($al['nama_lengkap'], $al['nim'], $al['prodi'], $al['fakultas']);
            
            if ($searchResult['confidence'] > 0) {
                $this->updateAlumniData($al['id'], $searchResult);
                $found++;
                $results[] = [
                    'id' => $al['id'],
                    'nama' => $al['nama_lengkap'],
                    'confidence' => $searchResult['confidence'],
                    'sources' => array_column($searchResult['sources'], 'type')
                ];
            }
        }
        
        return ['searched' => count($alumni), 'found' => $found, 'results' => $results];
    }
    
    /**
     * Update alumni record with found data
     */
    private function updateAlumniData($id, $data) {
        $fields = [];
        $params = [];
        
        $mapping = [
            'linkedin' => 'linkedin',
            'instagram' => 'instagram', 
            'facebook' => 'facebook',
            'tiktok' => 'tiktok',
            'email' => 'email',
            'tempat_bekerja' => 'tempat_bekerja',
            'alamat_bekerja' => 'alamat_bekerja',
            'posisi' => 'posisi',
            'status_pekerjaan' => 'status_pekerjaan',
            'work_social_media' => 'work_social_media'
        ];
        
        foreach ($mapping as $key => $col) {
            if (!empty($data[$key])) {
                $fields[] = "$col = ?";
                $params[] = $data[$key];
            }
        }
        
        if (!empty($fields)) {
            $fields[] = "status_pelacakan = ?";
            $params[] = $data['confidence'] >= 60 ? 'Teridentifikasi' : 'Perlu Verifikasi Manual';
            $fields[] = "tanggal_update = CURDATE()";
            $params[] = $id;
            
            $sql = "UPDATE alumni SET " . implode(', ', $fields) . " WHERE id = ?";
            $this->pdo->prepare($sql)->execute($params);
            
            // Save evidence
            foreach ($data['sources'] as $source) {
                $this->saveEvidence($id, $source);
            }
        }
    }
    
    /**
     * Save search evidence
     */
    private function saveEvidence($alumniId, $source) {
        $sql = "INSERT INTO jejak_bukti (alumni_id, sumber_temuan, ringkasan_info, confidence_score, tanggal_ditemukan, pointer_bukti) 
                VALUES (?, ?, ?, ?, CURDATE(), ?)";
        $this->pdo->prepare($sql)->execute([
            $alumniId,
            $source['type'],
            json_encode($source['data'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $source['data']['confidence'] ?? 50,
            $source['url'] ?? ''
        ]);
    }
}
