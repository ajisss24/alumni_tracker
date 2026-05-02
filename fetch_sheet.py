import urllib.request
import csv

url = "https://docs.google.com/spreadsheets/d/1JepgHxbtFpfwAxUO3DjZd6-TOpvtCr2d/export?format=csv"
try:
    response = urllib.request.urlopen(url)
    lines = [l.decode('utf-8') for l in response.readlines()]
    reader = csv.reader(lines)
    
    with open("names.txt", "w", encoding="utf-8") as f:
        count = 0
        for row in reader:
            if not row: continue
            if count > 0 and count <= 15:
                # Name is usually the first or second column, let's just write the whole row 
                # so we can parse it manually
                f.write(", ".join(row) + "\n")
            count += 1
    print("Done")
except Exception as e:
    print("Error:", e)
