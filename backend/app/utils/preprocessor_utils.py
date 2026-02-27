import copy
import os
import re
from typing import List, Dict, Any

from dotenv import load_dotenv

load_dotenv()
TOTAL_CHUNKS = int(os.getenv("CHUNK_SIZE"))


def preprocess_ocr_indonesian(text):
    if not text:
        return text

    cleaned = text

    # Remove replacement character (biasa muncul dari encoding errors)
    cleaned = cleaned.replace('�', '')

    # Remove zero-width characters
    cleaned = re.sub(r'[\u200B-\u200D\uFEFF]', '', cleaned)

    # Remove control characters (except \n, \r, \t)
    cleaned = re.sub(r'[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]', '', cleaned)

    # Step 2: Word-level filtering (more permissive)
    words = cleaned.split()
    cleaned_words = []

    for word in words:
        # Skip jika word kosong atau hanya whitespace
        if not word or word.isspace():
            continue

        # Skip jika word terdiri HANYA dari karakter aneh (misalnya: "||||", "~~~~")
        # Tapi keep jika ada huruf/angka
        if re.match(r'^[^\w\s]+$', word, re.UNICODE):
            # Word hanya special characters, tapi biarkan jika common punctuation
            if word in ['...', '--', '—', '–', '/', '|', '-', '+', '=', '&', '#', '%', '@']:
                cleaned_words.append(word)
            # Skip lainnya
            continue

        # Keep semua word yang mengandung huruf atau angka (termasuk Unicode)
        cleaned_words.append(word)

    # Step 3: Join dan clean whitespace
    cleaned_text = ' '.join(cleaned_words)
    cleaned_text = re.sub(r'\s+', ' ', cleaned_text).strip()

    return cleaned_text


def is_likely_document_code(word: str) -> bool:
    """Deteksi apakah kata adalah bagian dari nomor surat/kode dokumen."""
    code_patterns = [
        r'[A-Z0-9]+-[A-Z0-9]+/[A-Za-z]+',
        r'[A-Z]+\d+',
        r'\d+-\d+-\d+',
        r'[A-Z]+/[A-Z]+/[A-Z]+',
    ]
    return any(re.search(pattern, word) for pattern in code_patterns)


def clean_typo_results(typos: List[Dict]) -> List[Dict]:
    """
    Post-processing untuk membersihkan hasil typo dari false positive.
    """
    common_abbreviations = {
        'NIK', 'KTP', 'NPWP', 'BAPD', 'BAPL', 'BAPP', 'BAST',
        'KAB', 'KOTA', 'PROV', 'RT', 'RW', 'PIC', 'CV', 'PT',
        'UD', 'PD', 'BUMD', 'BUMN', 'SOP', 'SPK', 'PO', 'DO',
        'GRN', 'SDA', 'BES', 'BJM'
    }

    cleaned_typos = []
    for typo in typos:
        typo_word = typo.get('typo_word', '').strip()
        correction_word = typo.get('correction_word', '').strip()

        if not typo_word or not correction_word:
            continue
        if typo_word == correction_word:
            continue
        if typo_word.lower() == correction_word.lower():
            continue
        if is_likely_document_code(typo_word):
            continue
        if typo_word.upper() in common_abbreviations:
            continue
        if typo_word.rstrip(':;,.-') == correction_word:
            continue

        cleaned_typos.append(typo)

    return cleaned_typos


def convert_page_to_universal(
        data: List[Dict[str, Any]],
        filepath: str,
        doc_type: str = "non-typo",
        pages_per_file: int = TOTAL_CHUNKS
) -> List[Dict[str, Any]]:
    try:
        count = int(filepath.split('_')[-1].replace('.pdf', '')) - 1
    except ValueError:
        count = 0

    offset = count * pages_per_file
    if not data:
        return []

    result = copy.deepcopy(data)
    for obj in result:
        if doc_type.lower() == "typo":
            if 'page' in obj and isinstance(obj['page'], int):
                obj['page'] += offset
        else:
            boxes = obj.get('bounding_box')
            if isinstance(boxes, list):
                for bbox in boxes:
                    if 'page' in bbox and isinstance(bbox['page'], int):
                        bbox['page'] += offset
    return result