import logging
import calendar
from typing import Dict, Any, Optional, Tuple
from decimal import Decimal

logger = logging.getLogger(__name__)

MONTH_MAPPING = {
    "januari": 1,
    "februari": 2,
    "maret": 3,
    "april": 4,
    "mei": 5,
    "juni": 6,
    "juli": 7,
    "agustus": 8,
    "september": 9,
    "oktober": 10,
    "november": 11,
    "desember": 12
}

# Dokumen mandatory yang bisa punya harga_satuan_raw
MANDATORY_DOC_TYPES = ['SP', 'KL', 'WO', 'NOTA_PESANAN']


def parse_month_year(month_year_str: str) -> Optional[Tuple[int, int]]:
    """
    Parse string 'Bulan Tahun' menjadi (month, year).
    Contoh: 'Mei 2023' -> (5, 2023)
    """
    try:
        parts = month_year_str.strip().split()
        if len(parts) != 2:
            logger.error(f"Invalid format for month_year: {month_year_str}")
            return None

        month_name = parts[0].lower()
        year = int(parts[1])

        month = MONTH_MAPPING.get(month_name)
        if not month:
            logger.error(f"Unknown month name: {month_name}")
            return None

        return (month, year)

    except Exception as e:
        logger.error(f"Error parsing month_year '{month_year_str}': {str(e)}")
        return None


def get_days_in_month(month: int, year: int) -> int:
    """
    Get jumlah hari dalam bulan tertentu (realtime, including leap year).
    """
    return calendar.monthrange(year, month)[1]


def calculate_prorate_prices(
        prorate_data: Dict[str, int],
        harga_bulanan: float
) -> Dict[str, int]:
    """
    Hitung harga prorate untuk setiap bulan.

    Formula: (hari_prorate / total_hari_bulan) × harga_bulanan

    Args:
        prorate_data: Dict dengan format {"Mei 2023": 20, "Mei 2024": 11}
        harga_bulanan: Harga satuan bulanan (raw number)

    Returns:
        Dict dengan format {"Mei 2023": 3361935, "Mei 2024": 1849065}
    """
    if not prorate_data or not isinstance(prorate_data, dict):
        logger.warning("Prorate data is empty or invalid")
        return {}

    if not harga_bulanan or harga_bulanan <= 0:
        logger.error(f"Invalid harga_bulanan: {harga_bulanan}")
        return {}

    logger.info(f"Calculating prorate prices for {len(prorate_data)} months")
    logger.info(f"Monthly price: Rp {harga_bulanan:,.0f}")

    harga_prorate = {}

    for month_year_str, hari_prorate in prorate_data.items():
        try:
            # Parse month and year
            parsed = parse_month_year(month_year_str)
            if not parsed:
                logger.error(f"Failed to parse: {month_year_str}")
                continue

            month, year = parsed

            # Get total days in that month (REALTIME - includes leap year check)
            total_days = get_days_in_month(month, year)

            # Validate hari_prorate
            if hari_prorate < 1 or hari_prorate > total_days:
                logger.error(
                    f"Invalid hari_prorate {hari_prorate} for {month_year_str} "
                    f"(must be between 1 and {total_days})"
                )
                continue

            # Calculate prorate price
            # Formula: (hari_prorate / total_days) * harga_bulanan
            prorate_ratio = Decimal(hari_prorate) / Decimal(total_days)
            prorate_price = float(prorate_ratio * Decimal(harga_bulanan))

            # Round to nearest integer
            prorate_price_int = round(prorate_price)

            harga_prorate[month_year_str] = prorate_price_int

            logger.info(f"✓ {month_year_str}:")
            logger.info(f"  - Total days (realtime): {total_days}")
            logger.info(f"  - Hari prorate: {hari_prorate}")
            logger.info(f"  - Ratio: {hari_prorate}/{total_days} = {float(prorate_ratio):.4f}")
            logger.info(f"  - Prorate price: Rp {prorate_price_int:,}")

        except Exception as e:
            logger.error(f"Error calculating prorate for {month_year_str}: {str(e)}")
            continue

    logger.info(f"Prorate calculation completed: {len(harga_prorate)}/{len(prorate_data)} months")

    return harga_prorate


def calculate_total_tagihan(nilai_satuan_usage: Dict[str, int]) -> int:
    """
    Hitung total tagihan dari nilai_satuan_usage.

    Args:
        nilai_satuan_usage: Dict dengan format {"Mei 2023": 3361935, "Juni 2023": 5211000, ...}

    Returns:
        Total tagihan (integer)
    """
    if not nilai_satuan_usage or not isinstance(nilai_satuan_usage, dict):
        logger.warning("nilai_satuan_usage is empty or invalid")
        return 0

    try:
        total = sum(nilai_satuan_usage.values())
        logger.info(f"✓ Total tagihan calculated: Rp {total:,} from {len(nilai_satuan_usage)} months")
        return total
    except Exception as e:
        logger.error(f"Error calculating total tagihan: {str(e)}")
        return 0


def enrich_ground_truth_data(
        ground_truth_results: Dict[str, Any]
) -> Dict[str, Any]:
    """
    Enrich ground truth data dengan calculated/derived information.

    Input structure (FLAT):
    {
        "NPK": {"prorate": {...}, "nilai_satuan_usage": {...}},
        "SP": {"harga_satuan_raw": 5211000, ...}
    }

    Current enrichments:
    1. total_tagihan -> injected to NPK
       - Calculated from sum of nilai_satuan_usage
    2. harga_prorate -> injected to mandatory documents (SP/KL/WO/Nota Pesanan)
       - Calculated from NPK's prorate data + mandatory doc's harga_satuan_raw

    Process:
    1. Cari NPK -> ambil prorate data & nilai_satuan_usage
    2. Hitung total_tagihan dari nilai_satuan_usage -> inject ke NPK
    3. Cari dokumen mandatory -> ambil harga_satuan_raw
    4. Hitung prorate untuk setiap bulan
    5. Inject harga_prorate ke dalam data setiap dokumen mandatory

    Args:
        ground_truth_results: Dict hasil ekstraksi ground truth (FLAT structure)

    Returns:
        Dict ground_truth_results yang sudah di-enrich dengan calculated data
    """
    logger.info("=== ENRICHING GROUND TRUTH DATA ===")

    # Deep copy untuk avoid mutation
    enriched_results = {}
    for doc_type, data in ground_truth_results.items():
        enriched_results[doc_type] = data.copy() if isinstance(data, dict) else data

    # Step 1: Cari NPK dan ambil prorate data + nilai_satuan_usage
    npk_data = enriched_results.get("NPK")

    if not npk_data or not isinstance(npk_data, dict) or len(npk_data) == 0:
        logger.info("NPK not found or empty. Skipping enrichment.")
        return enriched_results

    # Step 2: Calculate total_tagihan from nilai_satuan_usage
    nilai_satuan_usage = npk_data.get("nilai_satuan_usage")

    if nilai_satuan_usage and isinstance(nilai_satuan_usage, dict) and len(nilai_satuan_usage) > 0:
        logger.info("Calculating total tagihan from nilai_satuan_usage...")
        total_tagihan = calculate_total_tagihan(nilai_satuan_usage)

        if total_tagihan > 0:
            enriched_results["NPK"]["total_tagihan"] = total_tagihan
            logger.info(f"✓ Injected total_tagihan to NPK: Rp {total_tagihan:,}")
        else:
            logger.warning("Total tagihan calculation returned 0")
    else:
        logger.info("nilai_satuan_usage not found or empty in NPK. Skipping total tagihan calculation.")

    # Step 3: Get prorate data for harga_prorate calculation
    prorate_data = npk_data.get("prorate")

    if not prorate_data or not isinstance(prorate_data, dict) or len(prorate_data) == 0:
        logger.info("Prorate data is empty in NPK. Skipping harga_prorate calculation.")
        return enriched_results

    logger.info(f"✓ Found prorate data in NPK: {prorate_data}")

    # Step 4: Cari dokumen mandatory yang punya harga_satuan_raw
    mandatory_docs_found = []
    harga_satuan_raw = None

    for doc_type in MANDATORY_DOC_TYPES:
        doc_data = ground_truth_results.get(doc_type)

        # Check if doc exists and not empty
        if not doc_data or not isinstance(doc_data, dict) or len(doc_data) == 0:
            continue

        harga = doc_data.get("harga_satuan_raw")

        if harga and harga > 0:
            mandatory_docs_found.append(doc_type)
            if not harga_satuan_raw:
                harga_satuan_raw = harga
                logger.info(f"✓ Found harga_satuan_raw in {doc_type}: Rp {harga:,}")

    if not mandatory_docs_found:
        logger.warning("No mandatory documents with harga_satuan_raw found. Skipping prorate calculation.")
        return enriched_results

    if not harga_satuan_raw or harga_satuan_raw <= 0:
        logger.error("harga_satuan_raw is invalid. Cannot calculate prorate.")
        return enriched_results

    logger.info(f"✓ Mandatory documents found: {', '.join(mandatory_docs_found)}")
    logger.info(f"✓ Using harga_satuan_raw: Rp {harga_satuan_raw:,}")

    # Step 5: Calculate prorate prices
    logger.info("Calculating prorate prices...")
    harga_prorate = calculate_prorate_prices(prorate_data, harga_satuan_raw)

    if not harga_prorate:
        logger.warning("Prorate calculation returned empty result")
        return enriched_results

    logger.info(f"✓ Prorate prices calculated successfully for {len(harga_prorate)} months")

    # Step 6: Inject harga_prorate ke setiap mandatory document
    for doc_type in mandatory_docs_found:
        enriched_results[doc_type]["harga_prorate"] = harga_prorate
        logger.info(f"✓ Injected harga_prorate to {doc_type}")

    # Log final result
    logger.info("=== ENRICHMENT COMPLETED ===")
    logger.info(f"Prorate data injected to: {', '.join(mandatory_docs_found)}")
    for month_year, price in harga_prorate.items():
        logger.info(f"  - {month_year}: Rp {price:,}")

    return enriched_results