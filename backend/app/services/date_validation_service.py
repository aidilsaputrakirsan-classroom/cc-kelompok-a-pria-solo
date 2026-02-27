from datetime import datetime
from typing import List, Dict

from app.services.ocr_service import find_bounding_box_with_context
from app.utils.date_validation_utils import (
    extract_all_days_and_dates,
    dict_day_reverse,
    standarization_date_format,
    normalize_text,
    angka_ke_teks_tanggal,
    angka_ke_teks_tahun,
    bulan_angka_ke_teks,
    extract_prefix_from_full_text,
    generate_correction_text
)


def validate_text_dates(text_dictionary: Dict) -> List[Dict]:

    text = text_dictionary['text_content'].lower()
    paragraphs_data = text_dictionary.get('paragraphs_data', [])
    results = []

    extracted_infos = extract_all_days_and_dates(text)

    for info in extracted_infos:
        if not info.tanggal_bracket:
            continue

        try:
            # === PARSE BRACKET DATE ===
            standart_date = standarization_date_format(info.tanggal_bracket)
            tanggal_obj = datetime.strptime(standart_date, "%d-%m-%Y")

            # === KONVERSI DATETIME → TEKS MENGGUNAKAN num2words ===
            # Ini adalah "expected text" berdasarkan tanggal di bracket
            expected_hari_eng = tanggal_obj.strftime("%A").lower()
            expected_hari_indo = dict_day_reverse.get(expected_hari_eng, expected_hari_eng)

            expected_tanggal_text = angka_ke_teks_tanggal(tanggal_obj.day)
            expected_bulan_text = bulan_angka_ke_teks(tanggal_obj.month)
            expected_tahun_text = angka_ke_teks_tahun(tanggal_obj.year)

            # === NORMALISASI TEKS (dari OCR dan dari num2words) ===
            # Ini penting untuk handling whitespace, case sensitivity, dll
            ocr_hari = normalize_text(info.hari)
            ocr_tanggal = normalize_text(info.tanggal)
            ocr_bulan = normalize_text(info.bulan)
            ocr_tahun = normalize_text(info.tahun)

            expected_hari = normalize_text(expected_hari_indo)
            expected_tanggal = normalize_text(expected_tanggal_text)
            expected_bulan = normalize_text(expected_bulan_text)
            expected_tahun = normalize_text(expected_tahun_text)

            # === VALIDASI DENGAN STRING COMPARISON ===
            is_valid_hari = (ocr_hari == expected_hari)
            is_valid_tanggal = (ocr_tanggal == expected_tanggal)
            is_valid_bulan = (ocr_bulan == expected_bulan)
            is_valid_tahun = (ocr_tahun == expected_tahun)

            is_valid = is_valid_hari and is_valid_tanggal and is_valid_bulan and is_valid_tahun

            bounding_box = []

            if not is_valid and paragraphs_data:
                try:
                    full_context = info.full_text
                    bbox_results = find_bounding_box_with_context(
                        paragraphs_data=paragraphs_data,
                        full_context=full_context,
                        target_numeric=info.tanggal_bracket,
                        fuzzy_threshold=0.7
                    )

                    # Process LIST of results
                    for bbox_result in bbox_results:
                        if bbox_result.get('found'):
                            bounding_box.append({
                                'page': bbox_result['page'],
                                'word': info.tanggal_bracket,
                                'bbox': bbox_result['bbox'],
                                'page_in_file': bbox_result.get('page_in_file', bbox_result['page'])
                            })

                    if len(bounding_box) > 1:
                        print(f"Info: Ditemukan {len(bounding_box)} kemunculan untuk tanggal {info.tanggal_bracket}")

                except Exception as e:
                    print(f"Warning: Gagal mencari bounding box: {e}")
                    pass

            # === HASIL ===
            result = {
                "full_text": info.full_text,
                "tanggal_bracket": tanggal_obj.strftime("%Y-%m-%d"),
                "is_valid": is_valid,
                "bounding_box": bounding_box
            }

            # === CORRECTION: Generate HANYA jika invalid ===
            if not is_valid:
                # Generate correction dalam format terbilang lengkap (TANPA bracket)
                # Menggunakan tanggal dari bracket sebagai sumber kebenaran
                correction = generate_correction_text(
                    hari=dict_day_reverse.get(expected_hari_eng, expected_hari_eng).capitalize(),
                    tanggal=tanggal_obj.day,
                    bulan=tanggal_obj.month,
                    tahun=tanggal_obj.year,
                    original_prefix=extract_prefix_from_full_text(info.full_text)
                )
                result["correction"] = correction

            results.append(result)

        except Exception as e:
            print(f"Error: Gagal validasi tanggal: {e}")
            import traceback
            traceback.print_exc()
            continue

    return results
