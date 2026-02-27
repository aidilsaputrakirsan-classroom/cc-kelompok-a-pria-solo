import asyncio
import json
import logging
from concurrent.futures import ThreadPoolExecutor
from pathlib import Path
from typing import List, Dict, Any

from app.services.ocr_service import extract_document_content_di
from app.services.advance_review_service import (
    extract_ground_truth_from_ocr,
    extract_file_type,
    GROUND_TRUTH_TYPES
)
from app.utils.ground_truth_calculation_utils import enrich_ground_truth_data

logger = logging.getLogger(__name__)

# Thread pool untuk blocking operations
extraction_executor = ThreadPoolExecutor(max_workers=3)

# Semaphore untuk limit concurrent OCR
ocr_semaphore = asyncio.Semaphore(3)


async def extract_single_document(filepath: str, doc_type: str) -> tuple:

    async with ocr_semaphore:
        try:
            logger.info(f"Processing: {doc_type} - {Path(filepath).name}")
            loop = asyncio.get_event_loop()

            # Extract menggunakan Document Intelligence
            ocr_result = await loop.run_in_executor(
                None,
                extract_document_content_di,
                filepath,
                doc_type  # ← Pass doc_type as doc_name
            )

            if not ocr_result:
                logger.error(f"OCR failed for {doc_type}: {filepath}")
                return (doc_type, None, "failed")

            # Validate OCR result
            if 'text_content' not in ocr_result:
                logger.error(f"OCR result missing text_content for {doc_type}")
                return (doc_type, None, "failed")

            text_content = ocr_result['text_content']

            # Basic validation
            if not text_content or len(text_content.strip()) < 20:
                logger.warning(f"OCR returned very short content for {doc_type}: {len(text_content)} chars")
                return (doc_type, None, "failed")

            logger.info(f"✓ OCR success: {doc_type} ({len(text_content)} chars)")
            logger.debug(f"OCR has {len(ocr_result.get('paragraphs_data', []))} paragraphs, "
                         f"{len(ocr_result.get('words_data', []))} words")

            return (doc_type, ocr_result, "success")

        except Exception as e:
            logger.error(f"Extraction error for {doc_type} ({filepath}): {str(e)}")
            import traceback
            traceback.print_exc()
            return (doc_type, None, "failed")


async def ocr_all_documents(
        file_paths: List[str],
        ticket_storage: Path
) -> Dict[str, Any]:

    logger.info(f"Starting OCR extraction for {len(file_paths)} documents")

    # Identify document types
    document_metadata = []
    for filepath in file_paths:
        filename = Path(filepath).name
        doc_type = extract_file_type(filename)
        document_metadata.append((filepath, doc_type))

    # Create extraction tasks
    tasks = [
        extract_single_document(filepath, doc_type)
        for filepath, doc_type in document_metadata
    ]

    # Run all extractions in parallel
    results = await asyncio.gather(*tasks, return_exceptions=True)

    # Build results dictionary
    extraction_results = {}
    failed_docs = []

    for result in results:
        if isinstance(result, Exception):
            logger.error(f"Extraction task failed with exception: {result}")
            continue

        doc_type, ocr_result, status = result

        if status == "failed" or ocr_result is None:
            failed_docs.append(doc_type)
        else:
            # Store FULL OCR result (text_content + paragraphs_data + words_data)
            extraction_results[doc_type] = ocr_result

    if failed_docs:
        logger.warning(f"Extraction failed for documents: {', '.join(failed_docs)}")

    logger.info(f"Extraction completed: Success={len(extraction_results)}, Failed={len(failed_docs)}")

    # Save FULL extraction results to JSON
    output_data = {
        "extraction_results": extraction_results,
        "statistics": {
            "total": len(file_paths),
            "success": len(extraction_results),
            "failed": len(failed_docs),
            "failed_docs": failed_docs
        }
    }

    ocr_json_path = ticket_storage / "extraction_results.json"
    try:
        with open(ocr_json_path, 'w', encoding='utf-8') as f:
            json.dump(output_data, f, ensure_ascii=False, indent=2)
        logger.info(f"Extraction results saved to {ocr_json_path}")
    except Exception as e:
        logger.error(f"Failed to save extraction results to JSON: {str(e)}")

    return {
        "results": extraction_results
    }


async def extract_from_ocr_worker(doc_type: str, ocr_text: str):
    """
    Worker function untuk extract Ground Truth dari OCR text.
    """
    async with ocr_semaphore:
        loop = asyncio.get_event_loop()

        def sync_extract():
            return asyncio.run(extract_ground_truth_from_ocr(doc_type, ocr_text))

        return await loop.run_in_executor(extraction_executor, sync_extract)


async def extract_all_ground_truths(
        extraction_data: Dict[str, Any]
) -> Dict[str, Any]:

    ocr_results = extraction_data.get("results", {})

    # Filter: only process Ground Truth documents
    # Extract text_content from FULL OCR result
    gt_ocr_texts = {}
    for doc_type, ocr_result in ocr_results.items():
        if doc_type in GROUND_TRUTH_TYPES:
            # Extract text_content dari FULL OCR result
            text_content = ocr_result.get('text_content', '')
            if text_content:
                gt_ocr_texts[doc_type] = text_content

    if not gt_ocr_texts:
        logger.warning("No Ground Truth documents found in extraction results")
        return {}

    skipped_docs = set(ocr_results.keys()) - set(gt_ocr_texts.keys())
    if skipped_docs:
        logger.info(f"Skipping non-GT documents: {', '.join(skipped_docs)}")

    logger.info(f"Starting Ground Truth extraction for {len(gt_ocr_texts)} documents")

    # Create extraction tasks (only for Ground Truth)
    tasks = [
        extract_from_ocr_worker(doc_type, text_content)
        for doc_type, text_content in gt_ocr_texts.items()
    ]

    # Run all extractions in parallel
    results = await asyncio.gather(*tasks, return_exceptions=True)

    # Structure the results - FLATTENED (only data, no status/doc_type wrapper)
    extraction_results = {}
    success_count = 0
    error_count = 0

    for idx, result in enumerate(results):
        doc_type = list(gt_ocr_texts.keys())[idx]

        # Handle exceptions from gather
        if isinstance(result, Exception):
            extraction_results[doc_type] = {}  # Empty dict for failed extraction
            error_count += 1
            logger.error(f"Extraction failed for {doc_type}: {str(result)}")
        else:
            # Check if extraction was successful
            if result.get("status") == "success" and "data" in result:
                # Extract only the data field (flatten structure)
                extraction_results[doc_type] = result["data"]
                success_count += 1
            else:
                # Extraction failed, return empty dict
                extraction_results[doc_type] = {}
                error_count += 1
                logger.warning(f"Extraction returned non-success for {doc_type}")

    logger.info(f"Ground Truth extraction completed: {success_count} success, {error_count} errors")

    return extraction_results


async def run_document_extraction(
        file_paths: List[str],
        ticket_storage: Path
) -> Dict[str, Any]:

    try:
        # Step 1: Extract all documents dengan OCR (FULL result)
        logger.info("Step 1: Running OCR extraction...")
        extraction_data = await ocr_all_documents(file_paths, ticket_storage)

        if not extraction_data.get("results"):
            return {
                "status": "error",
                "error": "All OCR extraction operations failed",
                "total_files": len(file_paths),
                "ground_truth_results": {}
            }

        # Step 2: Extract ground truth from OCR results
        logger.info("Step 2: Extracting Ground Truth from OCR results...")
        ground_truth_results = await extract_all_ground_truths(extraction_data)

        # Count success/error for Ground Truth extraction
        gt_success_count = sum(
            1 for result in ground_truth_results.values()
            if result.get("status") == "success"
        )
        gt_error_count = len(ground_truth_results) - gt_success_count

        logger.info(f"Ground Truth extraction: {gt_success_count} success, {gt_error_count} errors")

        if gt_success_count == 0:
            logger.warning("No ground truth data was successfully extracted")
            return {
                "status": "completed",
                "total_files": len(file_paths),
                "ocr_extraction_success": len(extraction_data["results"]),
                "ground_truth_results": ground_truth_results
            }

        # Step 3: Enrich ground truth data (prorate, total_tagihan, etc.)
        logger.info("Step 3: Enriching ground truth data...")
        enriched_ground_truth_results = enrich_ground_truth_data(ground_truth_results)

        # Return final result with enriched ground truth
        return {
            "status": "completed",
            "total_files": len(file_paths),
            "ocr_extraction_success": len(extraction_data["results"]),
            "ground_truth_results": enriched_ground_truth_results
        }

    except Exception as e:
        logger.error(f"Critical error in run_document_extraction: {str(e)}")
        import traceback
        traceback.print_exc()

        return {
            "status": "error",
            "error": str(e),
            "total_files": len(file_paths) if file_paths else 0,
            "ground_truth_results": {}
        }