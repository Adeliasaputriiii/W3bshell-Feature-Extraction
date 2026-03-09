import pandas as pd

ast_df = pd.read_csv("ast_features.csv")
lexical_df = pd.read_csv("lexical_features.csv")
statistical_df = pd.read_csv("statistical_features.csv")

print("AST: ", len(ast_df))
print("Lexical: ", len(lexical_df))
print("Statistical: ", len(statistical_df))

for df in [ast_df, lexical_df, statistical_df]:
    df["filepath"] = df["filepath"].str.strip()

merged = ast_df.merge(lexical_df, on=["filepath", "label"], how="inner")
merged = merged.merge(statistical_df, on=["filepath", "label"], how="inner")
merged = merged[
    [c for c in merged.columns if c not in ["filepath","label"]]
    + ["filepath","label"]
]

print("Merged total: ", len(merged))

merged.to_csv("php_features_dataset.csv", index=False)
print("Merge Done")