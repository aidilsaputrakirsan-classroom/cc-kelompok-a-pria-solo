import asyncio
import logging
import os
from typing import Dict, Any, List

from app.services.date_validation_service import validate_text_dates
from app.services.ocr_service import extract_document_content_di
from app.services.price_validation_service import validate_text_prices
from app.services.typo_validation_service import validate_text_typos
from app.utils.preprocessor_utils import convert_page_to_universal

logger = logging.getLogger(__name__)


async def run_all_validators(filepath: str) -> Dict[str, Any]:
    try:
        full_text = await asyncio.to_thread(extract_document_content_di, filepath)

        if full_text:
            typo_task = asyncio.to_thread(validate_text_typos, full_text)
            price_task = asyncio.to_thread(validate_text_prices, full_text)
            date_task = asyncio.to_thread(validate_text_dates, full_text)

            typo_result, price_result, date_result = await asyncio.gather(
                typo_task, price_task, date_task, return_exceptions=True
            )

            file_size_bytes = os.path.getsize(filepath)
            file_size_mb = round(file_size_bytes / (1024 * 1024), 2)

            result = {
                "typo_checker": convert_page_to_universal(typo_result, filepath, "typo") if not isinstance(typo_result,
                                                                                                           Exception) else {
                    "error": str(typo_result)},
                "price_validator": convert_page_to_universal(price_result, filepath) if not isinstance(price_result,
                                                                                                       Exception) else {
                    "error": str(price_result)},
                "date_validator": convert_page_to_universal(date_result, filepath) if not isinstance(date_result,
                                                                                                     Exception) else {
                    "error": str(date_result)},
                "text_extracted": True,
                "file_size": f"{file_size_mb} MB",
                "file_path": filepath
            }

            return result

        else:
            logger.warning(f"Failed to extract text from {filepath}")
            return {
                "typo_checker": {"error": "Failed to extract text"},
                "price_validator": {"error": "Failed to extract text"},
                "date_validator": {"error": "Failed to extract text"},
                "text_extracted": False,
                "file_path": filepath
            }

    except Exception as e:
        logger.error(f"Error processing file {filepath}: {str(e)}")
        return {
            "typo_checker": {"error": str(e)},
            "price_validator": {"error": str(e)},
            "date_validator": {"error": str(e)},
            "text_extracted": False,
            "filetype": None,
            "file_path": filepath,
            "processing_error": str(e)
        }


async def run_single_file_validators(filepath: str) -> Dict[str, Any]:

    try:
        result = await run_all_validators(filepath)
        result["status"] = "success"
        return result
    except Exception as e:
        logger.error(f"Unexpected error processing {filepath}: {str(e)}")
        return {
            "status": "error",
            "typo_checker": {"error": str(e)},
            "price_validator": {"error": str(e)},
            "date_validator": {"error": str(e)},
            "text_extracted": False,
            "filetype": None,
            "file_path": filepath,
            "processing_error": str(e)
        }


async def run_multiple_files_validators(filepaths: List[str]) -> Dict[str, Dict[str, Any]]:

    if not filepaths:
        return {}

    logger.info(f"Starting validation for {len(filepaths)} files")

    try:
        tasks = [run_single_file_validators(filepath) for filepath in filepaths]
        results = await asyncio.gather(*tasks, return_exceptions=True)

        combined_results = {}
        for i, filepath in enumerate(filepaths):
            if isinstance(results[i], Exception):
                logger.error(f"Exception processing {filepath}: {str(results[i])}")
                combined_results[filepath] = {
                    "status": "error",
                    "typo_checker": {"error": str(results[i])},
                    "price_validator": {"error": str(results[i])},
                    "date_validator": {"error": str(results[i])},
                    "text_extracted": False,
                    "filetype": None,
                    "file_path": filepath,
                    "processing_error": str(results[i])
                }
            else:
                combined_results[filepath] = results[i]

        logger.info(f"Completed validation for {len(filepaths)} files")
        return combined_results

    except Exception as e:
        logger.error(f"Error in run_multiple_files_validators: {str(e)}")
        return {
            filepath: {
                "status": "error",
                "typo_checker": {"error": str(e)},
                "price_validator": {"error": str(e)},
                "date_validator": {"error": str(e)},
                "text_extracted": False,
                "filetype": None,
                "file_path": filepath,
                "processing_error": str(e)
            }
            for filepath in filepaths
        }
