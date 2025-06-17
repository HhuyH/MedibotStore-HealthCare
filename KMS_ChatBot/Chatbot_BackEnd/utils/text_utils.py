# utils/text_utils.py
import re, unicodedata
# hoặc: from unidecode import unidecode

def normalize_text(text: str) -> str:
    # nếu muốn vừa loại dấu vừa loại ký tự không phải chữ/số
    text = text.lower()
    text = unicodedata.normalize("NFD", text)
    text = "".join(c for c in text if unicodedata.category(c) != "Mn")
    text = re.sub(r"[^\w\s]", "", text)
    text = re.sub(r"\s+", " ", text).strip()
    return text

# --- chỉ bỏ dấu và lowercase ---
# def normalize_text(text: str) -> str:
#     return unidecode(text).lower().strip()
