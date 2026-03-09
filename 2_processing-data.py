import os
import base64
import pandas as pd

# -------- CONFIG --------
ROOT_DIRS = {
    "normal": "./Cleaned-Dataset/Normal/",
    "webshell": "./Cleaned-Dataset/Webshell/"
}

OUTPUT_CSV = "processed_dataset.csv"


def load_file(path):
    with open(path, "rb") as f:   
        return f.read()


def safe_encode(binary_data):
    return base64.b64encode(binary_data).decode("utf-8")


def process_all_dirs(root_dirs):
    rows = []

    for label, root in root_dirs.items():
        if not os.path.exists(root):
            print(f"[WARNING] Folder tidak ditemukan: {root}")
            continue

        for dirpath, _, files in os.walk(root):
            for filename in files:

                if not filename.lower().endswith(".php"):
                    continue

                fullpath = os.path.join(dirpath, filename)

                try:
                    raw_binary = load_file(fullpath)
                    encoded_code = safe_encode(raw_binary)
                except Exception as e:
                    print(f"[ERROR] Gagal proses {fullpath}: {e}")
                    continue

                rows.append({
                    "filepath": fullpath,
                    "label": label,
                    "processed_code_b64": encoded_code
                })

    return pd.DataFrame(rows)


if __name__ == "__main__":
    df = process_all_dirs(ROOT_DIRS)
    df.to_csv(OUTPUT_CSV, index=False)

    print("Processing selesai")
    print("Total file:", len(df))
    print(df["label"].value_counts())