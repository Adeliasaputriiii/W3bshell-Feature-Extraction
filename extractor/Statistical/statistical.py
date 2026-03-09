import math
import base64
import zlib
import re
import pandas as pd
from collections import Counter

def safe_decode_base64(text):
    try:
        return base64.b64decode(text).decode("utf-8", errors="ignore")
    except Exception:
        return ""

def calculate_entropy(text):
    if not text:
        return 0.0
    counter = Counter(text)
    length = len(text)
    return -sum(
        (count / length) * math.log2(count / length)
        for count in counter.values()
    )


def compression_ratio(text):
    if not text:
        return 0.0
    compressed = zlib.compress(text.encode("utf-8"))
    return len(compressed) / len(text.encode("utf-8"))


def longest_string_literal(text):
    strings = re.findall(r"(\".*?\"|\'.*?\')", text)
    return max((len(s) for s in strings), default=0)


def extract_statistical_features(php_code):
    length = len(php_code)

    if length == 0:
        return {
            "stat_entropy": 0,
            "stat_length": 0,
            "stat_unique_char_ratio": 0,
            "stat_digit_ratio": 0,
            "stat_symbol_ratio": 0,
            "stat_longest_string": 0,
            "stat_compression_ratio": 0
        }

    return {
        "stat_entropy": calculate_entropy(php_code),
        "stat_length": length,
        "stat_unique_char_ratio": len(set(php_code)) / length,
        "stat_digit_ratio": sum(c.isdigit() for c in php_code) / length,
        "stat_symbol_ratio": sum(not c.isalnum() for c in php_code) / length,
        "stat_longest_string": longest_string_literal(php_code),
        "stat_compression_ratio": compression_ratio(php_code)
    }

def statistical_from_csv(input_csv, output_csv):
    df = pd.read_csv(input_csv)
    rows = []

    for _, row in df.iterrows():
        code = safe_decode_base64(row["processed_code_b64"])
        feats = extract_statistical_features(code)

        feats["filepath"] = row["filepath"]
        feats["label"] = row["label"]

        rows.append(feats)

    df_features = pd.DataFrame(rows)
    df_features.to_csv(output_csv, index=False)

    print("statistical feature extraction selesai")
    print("Total file:", len(df_features))


if __name__ == "__main__":
    statistical_from_csv(
        input_csv= "../../processed_dataset.csv",
        output_csv="../../extraction-result/php_statistical_features.csv"
    )


