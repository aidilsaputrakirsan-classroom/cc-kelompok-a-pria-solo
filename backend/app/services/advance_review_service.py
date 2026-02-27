import asyncio
import json
import logging
import os
from pathlib import Path
from typing import Dict, Any, List, Optional, Tuple

from app.utils.advance_review_utils import (
    extract_json_from_llm_response,
    validate_review_structure,
    validate_review_content_quality
)
from app.utils.prompt_templates import get_prompt_template
from dotenv import load_dotenv
from langchain.chains import LLMChain
from langchain.globals import set_llm_cache
from langchain.prompts import PromptTemplate
from langchain_openai.chat_models import ChatOpenAI

load_dotenv()

logger = logging.getLogger(__name__)

GROUND_TRUTH_MANDATORY = ["KL", "SP", "WO", "NOPES"]

GROUND_TRUTH_OPTIONAL = ["NPK", "BAUT", "BARD", "P7", "BAST"]

GROUND_TRUTH_TYPES = GROUND_TRUTH_MANDATORY + GROUND_TRUTH_OPTIONAL

# Single review types
SINGLE_REVIEW_TYPES = ["PO", "GR", "PR", "SPB", "INVOICE", "KUITANSI", "FAKTUR_PAJAK"]

MODEL = 'gpt-4o-mini'

# Semaphore limits (OCR tidak digunakan lagi, tapi tetap dipertahankan untuk backward compatibility)
MAX_CONCURRENT_OCR_GLOBAL = 6
MAX_CONCURRENT_OCR_PER_USER = 3

_global_ocr_semaphore = asyncio.Semaphore(MAX_CONCURRENT_OCR_GLOBAL)
_user_ocr_semaphores: Dict[str, asyncio.Semaphore] = {}

# ==================== LLM SETUP ====================

set_llm_cache(None)


def get_llm_instance(model_type: str = "gpt-4o-mini", task: str = "review"):
    """Get configured LLM instance"""
    if model_type == "gpt-4o-mini":
        return ChatOpenAI(
            openai_api_key=os.getenv("OPENAI_API_KEY"),
            model="gpt-4o-mini",
            temperature=0.2,
            frequency_penalty=0.2,
            presence_penalty=0.1,
            response_format={"type": "json_object"},
            cache=False
        )
    else:
        return ChatOpenAI(
            openai_api_key=os.getenv("OPENAI_API_KEY"),
            model="gpt-4o-mini",
            temperature=0.1,
            response_format={"type": "json_object"},
            cache=False
        )


# ==================== UTILITY FUNCTIONS ====================

def extract_file_type(filename: str) -> Optional[str]:
    """
    Extract document type from filename.

    Supported patterns:
    - 21.KL.pdf → KL
    - 22.PO.pdf → PO
    - 12.BAST_2024.pdf → BAST
    """
    file_type = filename.split(".")[0]
    return file_type


def is_mandatory_ground_truth(doc_type: str) -> bool:
    """Check if document type is mandatory ground truth"""
    return doc_type in GROUND_TRUTH_MANDATORY


def is_optional_ground_truth(doc_type: str) -> bool:
    """Check if document type is optional ground truth"""
    return doc_type in GROUND_TRUTH_OPTIONAL


def get_user_semaphore(user_id: str) -> asyncio.Semaphore:
    """Get or create per-user semaphore for OCR rate limiting"""
    if user_id not in _user_ocr_semaphores:
        _user_ocr_semaphores[user_id] = asyncio.Semaphore(MAX_CONCURRENT_OCR_PER_USER)
    return _user_ocr_semaphores[user_id]


async def extract_ground_truth_from_ocr(
        doc_type: str,
        ocr_text: str
) -> Dict[str, Any]:
    """
    Extract ground truth data from pre-extracted OCR text.

    Args:
        doc_type: Document type (e.g., "KL", "SP", "WO")
        ocr_text: Pre-extracted text content from extraction_results.json

    Returns:
        Dictionary containing extraction results
    """
    try:
        logger.info(f"Extracting ground truth from OCR text: {doc_type}")

        # Validate input
        if not ocr_text or not ocr_text.strip():
            logger.error(f"Empty OCR text for {doc_type}")
            return {
                "status": "error",
                "doc_type": doc_type,
                "error": "Empty OCR text"
            }

        # Get extraction prompt template
        template = get_prompt_template("EXTRACT", doc_type)
        if template is None:
            error_msg = f"No extraction template found for doc_type '{doc_type}'"
            logger.error(error_msg)
            return {
                "status": "error",
                "doc_type": doc_type,
                "error": error_msg
            }

        # Create prompt
        prompt_template = PromptTemplate(
            input_variables=['document_text'],
            template=template
        )

        # Create chain
        llm = get_llm_instance(MODEL, "extraction")
        chain = LLMChain(llm=llm, prompt=prompt_template)

        # Run extraction
        result = chain.run(document_text=ocr_text)

        # Parse JSON response
        try:
            parsed_result = extract_json_from_llm_response(result)
        except ValueError as e:
            logger.error(f"Failed to extract JSON from LLM response: {e}")
            return {
                "status": "error",
                "doc_type": doc_type,
                "error": f"JSON extraction failed: {str(e)}",
                "raw_response": result[:500]
            }

        if not isinstance(parsed_result, dict) or len(parsed_result) == 0:
            logger.error(f"Extraction returned empty or invalid data for {doc_type}")
            return {
                "status": "error",
                "doc_type": doc_type,
                "error": "Extraction returned empty or invalid data",
                "extracted_data": parsed_result
            }

        logger.info(f"Successfully extracted {doc_type} ({len(parsed_result)} fields)")

        return {
            "status": "success",
            "doc_type": doc_type,
            "data": parsed_result
        }

    except Exception as e:
        logger.error(f"Error extracting from OCR for {doc_type}: {str(e)}")
        return {
            "status": "error",
            "doc_type": doc_type,
            "error": str(e)
        }


async def review_single_document(
        filepath: str,
        doc_type: str,
        ground_truth: Dict[str, Any],
        ocr_cache: Optional[Dict[str, str]] = None
) -> Dict[str, Any]:
    """
    Review a single document using pre-extracted OCR text.

    Args:
        filepath: Virtual filepath (not used for reading, just for logging)
        doc_type: Document type (e.g., "PR", "PO", "GR")
        ground_truth: Ground truth data for comparison
        ocr_cache: Dictionary mapping doc_type to text_content from extraction_results.json

    Returns:
        Dictionary containing review results
    """
    try:
        logger.info(f"Reviewing {doc_type}: {Path(filepath).name}")

        # Normalize doc_type
        doc_type_aliases = {
            "NOTA_PESANAN": "NOPES",
            "NOTA PESANAN": "NOPES"
        }
        normalized_doc_type = doc_type_aliases.get(doc_type, doc_type)

        # Get text content from ocr_cache
        if not ocr_cache or doc_type not in ocr_cache:
            logger.error(f"No OCR cache provided for {doc_type}")
            return {
                "status": "error",
                "error": "No OCR text available in cache",
                "filepath": filepath,
                "doc_type": normalized_doc_type
            }

        text_content = ocr_cache[doc_type]

        if not text_content or not text_content.strip():
            logger.error(f"Empty text content for {doc_type}")
            return {
                "status": "error",
                "error": "Empty text content",
                "filepath": filepath,
                "doc_type": normalized_doc_type
            }

        logger.info(f"Using pre-extracted OCR text for review: {doc_type}")

        # Get review template
        template = get_prompt_template("REVIEW", normalized_doc_type)

        if template is None:
            error_msg = f"No prompt template found for doc_type '{normalized_doc_type}'"
            logger.error(error_msg)
            return {
                "status": "error",
                "error": error_msg,
                "filepath": filepath,
                "doc_type": normalized_doc_type
            }

        # Input variable mapping
        input_var_mapping = {
            "PR": "pr_document_text",
            "PO": "po_documents_text",
            "GR": "grn_documents_text",
            "NPK": "npk_document_text",
            "SPB": "spb_document_text",
            "INVOICE": "invoice_document_text",
            "KUITANSI": "kuitansi_document_text",
            "FAKTUR_PAJAK": "faktur_pajak_document_text",
            "BARD": "bard_document_text",
            "BAUT": "baut_document_text",
            "BAST": "bast_document_text",
            "P7": "p7_document_text",
            "SKM": "skm_document_text",
            "KL": "kl_text",
            "SP": "sp_text",
            "WO": "wo_text",
            "NOPES": "nopes_text"
        }

        text_input_key = input_var_mapping.get(
            normalized_doc_type,
            f"{normalized_doc_type.lower()}_text"
        )

        # Create prompt
        prompt_template = PromptTemplate(
            input_variables=[text_input_key, 'ground_truth_json'],
            template=template
        )

        # Create chain
        llm = get_llm_instance(MODEL, "review")
        chain = LLMChain(llm=llm, prompt=prompt_template)

        # Run review
        result = chain.run(
            **{
                text_input_key: text_content,
                'ground_truth_json': json.dumps(ground_truth, indent=2)
            }
        )

        # Parse and validate
        try:
            parsed_result = extract_json_from_llm_response(result)
        except ValueError as e:
            logger.error(f"Failed to extract JSON from review response: {e}")
            return {
                "status": "error",
                "error": f"JSON extraction failed: {str(e)}",
                "filepath": filepath,
                "doc_type": normalized_doc_type,
                "raw_response": result[:500]
            }

        is_valid, validation_error = validate_review_structure(parsed_result, normalized_doc_type)

        if not is_valid:
            logger.error(f"Review structure validation failed: {validation_error}")
            return {
                "status": "error",
                "error": f"Review validation failed: {validation_error}",
                "filepath": filepath,
                "doc_type": normalized_doc_type,
                "review_data": parsed_result
            }

        is_quality_ok, quality_warnings = validate_review_content_quality(
            parsed_result,
            normalized_doc_type
        )

        if not is_quality_ok:
            logger.warning(f"Review quality warnings for {normalized_doc_type}: {quality_warnings}")

        logger.info(f"Successfully reviewed {doc_type} (as {normalized_doc_type})")

        return {
            "status": "success",
            "doc_type": normalized_doc_type,
            "filepath": filepath,
            "review_result": parsed_result,
            "quality_warnings": quality_warnings if not is_quality_ok else None
        }

    except Exception as e:
        logger.error(f"Error reviewing {filepath}: {str(e)}")
        import traceback
        traceback.print_exc()

        return {
            "status": "error",
            "error": str(e),
            "filepath": filepath,
            "doc_type": normalized_doc_type if 'normalized_doc_type' in locals() else doc_type
        }