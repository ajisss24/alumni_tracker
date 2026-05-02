import urllib.request
import csv
import random

url = "https://docs.google.com/spreadsheets/d/1JepgHxbtFpfwAxUO3DjZd6-TOpvtCr2d/export?format=csv"
try:
    response = urllib.request.urlopen(url)
    lines = [l.decode('utf-8', errors='ignore') for l in response.readlines()]
    reader = list(csv.reader(lines))
    
    years = ['2020','2021','2022','2023','2024','2025']
    candidates = {y: [] for y in years}
    
    for row in reader:
        if len(row) > 3:
            tgl = str(row[3])
            for y in years:
                if y in tgl:
                    candidates[y].append(row)
                    break
                    
    selected = []
    for y in years:
        if candidates[y]:
            selected.extend(random.sample(candidates[y], min(2, len(candidates[y]))))
            
    if len(selected) < 10:
        flat = [item for sub in candidates.values() for item in sub]
        rem = [x for x in flat if x not in selected]
        selected.extend(random.sample(rem, 10 - len(selected)))
        
    with open("names.txt", "w", encoding="utf-8") as f:
        for i, r in enumerate(selected[:10]):
            f.write(f"[{i+1}] {r[0].strip()} | Lulus: {r[3]} | {r[4] if len(r)>4 else ''}\n")

except Exception as e:
    print("Error:", e)
