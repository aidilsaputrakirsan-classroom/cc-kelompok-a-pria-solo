from typing import List, Dict
from app.services.ocr_service import find_bounding_box_with_context
from app.utils.price_validation_utils import method_compiled_find_all
from num2words import num2words


def validate_text_prices(text_dictionary: Dict) -> List[Dict]:

    text = text_dictionary['text_content'].lower()
    paragraphs_data = text_dictionary.get('paragraphs_data', [])
    results = []

    extracted_infos = method_compiled_find_all(text)

    for info in extracted_infos:
        try:
            # Validasi angka vs terbilang
            ground_truth = num2words(int(info.nilai), lang='id') + " rupiah"
            terbilang = info.terbilang
            is_valid = terbilang.strip() == ground_truth.strip()

            # OPTIMASI: Bounding box HANYA dicari jika INVALID
            bounding_box = []

            if not is_valid and paragraphs_data and info.angka:
                try:
                    # Gunakan find_bounding_box_with_context untuk akurasi tinggi
                    # UPDATED: Sekarang mengembalikan LIST of matches
                    full_context = getattr(info, 'full_text', None)

                    if not full_context:
                        # Fallback: reconstruct dari info
                        full_context = f"Rp {info.angka} ({info.terbilang})"

                    # Cari SEMUA bounding box dengan context (bisa multiple matches)
                    bbox_results = find_bounding_box_with_context(
                        paragraphs_data=paragraphs_data,
                        full_context=full_context,
                        target_numeric=info.angka,  # "1.076.088.864" atau "1076088864"
                        fuzzy_threshold=0.7
                    )

                    # UPDATED: Process LIST of results
                    for bbox_result in bbox_results:
                        if bbox_result.get('found'):
                            # Convert ke format yang kompatibel dengan frontend
                            bounding_box.append({
                                'page': bbox_result['page'],
                                'word': info.angka,
                                'bbox': bbox_result['bbox'],
                                'paragraph_id': bbox_result.get('paragraph_id'),  # untuk debugging
                                'matched_words_count': bbox_result.get('matched_words_count')  # untuk debugging
                            })
                        else:
                            # Log untuk debugging (hanya jika semua hasil tidak found)
                            if not bounding_box:  # Log sekali saja
                                print(f"Warning: Bounding box tidak ditemukan untuk harga {info.angka}")
                                print(f"  Full context: {full_context[:100]}...")
                                print(f"  Error: {bbox_result.get('error', 'Unknown')}")


                except Exception as e:
                    # Silent fail - bounding box tidak kritis
                    print(f"Warning: Gagal mencari bounding box untuk harga: {e}")
                    pass

            # Minimal result
            result = {
                "extracted_text": f"Rp {info.angka} ({info.terbilang})",
                "is_valid": is_valid,
                "bounding_box": bounding_box  # SEKARANG BISA LIST KOSONG ATAU MULTIPLE ITEMS
            }

            # Hanya tambahkan nilai jika tidak valid (untuk correction)
            if not is_valid:
                result["false_terbilang"] = terbilang
                result["correct_terbilang"] = ground_truth

            results.append(result)

        except Exception as e:
            # Silent fail untuk error conversion
            print(f"Warning: Gagal validasi harga: {e}")
            results.append({
                "extracted_text": f"Rp {getattr(info, 'angka', 'N/A')} ({getattr(info, 'terbilang', 'N/A')})",
                "is_valid": False,
                "bounding_box": []
            })

    return results
