import asyncio
import logging
from pathlib import Path
from typing import Dict, Any

from app.services.typo_validation_service import validate_text_typos
from app.services.price_validation_service import validate_text_prices
from app.services.date_validation_service import validate_text_dates

from app.services.advance_review_service import review_single_document

logger = logging.getLogger(__name__)


async def run_unified_review_orchestrator(
        ticket: str,
        ocr_results: Dict[str, Any],
        ground_truth: Dict[str, Any],
        ticket_storage: Path
) -> Dict[str, Any]:
    """
    Unified review orchestrator that uses pre-extracted OCR results.

    Args:
        ticket: Ticket ID
        ocr_results: Dictionary from extraction_results.json with structure:
                     {"extraction_results": {"PR": {...}, "PO": {...}, ...}}
        ground_truth: Ground truth data for validation
        ticket_storage: Path to ticket storage directory

    Returns:
        Dictionary containing basic_review, advance_review, and summary
    """
    logger.info(f"Starting unified review orchestrator for ticket: {ticket}")

    # Extract the actual extraction results
    extraction_data = ocr_results.get("extraction_results", ocr_results)

    # Get all doc_types from OCR results
    available_doc_types = set(extraction_data.keys())
    gt_doc_types = set(ground_truth.keys())

    if not available_doc_types:
        logger.warning("No OCR results found to review")
        return {
            "basic_review": {},
            "advance_review": {},
            "summary": {
                "total_ocr_docs": 0,
                "total_gt_docs": len(gt_doc_types),
                "reviewed": 0,
                "skipped_no_ocr": 0
            }
        }

    reviewable_doc_types = available_doc_types

    logger.info(f"Will review {len(reviewable_doc_types)} documents: {list(reviewable_doc_types)}")
    logger.info(f"Ground truth contains {len(gt_doc_types)} doc_types: {list(gt_doc_types)}")

    # Run reviews in parallel
    semaphore = asyncio.Semaphore(6)  # Max 6 concurrent reviews

    async def review_single_doc(doc_type: str):
        async with semaphore:
            logger.info(f"Processing {doc_type}...")

            try:
                # Get OCR data from extraction_results.json
                ocr_data = extraction_data[doc_type]

                # Extract text content directly from JSON
                text_content = ocr_data.get("text_content", "")

                if not text_content:
                    logger.warning(f"No text_content found for {doc_type}")
                    return (doc_type, None, None)

                # === BASIC REVIEW ===
                basic_result = await run_basic_review(doc_type, ocr_data)

                # === ADVANCE REVIEW ===
                # Pass text_content directly, no file reading needed
                advance_result = await run_advance_review(
                    doc_type,
                    ticket,
                    ground_truth,
                    text_content
                )

                logger.info(f"✓ Completed {doc_type}")

                return (doc_type, basic_result, advance_result)

            except Exception as e:
                logger.error(f"Error reviewing {doc_type}: {str(e)}")
                import traceback
                traceback.print_exc()

                return (doc_type, None, None)

    # Create tasks
    tasks = [review_single_doc(doc_type) for doc_type in reviewable_doc_types]

    # Execute in parallel
    results = await asyncio.gather(*tasks, return_exceptions=True)

    # Build response
    basic_review = {}
    advance_review = {}
    success_count = 0
    error_count = 0

    for result in results:
        if isinstance(result, Exception):
            logger.error(f"Task failed with exception: {result}")
            error_count += 1
            continue

        doc_type, basic_result, advance_result = result

        if basic_result is None or advance_result is None:
            error_count += 1
            continue

        # Add to results
        basic_review[doc_type] = basic_result
        advance_review[doc_type] = advance_result
        success_count += 1

    logger.info(f"Review completed: {success_count} success, {error_count} errors")

    return {
        "basic_review": basic_review,
        "advance_review": advance_review,
        "summary": {
            "total_ocr_docs": len(available_doc_types),
            "total_gt_docs": len(gt_doc_types),
            "reviewed": success_count,
            "errors": error_count,
            "note": "All documents reviewed using extraction_results.json"
        }
    }


async def run_basic_review(doc_type: str, ocr_data: Dict[str, Any]) -> Dict[str, Any]:
    """
    Run basic validators (typo, price, date) on OCR data.

    Args:
        doc_type: Document type (e.g., "PR", "PO")
        ocr_data: OCR data dictionary containing text_content, paragraphs_data, words_data

    Returns:
        Dictionary containing validation results
    """
    logger.info(f"Running basic validators for {doc_type}")

    # Validate OCR data structure
    if not isinstance(ocr_data, dict):
        logger.error(f"Invalid OCR data structure for {doc_type}")
        return {
            "typo_checker": [],
            "price_validator": [],
            "date_validator": [],
            "error": "Invalid OCR data structure"
        }

    # Check required fields
    text_content = ocr_data.get('text_content', '')
    if not text_content or len(text_content.strip()) < 20:
        logger.warning(f"Text content too short for {doc_type}")
        return {
            "typo_checker": [],
            "price_validator": [],
            "date_validator": [],
            "error": "Text content too short or missing"
        }

    # Prepare text_dictionary for validators
    text_dictionary = {
        'text_content': text_content,
        'paragraphs_data': ocr_data.get('paragraphs_data', []),
        'words_data': ocr_data.get('words_data', [])
    }

    try:
        loop = asyncio.get_event_loop()

        typo_task = loop.run_in_executor(None, validate_text_typos, text_dictionary)
        price_task = loop.run_in_executor(None, validate_text_prices, text_dictionary)
        date_task = loop.run_in_executor(None, validate_text_dates, text_dictionary)

        typo_result, price_result, date_result = await asyncio.gather(
            typo_task, price_task, date_task,
            return_exceptions=True
        )

        # Handle exceptions
        if isinstance(typo_result, Exception):
            logger.error(f"Typo validation failed for {doc_type}: {typo_result}")
            typo_result = []

        if isinstance(price_result, Exception):
            logger.error(f"Price validation failed for {doc_type}: {price_result}")
            price_result = []

        if isinstance(date_result, Exception):
            logger.error(f"Date validation failed for {doc_type}: {date_result}")
            date_result = []

        logger.info(f"✓ Basic validation completed for {doc_type}: "
                    f"typos={len(typo_result)}, prices={len(price_result)}, dates={len(date_result)}")

        return {
            "typo_checker": typo_result,
            "price_validator": price_result,
            "date_validator": date_result
        }

    except Exception as e:
        logger.error(f"Error in basic validators for {doc_type}: {str(e)}")
        import traceback
        traceback.print_exc()

        return {
            "typo_checker": [],
            "price_validator": [],
            "date_validator": [],
            "error": str(e)
        }


async def run_advance_review(
        doc_type: str,
        ticket: str,
        ground_truth: Dict[str, Any],
        text_content: str
) -> Dict[str, Any]:
    """
    Run advance review using pre-extracted OCR text content.

    Args:
        doc_type: Document type (e.g., "PR", "PO")
        ticket: Ticket ID
        ground_truth: Ground truth data for comparison
        text_content: OCR text content from extraction_results.json

    Returns:
        Dictionary containing review results
    """
    logger.info(f"Running advance review for {doc_type} using extraction_results.json")

    try:
        # Validate text content
        if not text_content or not text_content.strip():
            logger.warning(f"No text content provided for {doc_type}")
            return {
                "status": "error",
                "error": "No text content available in OCR results"
            }

        # Create OCR cache to pass to review service
        # The review service expects a dict mapping doc_type to text_content
        ocr_cache_payload = {
            doc_type: text_content
        }

        # Create virtual filepath (not used for reading, just for logging)
        virtual_filepath = f"{ticket}/{doc_type}.from_extraction_results"

        # Call review service with pre-extracted text
        result = await review_single_document(
            filepath=virtual_filepath,
            doc_type=doc_type,
            ground_truth=ground_truth,
            ocr_cache=ocr_cache_payload
        )

        logger.info(f"✓ Advance review completed for {doc_type}: {result.get('status')}")

        return result

    except Exception as e:
        logger.error(f"Error in advance review for {doc_type}: {str(e)}")
        import traceback
        traceback.print_exc()

        return {
            "status": "error",
            "error": str(e)
        }