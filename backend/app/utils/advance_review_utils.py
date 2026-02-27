import json
import re
from typing import Dict, Any, Optional, List
from num2words import num2words


def extract_json_from_llm_response(response_text: str) -> Dict[str, Any]:

    text = response_text.strip()

    # Strategy 1: Coba parse langsung
    try:
        parsed = json.loads(text)
        if isinstance(parsed, dict):
            return parsed
    except json.JSONDecodeError:
        pass

    # Strategy 2: Ekstrak dari markdown code blocks
    # Pattern: ```json\n{...}\n```
    if '```json' in text:
        try:
            json_text = text.split('```json')[1].split('```')[0].strip()
            parsed = json.loads(json_text)
            if isinstance(parsed, dict):
                return parsed
        except (IndexError, json.JSONDecodeError):
            pass

    # Pattern: ```\n{...}\n```
    if '```' in text:
        try:
            json_text = text.split('```')[1].split('```')[0].strip()
            parsed = json.loads(json_text)
            if isinstance(parsed, dict):
                return parsed
        except (IndexError, json.JSONDecodeError):
            pass

    # Strategy 3: Gunakan regex untuk menemukan JSON object
    # Pattern untuk nested JSON objects
    patterns = [
        # Nested JSON with proper structure
        r'\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}',
        # Simpler JSON pattern
        r'\{.*?\}',
    ]

    for pattern in patterns:
        matches = re.findall(pattern, text, re.DOTALL)
        # Coba dari match terpanjang ke terpendek
        for match in sorted(matches, key=len, reverse=True):
            try:
                parsed = json.loads(match)
                if isinstance(parsed, dict):
                    return parsed
            except json.JSONDecodeError:
                continue

    # Jika semua strategi gagal
    raise ValueError(
        "Tidak dapat mengekstrak JSON valid dari response LLM. "
        f"Response snippet: {text[:200]}..."
    )


def validate_review_structure(
        data: Dict[str, Any],
        doc_type: str
) -> tuple[bool, Optional[str]]:
    """
    Validasi struktur hasil REVIEW dari LLM sesuai dengan schema yang diharapkan
    untuk masing-masing tipe dokumen.

    SEMUA document types sekarang menggunakan STAGE (konsisten).
    Setiap stage harus memiliki:
    - "review": string (1 kalimat deskripsi)
    - "keterangan": string (narasi detail hasil validasi)

    Args:
        data (Dict[str, Any]): Data hasil review dari LLM
        doc_type (str): Tipe dokumen yang direview

    Returns:
        tuple[bool, Optional[str]]:
            - bool: True jika valid, False jika tidak valid
            - str: Pesan error jika tidak valid, None jika valid
    """
    doc_type = doc_type.upper()

    # Cek apakah data adalah dict
    if not isinstance(data, dict):
        return False, f"Data bukan dictionary, tipe: {type(data)}"

    # Define expected structure per document type
    # SEKARANG SEMUA PAKAI "stages" (konsisten!)
    expected_structures = {
        "PR": {
            "stages": [
                "stage_1_uraian",
                "stage_2_net_price",
                "stage_3_tot_value",
                "stage_4_total_pr",
                "stage_5_month_sequence",
                "stage_6_net_price_consistency",
                "stage_7_tot_value_consistency",
                "stage_8_net_equals_tot",
                "stage_9_total_calculation"
            ]
        },
        "PO": {
            "stages": [
                "stage_1_nomor_kontrak",
                "stage_2_tanggal_kontrak",
                "stage_3a_description_month",
                "stage_3b_quantity",
                "stage_3c_price_consistency",
                "stage_4_amount_total",
                "stage_5a_count_presence",
                "stage_5b_nomor_kontrak_consistency",
                "stage_5c_tanggal_kontrak_consistency",
                "stage_5d_amount_consistency"
            ]
        },
        "GRN": {
            "stages": [
                "stage_1_nama_material_month",
                "stage_2_harga_satuan",
                "stage_3_jumlah_harga",
                "stage_4_total_sebelum_ppn",
                "stage_5a_count_presence",
                "stage_5b_month_sequence_consistency",
                "stage_5c_harga_satuan_consistency",
                "stage_5d_total_sebelum_ppn_consistency"
            ]
        },
        "NPK": {
            "stages": [
                "stage_1_nama_cc",
                "stage_2_no_kl_sp_wo",
                "stage_3_uraian",
                "stage_4_sid",
                "stage_5_periode",
                "stage_6_nilai",
                "stage_7_total_hak_mitra"
            ]
        },
        "SPB": {
            "stages": [
                "stage_1_judul_project",
                "stage_2_periode",
                "stage_3_nomor_kontrak",
                "stage_4_tanggal_kontrak",
                "stage_5_total_pembayaran",
                "stage_6_detail_rekening"
            ]
        },
        "INVOICE": {
            "stages": [
                # TODO: Define stages untuk INVOICE
                # Sementara empty, akan diisi sesuai prompt INVOICE
            ]
        },
        "KWITANSI": {
            "stages": [
                # TODO: Define stages untuk KWITANSI
                # Sementara empty, akan diisi sesuai prompt KWITANSI
            ]
        },
        "FAKTUR_PAJAK": {
            "stages": [
                # TODO: Define stages untuk FAKTUR_PAJAK
                # Sementara empty, akan diisi sesuai prompt FAKTUR_PAJAK
            ]
        },
    }

    # Cek apakah doc_type didukung
    if doc_type not in expected_structures:
        # Untuk document types yang belum ada template-nya
        # Return True tapi dengan warning log
        return True, None

    expected = expected_structures[doc_type]
    all_expected_keys = expected.get("stages", [])

    # Jika stages masih kosong (belum didefinisikan), skip validation
    if not all_expected_keys:
        return True, None

    # Cek keberadaan semua required keys
    missing_keys = [key for key in all_expected_keys if key not in data]
    if missing_keys:
        return False, f"Missing required keys: {', '.join(missing_keys)}"

    # Validasi struktur setiap key (harus punya "review" dan "keterangan")
    for key in all_expected_keys:
        field_data = data[key]

        # Setiap field harus dict
        if not isinstance(field_data, dict):
            return False, f"{key} bukan dict, tipe: {type(field_data)}"

        # Harus punya "review" dan "keterangan"
        if "review" not in field_data:
            return False, f"{key} missing 'review' field"
        if "keterangan" not in field_data:
            return False, f"{key} missing 'keterangan' field"

        # Validasi tipe data
        if not isinstance(field_data["review"], str):
            return False, f"{key}.review harus string, tipe: {type(field_data['review'])}"
        if not isinstance(field_data["keterangan"], str):
            return False, f"{key}.keterangan harus string, tipe: {type(field_data['keterangan'])}"

        # Validasi content tidak kosong
        if not field_data["review"].strip():
            return False, f"{key}.review tidak boleh kosong"
        if not field_data["keterangan"].strip():
            return False, f"{key}.keterangan tidak boleh kosong"

    # Cek apakah ada extra keys yang tidak expected (optional warning)
    extra_keys = [key for key in data.keys() if key not in all_expected_keys]
    if extra_keys:
        # Ini bukan error fatal, tapi bisa di-log sebagai warning
        # Untuk sekarang kita abaikan extra keys
        pass

    return True, None


def validate_review_content_quality(
        data: Dict[str, Any],
        doc_type: str,
        min_keterangan_length: int = 20
) -> tuple[bool, Optional[List[str]]]:

    warnings = []

    # Placeholder patterns yang tidak boleh ada di output
    placeholder_patterns = [
        r'\[.*?\]',  # [Field], [nilai], etc
        r'\.\.\.+',  # Multiple dots
        r'XXX',
        r'TODO',
        r'FIXME',
        r'\[X\]',
        r'\[nilai\]',
        r'\[detail\]',
    ]

    # Iterate through all fields
    for key, value in data.items():
        if not isinstance(value, dict):
            continue

        if "keterangan" in value:
            keterangan = value["keterangan"]

            # Check minimum length
            if len(keterangan.strip()) < min_keterangan_length:
                warnings.append(
                    f"{key}.keterangan terlalu pendek ({len(keterangan)} chars, "
                    f"min {min_keterangan_length})"
                )

            # Check for placeholders
            for pattern in placeholder_patterns:
                if re.search(pattern, keterangan):
                    warnings.append(
                        f"{key}.keterangan contains placeholder pattern: {pattern}"
                    )

            # Check if keterangan starts with proper format
            # Should start with "Sudah benar." or "Ada yang salah." or "Sebagian sudah benar."
            valid_starts = ["Sudah benar", "Ada yang salah", "Sebagian sudah benar"]
            if not any(keterangan.strip().startswith(start) for start in valid_starts):
                warnings.append(
                    f"{key}.keterangan tidak dimulai dengan format yang benar. "
                    f"Should start with: {', '.join(valid_starts)}"
                )

    is_ok = len(warnings) == 0
    return is_ok, warnings if warnings else None


# ==================== HELPER FUNCTION ====================

def get_expected_stages_for_doc_type(doc_type: str) -> List[str]:

    doc_type = doc_type.upper()

    stage_mapping = {
        "PR": [
            "stage_1_uraian",
            "stage_2_net_price",
            "stage_3_tot_value",
            "stage_4_total_pr",
            "stage_5_month_sequence",
            "stage_6_net_price_consistency",
            "stage_7_tot_value_consistency",
            "stage_8_net_equals_tot",
            "stage_9_total_calculation"
        ],
        "PO": [
            "stage_1_nomor_kontrak",
            "stage_2_tanggal_kontrak",
            "stage_3a_description_month",
            "stage_3b_quantity",
            "stage_3c_price_consistency",
            "stage_4_amount_total",
            "stage_5a_count_presence",
            "stage_5b_nomor_kontrak_consistency",
            "stage_5c_tanggal_kontrak_consistency",
            "stage_5d_amount_consistency"
        ],
        "GRN": [
            "stage_1_nama_material_month",
            "stage_2_harga_satuan",
            "stage_3_jumlah_harga",
            "stage_4_total_sebelum_ppn",
            "stage_5a_count_presence",
            "stage_5b_month_sequence_consistency",
            "stage_5c_harga_satuan_consistency",
            "stage_5d_total_sebelum_ppn_consistency"
        ],
        "NPK": [
            "stage_1_nama_cc",
            "stage_2_no_kl_sp_wo",
            "stage_3_uraian",
            "stage_4_sid",
            "stage_5_periode",
            "stage_6_nilai",
            "stage_7_total_hak_mitra"
        ],
        "SPB": [
            "stage_1_judul_project",
            "stage_2_periode",
            "stage_3_nomor_kontrak",
            "stage_4_tanggal_kontrak",
            "stage_5_total_pembayaran",
            "stage_6_detail_rekening"
        ],
    }

    return stage_mapping.get(doc_type, [])


required_fields = [
    "judul_project",
    "nomor_surat_utama",
    "nomor_surat_lainnya",
    "tanggal_kontrak",
    "rujukan",
    "delivery",
    "jangka_waktu",
    "dpp",
    "metode_pembayaran",
    "harga_satuan",
    "detail_rekening",
    "pejabat_penanda_tangan"
]


def expand_ground_truth_info(ground_truth: json, doc_type: str):
    # Hitung DPP Setelah Pajak 11%
    dpp = ground_truth['dpp']
    vat_dpp = dpp * (11 / 100)
    dpp_with_vat = dpp + vat_dpp
    ground_truth['dpp_with_vat'] = dpp_with_vat

    # Hitung Harga Satuan Setelah Pajak 11%
    harga_satuan = ground_truth['harga_satuan']
    vat_satuan = harga_satuan * (11 / 100)
    harga_satuan_with_vat = harga_satuan + vat_satuan
    ground_truth['harga_satuan_with_vat'] = harga_satuan_with_vat

    # Terbilang DPP
    terbilang_dpp = num2words(dpp)
    ground_truth['terbilang_dpp'] = terbilang_dpp

    # Terbilang Harga Satuan
    terbilang_satuan = num2words(harga_satuan)
    ground_truth['terbilang_dpp'] = terbilang_satuan

    # urutan uraian
    if doc_type == "PO":
        start_date = ground_truth["jangka_waktu"]['start_date']
        end_data = ground_truth["jangka_waktu"]['end_date']
        duration = ground_truth['jangka_waktu']['duration']

        cycle_bulanan = []

        for period in range(duration):
            pass

    return ground_truth
