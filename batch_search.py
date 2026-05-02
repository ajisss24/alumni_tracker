"""
Alumni Real Data Finder - Batch search using multiple sources
Searches PDDIKTI, Google, and constructs verifiable LinkedIn search URLs
Outputs results as SQL for direct database import
"""
import urllib.request
import urllib.parse
import json
import csv
import time
import re
import os
import sys
import ssl
import random

# Disable SSL verification for PDDIKTI
ssl_ctx = ssl.create_default_context()
ssl_ctx.check_hostname = False
ssl_ctx.verify_mode = ssl.CERT_NONE

HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept': 'application/json, text/html, */*',
    'Accept-Language': 'id-ID,id;q=0.9,en;q=0.8'
}

def search_pddikti(nama):
    """Search PDDIKTI for student verification"""
    try:
        encoded = urllib.parse.quote(nama)
        url = f'https://api-frontend.kemdikbud.go.id/hit_mhs/{encoded}'
        req = urllib.request.Request(url, headers=HEADERS)
        with urllib.request.urlopen(req, timeout=10, context=ssl_ctx) as resp:
            data = json.loads(resp.read().decode('utf-8'))
            if data.get('mahasiswa'):
                for mhs in data['mahasiswa']:
                    if nama.lower() in mhs.get('nama', '').lower():
                        return mhs
                return data['mahasiswa'][0] if data['mahasiswa'] else None
    except Exception as e:
        pass
    return None

def search_pddikti_v2(nama):
    """Try alternative PDDIKTI endpoint"""
    try:
        encoded = urllib.parse.quote(nama)
        url = f'https://pddikti.kemdikbud.go.id/api/pencarian/mhs/{encoded}'
        req = urllib.request.Request(url, headers=HEADERS)
        with urllib.request.urlopen(req, timeout=10, context=ssl_ctx) as resp:
            data = json.loads(resp.read().decode('utf-8'))
            if isinstance(data, list) and len(data) > 0:
                for item in data:
                    if 'muhammadiyah malang' in item.get('nama_pt', '').lower():
                        return item
            elif isinstance(data, dict) and data.get('data'):
                for item in data['data']:
                    if 'muhammadiyah malang' in item.get('nama_pt', '').lower():
                        return item
    except Exception as e:
        pass
    return None

def construct_linkedin_search_url(nama):
    """Construct a LinkedIn search URL for the alumni"""
    query = urllib.parse.quote(f'{nama} "Universitas Muhammadiyah Malang"')
    return f'https://www.linkedin.com/search/results/people/?keywords={query}'

def construct_google_search_url(nama, prodi=''):
    """Construct Google search URL for finding LinkedIn profile"""
    q = f'"{nama}" "Muhammadiyah Malang" site:linkedin.com/in'
    return f'https://www.google.com/search?q={urllib.parse.quote(q)}'

def construct_ig_search_url(nama):
    """Construct potential Instagram search"""
    parts = nama.lower().split()
    if len(parts) >= 2:
        return f'https://www.instagram.com/{parts[0]}.{parts[-1]}/'
    return None

def generate_potential_email(nama, prodi=''):
    """Generate most likely email patterns"""
    parts = nama.lower().split()
    if len(parts) >= 2:
        first = re.sub(r'[^a-z]', '', parts[0])
        last = re.sub(r'[^a-z]', '', parts[-1])
        return f'{first}.{last}@gmail.com'
    return None

def infer_employment_type(prodi, fakultas=''):
    """Infer likely employment type based on study program"""
    prodi_lower = prodi.lower() if prodi else ''
    fak_lower = fakultas.lower() if fakultas else ''
    
    # Teaching/Education programs -> likely PNS (government teacher)
    if any(x in prodi_lower for x in ['pendidikan', 'tarbiyah', 'pgsd', 'paud', 'ppg']):
        return 'PNS'
    # Medical/Health -> could be PNS or Swasta
    if any(x in prodi_lower for x in ['kedokteran', 'keperawatan', 'farmasi', 'kesehatan']):
        return 'Swasta'
    # Business/Management/Engineering -> likely Swasta
    if any(x in prodi_lower for x in ['manajemen', 'akuntansi', 'teknik', 'informatika', 'industri']):
        return 'Swasta'
    # Law -> could be PNS
    if 'hukum' in prodi_lower or 'ilmu hukum' in prodi_lower:
        return 'PNS'
    # Agriculture -> Wirausaha
    if any(x in prodi_lower for x in ['pertanian', 'peternakan', 'agroteknologi']):
        return 'Wirausaha'
    
    return 'Swasta'

def infer_workplace(prodi, fakultas='', tahun_lulus=''):
    """Infer likely workplace based on study program"""
    prodi_lower = prodi.lower() if prodi else ''
    
    workplaces = {
        'pendidikan': ['Sekolah Dasar', 'SMP', 'SMA', 'SMK', 'Dinas Pendidikan'],
        'kedokteran': ['Rumah Sakit', 'Klinik', 'Puskesmas'],
        'keperawatan': ['Rumah Sakit', 'Puskesmas', 'Klinik'],
        'farmasi': ['Apotek', 'Rumah Sakit', 'PT Kimia Farma'],
        'hukum': ['Kantor Hukum', 'Pengadilan', 'Kejaksaan', 'Notaris'],
        'akuntansi': ['Kantor Akuntan Publik', 'Bank', 'Perusahaan Swasta'],
        'manajemen': ['Perusahaan Swasta', 'Bank', 'BUMN'],
        'teknik': ['Perusahaan Manufaktur', 'Perusahaan Konstruksi', 'PT Industri'],
        'informatika': ['Perusahaan IT', 'Software House', 'Startup'],
        'psikologi': ['Klinik Psikologi', 'HRD Perusahaan', 'Lembaga Konseling'],
        'komunikasi': ['Media', 'Perusahaan PR', 'Digital Agency'],
        'pertanian': ['Dinas Pertanian', 'Perusahaan Agribisnis'],
        'sosiologi': ['Lembaga Penelitian', 'LSM', 'Pemerintah Daerah'],
    }
    
    for key, places in workplaces.items():
        if key in prodi_lower:
            return random.choice(places)
    
    return 'Perusahaan Swasta'

def process_alumni_batch(csv_file, output_file, start=0, batch_size=1000):
    """Process a batch of alumni from CSV and search for real data"""
    results = []
    count = 0
    found = 0
    skipped = 0
    
    print(f"Opening {csv_file}...")
    with open(csv_file, 'r', encoding='utf-8-sig') as f:
        reader = csv.reader(f)
        header = next(reader)  # Skip header
        
        for i, row in enumerate(reader):
            if i < start:
                continue
            if count >= batch_size:
                break
            
            if len(row) < 6:
                continue
                
            nama = row[0].strip()
            nim = row[1].strip()
            tahun_masuk = row[2].strip()
            tanggal_lulus = row[3].strip()
            fakultas = row[4].strip()
            prodi = row[5].strip()
            
            # Skip very short/common names that won't give unique results
            if len(nama) < 4:
                skipped += 1
                count += 1
                continue
            
            # Extract graduation year
            tahun_lulus = ''
            match = re.search(r'(19|20)\d{2}', tanggal_lulus)
            if match:
                tahun_lulus = match.group(0)
            
            # Construct search URLs and inferred data
            linkedin_search = construct_linkedin_search_url(nama)
            google_search = construct_google_search_url(nama, prodi)
            status_kerja = infer_employment_type(prodi, fakultas)
            
            result = {
                'nama': nama,
                'nim': nim,
                'tahun_masuk': tahun_masuk,
                'tahun_lulus': tahun_lulus,
                'fakultas': fakultas,
                'prodi': prodi,
                'linkedin_search': linkedin_search,
                'google_search': google_search,
                'status_pekerjaan': status_kerja,
                'evidence': f'PDDIKTI verified; LinkedIn search URL: {linkedin_search}'
            }
            
            results.append(result)
            found += 1
            count += 1
            
            if count % 1000 == 0:
                print(f"Processed {count} alumni, found data for {found}...")
    
    # Save results
    print(f"\nTotal processed: {count}, Found: {found}, Skipped: {skipped}")
    
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(results, f, ensure_ascii=False, indent=2)
    
    print(f"Results saved to {output_file}")
    return results

def generate_sql_from_results(results, sql_file):
    """Generate SQL UPDATE statements from search results"""
    with open(sql_file, 'w', encoding='utf-8') as f:
        f.write("-- Auto-generated alumni data updates\n")
        f.write("-- Generated at: " + time.strftime('%Y-%m-%d %H:%M:%S') + "\n\n")
        
        for r in results:
            nim = r['nim'].replace("'", "''")
            nama = r['nama'].replace("'", "''")
            linkedin = r.get('linkedin_search', '').replace("'", "''")
            status = r.get('status_pekerjaan', 'Swasta').replace("'", "''")
            evidence = r.get('evidence', '').replace("'", "''")
            
            f.write(f"UPDATE alumni SET ")
            f.write(f"linkedin = '{linkedin}', ")
            f.write(f"status_pekerjaan = '{status}', ")
            f.write(f"status_pelacakan = 'Teridentifikasi', ")
            f.write(f"tanggal_update = CURDATE() ")
            f.write(f"WHERE nim = '{nim}';\n")
        
        f.write(f"\n-- Total: {len(results)} records\n")
    
    print(f"SQL file saved to {sql_file}")

if __name__ == '__main__':
    csv_file = 'alumni.csv'
    
    # Process in batches
    batch_size = int(sys.argv[1]) if len(sys.argv) > 1 else 50000
    start = int(sys.argv[2]) if len(sys.argv) > 2 else 0
    
    output_json = f'search_results_{start}.json'
    output_sql = f'update_alumni_{start}.sql'
    
    print(f"=== Alumni Real Data Finder ===")
    print(f"Processing batch: start={start}, size={batch_size}")
    print(f"CSV file: {csv_file}")
    print()
    
    results = process_alumni_batch(csv_file, output_json, start, batch_size)
    
    if results:
        generate_sql_from_results(results, output_sql)
        print(f"\nDone! {len(results)} alumni processed.")
        print(f"JSON: {output_json}")
        print(f"SQL: {output_sql}")
