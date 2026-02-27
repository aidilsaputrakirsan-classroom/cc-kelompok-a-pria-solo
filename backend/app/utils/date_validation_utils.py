import re
from typing import List, Optional

from num2words import num2words


class ExtractedInfo:
    def __init__(self, hari: str, tanggal: str, bulan: str, tahun: str,
                 tanggal_bracket: Optional[str] = None, full_text: str = "",
                 isValid: Optional[bool] = None):
        self.hari = hari
        self.tanggal = tanggal
        self.bulan = bulan
        self.tahun = tahun
        self.tanggal_bracket = tanggal_bracket
        self.full_text = full_text
        self.isValid = isValid


def preprocess_ocr_text(text: str) -> str:

    if not text:
        return text

    # Step 1: Hilangkan spasi berlebih di sekitar tanda kurung dan strip
    text = re.sub(r'\(\s+', '(', text)
    text = re.sub(r'\s+\)', ')', text)

    # Step 2: Sambungkan tanggal yang terpisah newline/whitespace dalam kurung
    pattern = re.compile(
        r'\(\s*'  # kurung buka dengan optional whitespace
        r'(\d{1,2})\s*[-/]\s*'  # tanggal (1-2 digit) + separator
        r'(\d{1,2})\s*[-/]\s*'  # bulan (1-2 digit) + separator
        r'(\d{2,4})'  # tahun (2-4 digit)
        r'\s*\)',  # kurung tutup dengan optional whitespace
        re.MULTILINE | re.DOTALL
    )

    def replacer(match):
        dd, mm, yyyy = match.groups()
        # Normalisasi: pastikan dd dan mm 2 digit, yyyy 4 digit
        dd = dd.zfill(2)
        mm = mm.zfill(2)
        if len(yyyy) == 2:
            yyyy = '20' + yyyy
        return f'({dd}-{mm}-{yyyy})'

    text = pattern.sub(replacer, text)

    # Step 3: Tangani kasus extreme
    extreme_pattern = re.compile(
        r'\(\s*'
        r'(\d{1,2})\s*'
        r'[-/]\s*'
        r'(\d{1,2})\s*'
        r'[-/]\s*'
        r'(\d{2,4})\s*'
        r'\)',
        re.DOTALL
    )

    text = extreme_pattern.sub(replacer, text)

    return text


def extract_all_days_and_dates(text: str) -> List[ExtractedInfo]:

    if not text:
        return []

    # Pra-proses teks untuk memperbaiki format tanggal
    text = preprocess_ocr_text(text)

    results = []

    # PATTERN 1: Format lengkap dengan kata "tanggal"
    pattern_full = re.compile(
        r'(?:pada\s+hari\s+ini[,\s]*|hari\s+ini[,\s]*|pada\s+hari\s+)?' +  # prefix opsional
        r'(Senin|Selasa|Rabu|Kamis|Jumat|Sabtu|Minggu)' +  # hari
        r'[,\s]*' +  # separator (support koma)
        r'tanggal\s+' +  # "tanggal" (WAJIB untuk pattern ini)
        r'([A-Za-z\s]+?)' +  # tanggal terbilang
        r'\s+bulan\s+' +  # "bulan"
        r'([A-Za-z\s]+?)' +  # bulan terbilang
        r'\s+tahun\s+' +  # "tahun"
        r'([A-Za-z\s]+?)' +  # tahun terbilang
        r'[,\s]*' +  # separator (support koma)
        r'\(?\s*' +  # kurung buka OPSIONAL
        r'(\d{1,2}[-/]\d{1,2}[-/]\d{2,4})' +  # tanggal numerik
        r'\s*\)?',  # kurung tutup OPSIONAL
        re.IGNORECASE
    )

    # PATTERN 2: Format TANPA kata "tanggal" (seperti Image 3)
    # "Pada Hari Sabtu Bulan Februari Tahun Dua Ribu Dua Puluh Lima (01/02/2025)"
    pattern_no_tanggal = re.compile(
        r'(?:pada\s+hari\s+ini[,\s]*|hari\s+ini[,\s]*|pada\s+hari\s+)?' +  # prefix opsional
        r'(Senin|Selasa|Rabu|Kamis|Jumat|Sabtu|Minggu)' +  # hari
        r'[,\s]*' +  # separator
        r'bulan\s+' +  # "bulan" (langsung tanpa "tanggal")
        r'([A-Za-z\s]+?)' +  # bulan terbilang
        r'\s+tahun\s+' +  # "tahun"
        r'([A-Za-z\s]+?)' +  # tahun terbilang
        r'[,\s]*' +  # separator
        r'\(?\s*' +  # kurung buka OPSIONAL
        r'(\d{1,2}[-/]\d{1,2}[-/]\d{2,4})' +  # tanggal numerik
        r'\s*\)?',  # kurung tutup OPSIONAL
        re.IGNORECASE
    )

    # Try Pattern 1 (dengan "tanggal")
    for match in pattern_full.finditer(text):
        hari = match.group(1).strip()
        tanggal = match.group(2).strip()
        bulan = match.group(3).strip()
        tahun = match.group(4).strip()
        tanggal_bracket = match.group(5).strip()

        # Bersihkan koma dari komponen tanggal
        hari = hari.replace(',', '').strip()
        tanggal = tanggal.replace(',', '').strip()
        bulan = bulan.replace(',', '').strip()
        tahun = tahun.replace(',', '').strip()

        # Standarisasi format tanggal (handle tahun 2 digit → 4 digit)
        tanggal_bracket_standardized = standarization_date_format(tanggal_bracket)

        results.append(ExtractedInfo(
            hari=hari,
            tanggal=tanggal,
            bulan=bulan,
            tahun=tahun,
            tanggal_bracket=tanggal_bracket_standardized,
            full_text=match.group(0).strip()
        ))

    # Try Pattern 2 (tanpa "tanggal" - extract dari bracket)
    for match in pattern_no_tanggal.finditer(text):
        hari = match.group(1).strip()
        bulan = match.group(2).strip()
        tahun = match.group(3).strip()
        tanggal_bracket = match.group(4).strip()

        # Bersihkan koma
        hari = hari.replace(',', '').strip()
        bulan = bulan.replace(',', '').strip()
        tahun = tahun.replace(',', '').strip()

        # Standarisasi format tanggal
        tanggal_bracket_standardized = standarization_date_format(tanggal_bracket)

        # Parse tanggal dari bracket untuk mendapatkan tanggal terbilang
        try:
            from datetime import datetime
            date_obj = datetime.strptime(tanggal_bracket_standardized, "%d-%m-%Y")
            tanggal_terbilang = angka_ke_teks_tanggal(date_obj.day)
        except:
            # Fallback: gunakan angka dari bracket
            tanggal_terbilang = tanggal_bracket_standardized.split("-")[0]

        results.append(ExtractedInfo(
            hari=hari,
            tanggal=tanggal_terbilang,  # Dari bracket
            bulan=bulan,
            tahun=tahun,
            tanggal_bracket=tanggal_bracket_standardized,
            full_text=match.group(0).strip()
        ))

    return results


dict_day = {
    'senin': 'monday',
    'selasa': 'tuesday',
    'rabu': 'wednesday',
    'kamis': 'thursday',
    'jumat': 'friday',
    'sabtu': 'saturday',
    'minggu': 'sunday'
}

dict_day_reverse = {
    'monday': 'senin',
    'tuesday': 'selasa',
    'wednesday': 'rabu',
    'thursday': 'kamis',
    'friday': 'jumat',
    'saturday': 'sabtu',
    'sunday': 'minggu'
}

def standarization_date_format(date: str) -> str:

    if not date:
        return date

    # Ganti slash dengan dash
    date = date.replace("/", "-").strip()

    # Split by dash
    parts = date.split("-")

    if len(parts) != 3:
        return date  # Invalid format, return as is

    dd, mm, yyyy = parts

    # Pad day and month to 2 digits
    dd = dd.zfill(2)
    mm = mm.zfill(2)

    # Convert 2-digit year to 4-digit
    if len(yyyy) == 2:
        year_int = int(yyyy)
        # Heuristic: 00-50 → 2000-2050, 51-99 → 1951-1999
        if year_int <= 50:
            yyyy = '20' + yyyy
        else:
            yyyy = '19' + yyyy

    # Ensure 4-digit year
    if len(yyyy) != 4:
        # If still not 4 digits, pad with leading zeros (edge case)
        yyyy = yyyy.zfill(4)

    return f"{dd}-{mm}-{yyyy}"


def normalize_text(text: str) -> str:

    if not text:
        return ""

    # Lowercase dan strip
    text = text.lower().strip()

    # Replace multiple spaces dengan single space
    text = ' '.join(text.split())

    # Remove trailing/leading punctuation
    text = text.strip('.,;:!?')

    return text


def angka_ke_teks_tanggal(angka: int) -> str:

    try:
        return num2words(angka, lang='id')
    except:
        return str(angka)


def angka_ke_teks_tahun(tahun: int) -> str:

    try:
        return num2words(tahun, lang='id')
    except:
        return str(tahun)


def bulan_angka_ke_teks(bulan: int) -> str:

    bulan_map = {
        1: "januari", 2: "februari", 3: "maret", 4: "april",
        5: "mei", 6: "juni", 7: "juli", 8: "agustus",
        9: "september", 10: "oktober", 11: "november", 12: "desember"
    }

    return bulan_map.get(bulan, str(bulan))


def extract_prefix_from_full_text(full_text: str) -> str:

    match = re.search(
        r'^(.*?)(senin|selasa|rabu|kamis|jumat|sabtu|minggu)',
        full_text.lower(),
        re.IGNORECASE
    )

    if match:
        prefix = match.group(1).strip()
        # Remove trailing comma
        return prefix.rstrip(',').strip()

    return "pada hari ini"


def generate_correction_text(hari: str, tanggal: int, bulan: int, tahun: int,
                             original_prefix: str = "pada hari ini") -> str:

    hari_lower = hari.lower()
    tanggal_terbilang = angka_ke_teks_tanggal(tanggal)
    bulan_terbilang = bulan_angka_ke_teks(bulan)
    tahun_terbilang = angka_ke_teks_tahun(tahun)

    # Gunakan prefix asli atau default
    if original_prefix and original_prefix.lower() not in ['pada', 'hari']:
        correction = f"{original_prefix} {hari_lower} tanggal {tanggal_terbilang} bulan {bulan_terbilang} tahun {tahun_terbilang}"
    else:
        correction = f"pada hari ini {hari_lower} tanggal {tanggal_terbilang} bulan {bulan_terbilang} tahun {tahun_terbilang}"

    return correction
