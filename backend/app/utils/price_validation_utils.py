import re
from difflib import SequenceMatcher
from typing import List, Optional, Dict


class ExtractedInfo:
    def __init__(self, angka: str, terbilang: str, nilai: float,
                 teks_normal: Optional[str] = None,
                 isValid: Optional[bool] = None,
                 full_text: Optional[str] = None):
        self.angka = angka
        self.terbilang = terbilang
        self.nilai = nilai
        self.teks_normal = teks_normal
        self.isValid = isValid
        self.full_text = full_text


def preprocess_ocr_rupiah(text: str) -> str:
    """Pra-proses teks OCR untuk memperbaiki line breaks dan formatting"""
    if not text:
        return text
    text = re.sub(r'([a-z])\s*\n\s*([a-z])', r'\1\2', text, flags=re.IGNORECASE)
    text = re.sub(r'([a-z]{2,})\s*\n\s*([a-z]{1,2})\b', r'\1\2', text, flags=re.IGNORECASE)
    return text


def similarity(word1: str, word2: str) -> float:
    """Hitung similarity antara 2 kata (0.0 - 1.0)"""
    return SequenceMatcher(None, word1.lower(), word2.lower()).ratio()


def has_strong_number_pattern(text: str) -> bool:
    """Deteksi apakah teks mengandung pola angka yang kuat (ribu, juta, miliar, dll)"""
    text_lower = text.lower()
    strong_indicators = [
        'ribu', 'juta', 'miliar', 'milyar', 'triliun', 'trilyun',
        'ratus', 'puluh', 'belas'
    ]
    has_strong = any(indicator in text_lower for indicator in strong_indicators)
    if not has_strong:
        return False

    basic_numbers = ['satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan']
    has_basic = any(num in text_lower for num in basic_numbers)
    return has_strong and has_basic


def calculate_whitelist_ratio(text: str) -> float:
    """Hitung rasio kata yang valid dalam konteks terbilang"""
    text_lower = text.lower()
    kata_valid = {
        'nol', 'satu', 'dua', 'tiga', 'empat', 'lima',
        'enam', 'tujuh', 'delapan', 'sembilan',
        'sepuluh', 'sebelas', 'belas',
        'puluh', 'ratus', 'seratus',
        'ribu', 'seribu', 'juta', 'sejuta', 'miliar', 'semiliar',
        'milyar', 'semilyar', 'triliun', 'setriliun', 'trilyun', 'setrilyun',
        'se', 'rupiah'
    }

    words = text_lower.split()
    if not words:
        return 0.0

    valid_count = sum(1 for word in words if word in kata_valid)
    return valid_count / len(words)


def is_likely_garbage(text: str) -> bool:
    """Deteksi teks sampah berdasarkan rasio whitelist"""
    ratio = calculate_whitelist_ratio(text)
    return ratio < 0.50


def is_valid_terbilang_balanced(text: str) -> Dict:
    """
    Validasi teks terbilang dengan keseimbangan antara ketat dan toleran.
    """
    text = normalisasi_teks(text)
    whitelist_ratio = calculate_whitelist_ratio(text)

    if is_likely_garbage(text):
        return {
            'is_valid': False,
            'confidence': 0.0,
            'stage': 'strict_reject',
            'reason': f'Whitelist ratio too low ({whitelist_ratio:.0%} < 50%)',
            'fuzzy_corrections': {}
        }

    if not has_strong_number_pattern(text):
        return {
            'is_valid': False,
            'confidence': 0.0,
            'stage': 'strict_reject',
            'reason': 'No strong number pattern (ribu/juta/miliar/ratus/puluh)',
            'fuzzy_corrections': {}
        }

    kata_valid = {
        'nol', 'satu', 'dua', 'tiga', 'empat', 'lima',
        'enam', 'tujuh', 'delapan', 'sembilan',
        'sepuluh', 'sebelas', 'belas',
        'puluh', 'ratus', 'seratus',
        'ribu', 'seribu', 'juta', 'sejuta', 'miliar', 'semiliar',
        'milyar', 'semilyar', 'triliun', 'setriliun', 'trilyun', 'setrilyun',
        'se', 'rupiah'
    }

    words = text.split()
    if not words:
        return {
            'is_valid': False,
            'confidence': 0.0,
            'stage': 'strict_reject',
            'reason': 'Empty text',
            'fuzzy_corrections': {}
        }

    FUZZY_THRESHOLD = 0.75
    MIN_CONFIDENCE = 0.75

    matched_words = 0
    fuzzy_corrections = {}
    unmatched_words = []

    for word in words:
        if word in kata_valid:
            matched_words += 1
            continue

        best_match = None
        best_score = 0.0
        for valid_word in kata_valid:
            score = similarity(word, valid_word)
            if score > best_score:
                best_score = score
                best_match = valid_word

        if best_score >= FUZZY_THRESHOLD:
            matched_words += 1
            fuzzy_corrections[word] = {
                'suggestion': best_match,
                'confidence': best_score
            }
        else:
            unmatched_words.append(word)

    total_words = len(words)
    confidence = matched_words / total_words if total_words > 0 else 0.0
    is_valid = confidence >= MIN_CONFIDENCE

    kata_angka_inti = kata_valid - {'rupiah', 'se'}
    has_angka = any(
        word in kata_angka_inti or
        (word in fuzzy_corrections and fuzzy_corrections[word]['suggestion'] in kata_angka_inti)
        for word in words
    )

    if not has_angka:
        is_valid = False

    return {
        'is_valid': is_valid,
        'confidence': confidence,
        'stage': 'fuzzy_accept' if is_valid else 'fuzzy_reject',
        'reason': f"Confidence {confidence:.0%} ({'>=75%' if is_valid else '<75%'})",
        'fuzzy_corrections': fuzzy_corrections,
        'unmatched_words': unmatched_words
    }


def method_compiled_find_all(text: str) -> List[ExtractedInfo]:
    """
    Ekstraksi nilai Rupiah beserta terbilangnya dengan validasi seimbang.
    """
    text = preprocess_ocr_rupiah(text)
    compiled_regex = re.compile(
        r'Rp\.?\s*'
        r'([\d.,]+)'
        r',?-?\s*'
        r'\('
        r'([^)]+)'
        r'\)',
        re.IGNORECASE
    )

    results = []

    for match in compiled_regex.finditer(text):
        angka_raw = match.group(1)
        terbilang_raw = match.group(2).strip()
        terbilang = re.sub(r'\s+', ' ', terbilang_raw)

        validation_result = is_valid_terbilang_balanced(terbilang)
        if not validation_result['is_valid']:
            continue

        angka_clean = angka_raw.replace('.', '').replace(',', '.')
        try:
            nilai = float(angka_clean)
            angka_formatted = format_angka(int(nilai))
            results.append(ExtractedInfo(
                angka=angka_formatted,
                terbilang=terbilang.lower(),
                nilai=nilai,
                isValid=True,
                full_text=match.group(0)
            ))
        except ValueError:
            continue

    return results


def format_angka(nilai: int) -> str:
    """Format angka dengan titik pemisah ribuan"""
    return f"{nilai:,}".replace(",", ".")


def normalisasi_teks(teks: str) -> str:
    """Normalisasi teks (lowercase, tanpa tanda baca, spasi rapi)"""
    return re.sub(r'\s+', ' ', re.sub(r'[^\w\s]', ' ', teks.lower())).strip()
