import asyncio
import logging
from pathlib import Path
from typing import List, Dict, Any, Tuple

from app.services.advance_review_service import (
    extract_file_type,
    review_single_document,
)

logger = logging.getLogger(__name__)

VALID_DOC_TYPES = [
    'PR', 'PO', 'GR', 'NPK', 'SPB', 'KUITANSI',
    'FAKTUR PAJAK', 'BAST', 'BARD', 'BAUT',
    'P7', 'TYPO', 'SKM', 'INVOICE'
]


def is_valid_doc_type(doc_type: str) -> bool:
    """Check if document type is in valid list."""
    return doc_type in VALID_DOC_TYPES


def categorize_documents_for_review(
        filepaths: List[str]
) -> Tuple[List[Tuple[str, str]], List[Tuple[str, str]]]:
    valid_docs = []
    skipped_docs = []

    for filepath in filepaths:
        filename = Path(filepath).name
        doc_type = extract_file_type(filename)

        if not doc_type or doc_type == "UNKNOWN":
            logger.warning(f"Unknown document type, skipping: {filename}")
            skipped_docs.append((filename, "UNKNOWN"))
            continue

        # Check if doc_type is valid
        if not is_valid_doc_type(doc_type):
            logger.warning(f"Document type '{doc_type}' not in valid list, skipping: {filename}")
            skipped_docs.append((filename, doc_type))
            continue

        # Valid document for review
        valid_docs.append((filepath, doc_type))
        logger.info(f"Categorized for review: {filename} -> {doc_type}")

    # Log summary
    if skipped_docs:
        logger.info(f"Skipped {len(skipped_docs)} documents:")
        for filename, doc_type in skipped_docs:
            logger.info(f"  - {filename} (type: {doc_type})")

    return valid_docs, skipped_docs


async def process_reviews_parallel(
        review_docs: List[Tuple[str, str]],
        ground_truth: Dict[str, Any],
        max_concurrent: int = 6
) -> Dict[str, Any]:
    if not review_docs:
        logger.info("No documents to review")
        return {}

    semaphore = asyncio.Semaphore(max_concurrent)

    async def review_with_semaphore(filepath: str, doc_type: str):
        async with semaphore:
            logger.info(f"Starting review for {doc_type}: {Path(filepath).name}")
            try:
                result = await review_single_document(
                    filepath,
                    doc_type,
                    ground_truth
                )
                logger.info(f"Completed review for {doc_type}: {result.get('status')}")
                return doc_type, result
            except Exception as e:
                logger.error(f"Error reviewing {doc_type} ({Path(filepath).name}): {str(e)}")
                return doc_type, {
                    "status": "error",
                    "error": str(e),
                    "filepath": filepath,
                    "filename": Path(filepath).name
                }

    # Create tasks for all reviews
    tasks = [
        review_with_semaphore(filepath, doc_type)
        for filepath, doc_type in review_docs
    ]

    # Run all tasks in parallel
    results = await asyncio.gather(*tasks, return_exceptions=True)

    # Process results
    review_results = {}
    for result in results:
        if isinstance(result, Exception):
            logger.error(f"Error in review task: {result}")
            continue

        doc_type, review_data = result
        review_results[doc_type] = review_data

    return review_results


async def run_advance_review_orchestrator(
        filepaths: List[str],
        ticket: str,
        nama_mitra: str,
        ground_truth: Dict[str, Any],
        max_concurrent: int = 6
) -> Dict[str, Any]:
    logger.info(f"Starting advance review orchestrator for ticket: {ticket}")
    logger.info(f"Total files to process: {len(filepaths)}")
    logger.info(f"Input data - nama_mitra: {nama_mitra} - {ticket}")
    logger.info(f"Valid document types: {', '.join(VALID_DOC_TYPES)}")

    if not ground_truth or not isinstance(ground_truth, dict):
        raise ValueError("ground_truth must be a valid dictionary")

    logger.info(f"Ground truth received with keys: {list(ground_truth.keys())}")

    # Log ground truth structure (flat structure now)
    logger.info("Ground truth structure received:")
    for doc_type, doc_data in ground_truth.items():
        if isinstance(doc_data, dict):
            if "harga_prorate" in doc_data:
                logger.info(f"  ✓ {doc_type} contains harga_prorate with {len(doc_data['harga_prorate'])} months")
            if "total_tagihan" in doc_data:
                logger.info(f"  ✓ {doc_type} contains total_tagihan: Rp {doc_data['total_tagihan']:,}")
            if len(doc_data) == 0:
                logger.warning(f"  ⚠ {doc_type} is empty (extraction failed)")
        else:
            logger.debug(f"  - {doc_type} data type: {type(doc_data)}")

    try:
        logger.info("Step 1: Merging Ground Truth with user input...")

        # Ground truth is now flat structure - just add user input
        enriched_ground_truth = ground_truth.copy()
        enriched_ground_truth["nama_mitra"] = nama_mitra
        enriched_ground_truth["ticket"] = ticket

        logger.info("Step 2: Categorizing documents for review...")

        review_docs, skipped_docs = categorize_documents_for_review(filepaths)

        logger.info(f"Total documents for review: {len(review_docs)}")

        for filepath, doc_type in review_docs:
            logger.info(f"  - {doc_type}: {Path(filepath).name}")

        logger.info("Step 3: Processing reviews in parallel...")

        review_results = await process_reviews_parallel(
            review_docs,
            enriched_ground_truth,
            max_concurrent
        )

        logger.info(f"Review completed. Total results: {len(review_results)}")

        logger.info("Step 4: Building response...")

        # Build response
        response = {
            "ticket": ticket,
            "review_results": review_results,
            "summary": {
                "total_files": len(filepaths),
                "reviewed": len(review_results),
                "skipped": len(skipped_docs)
            }
        }

        logger.info(f"Advance review orchestrator completed successfully for ticket: {ticket}")

        return response

    except ValueError as e:
        logger.error(f"Validation error: {str(e)}")
        raise

    except Exception as e:
        # Unexpected errors
        logger.error(f"Unexpected error in orchestrator: {str(e)}")
        raise RuntimeError(f"Orchestrator failed: {str(e)}") from e