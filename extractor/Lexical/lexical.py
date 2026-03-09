import pandas as pd
import base64
import re

DANGEROUS_FUNCS = [
    "eval", "assert", "system", "exec","shell_exec", "passthru", "popen", "proc_open"
]

OBFUSCATION_FUNCS = [
    "base64_decode", "gzinflate","gzuncompress", "str_rot13"
]

USER_INPUTS = [
    r"\$_GET", r"\$_POST", r"\$_REQUEST", r"\$_COOKIE", r"\$_FILES"
]

INCLUDE_FUNCS = [
    "include", "include_once","require", "require_once"
]

STRING_PATTERN = r"(\".*?\"|\'.*?\')"
SYMBOL_PATTERN = r"[{}\[\]\(\);$]"


def safe_decode_base64(text):
    try:
        return base64.b64decode(text).decode("utf-8", errors="ignore")
    except Exception:
        return ""


def count_regex(pattern, text):
    return len(re.findall(pattern, text, flags=re.IGNORECASE))


def extract_lexical_features(code):
    features = {}

    for f in DANGEROUS_FUNCS:
        features[f"lex_func_{f}"] = count_regex(rf"\b{f}\b", code)

    for f in OBFUSCATION_FUNCS:
        features[f"lex_obfus_{f}"] = count_regex(rf"\b{f}\b", code)

    for var in USER_INPUTS:
        clean_name = var.replace("\\", "").replace("$", "")
        features[f"lex_input_{clean_name}"] = count_regex(var, code)

    for inc in INCLUDE_FUNCS:
        features[f"lex_include_{inc}"] = count_regex(rf"\b{inc}\b", code)

    features["lex_string_literal_count"] = count_regex(STRING_PATTERN, code)
    
    features["lex_symbol_count"] = count_regex(SYMBOL_PATTERN, code)

    return features



def lexical_from_csv(input_csv, output_csv):
    df = pd.read_csv(input_csv)
    rows = []

    for _, row in df.iterrows():
        code = safe_decode_base64(row["processed_code_b64"])
        feats = extract_lexical_features(code)

        feats["filepath"] = row["filepath"]
        feats["label"] = row["label"]

        rows.append(feats)

    df_features = pd.DataFrame(rows)
    df_features.to_csv(output_csv, index=False)

    print("Lexical feature extraction selesai")
    print("Total file:", len(df_features))


if __name__ == "__main__":
    lexical_from_csv(
        input_csv= "../../processed_dataset.csv",
        output_csv="../../extraction-result/php_lexical_features.csv"
    )
