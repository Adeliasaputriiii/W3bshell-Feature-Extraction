import os
import shutil
import hashlib

INPUT_DIRS = {
    "normal": "./Raw-php/Normal/",
    "webshell": "./Raw-php/Webshell/"
}

OUTPUT_BASE = "./Cleaned-Dataset"
OUTPUT_DIRS = {
    "normal": os.path.join(OUTPUT_BASE, "Normal"),
    "webshell": os.path.join(OUTPUT_BASE, "Webshell")
}

ALLOWED_EXT = ".php"
SKIP_DIRS = {".git", ".github", "__MACOSX"}

for d in OUTPUT_DIRS.values():
    os.makedirs(d, exist_ok=True)

def file_hash(path):
    h = hashlib.sha256()
    with open(path, "rb") as f:
        for chunk in iter(lambda: f.read(8192), b""):
            h.update(chunk)
    return h.hexdigest()

def safe_copy(src, dst_dir, filename):
    base, ext = os.path.splitext(filename)
    dst_path = os.path.join(dst_dir, filename)
    counter = 1

    while os.path.exists(dst_path):
        dst_path = os.path.join(dst_dir, f"{base}_{counter}{ext}")
        counter += 1

    shutil.copy2(src, dst_path)

def clean_and_collect():
    seen_hashes = set()
    stats = {
        "non_php": 0,
        "duplicate": 0,
        "copied": 0
    }

    for label, root_dir in INPUT_DIRS.items():
        output_dir = OUTPUT_DIRS[label]

        for dirpath, dirnames, files in os.walk(root_dir):
            dirnames[:] = [d for d in dirnames if d not in SKIP_DIRS]

            for file in files:
                full_path = os.path.join(dirpath, file)

                # Lewati non-PHP
                if not file.lower().endswith(ALLOWED_EXT):
                    stats["non_php"] += 1
                    continue

                try:
                    h = file_hash(full_path)
                except Exception as e:
                    print(f"[ERROR] Hash gagal {full_path}: {e}")
                    continue

                # Lewati duplikat isi
                if h in seen_hashes:
                    stats["duplicate"] += 1
                    continue

                seen_hashes.add(h)
                safe_copy(full_path, output_dir, file)
                stats["copied"] += 1

    print("=== HASIL PEMBERSIHAN DATASET ===")
    print(f"File non-PHP dilewati : {stats['non_php']}")
    print(f"File duplikat diabaikan: {stats['duplicate']}")
    print(f"File PHP tersimpan    : {stats['copied']}")

if __name__ == "__main__":
    clean_and_collect()
