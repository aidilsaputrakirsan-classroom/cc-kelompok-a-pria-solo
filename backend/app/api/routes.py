import json
import logging
import os
import shutil
import re
from pathlib import Path
from typing import List

from app.orchestrators.document_extraction_orchestrator import run_document_extraction
from app.orchestrators.unified_review_orchestrator import run_unified_review_orchestrator
from app.schemas.route_inputs import (
    GroundTruthJsonField,
    TicketField,
    validation_error_message,
)
from dotenv import load_dotenv
from fastapi import APIRouter, UploadFile, File
from fastapi import Form, HTTPException
from pydantic import ValidationError

load_dotenv()

# Batas praktis untuk upload (setara “validasi kuat” di sisi server)
MAX_PDF_FILES_PER_REQUEST = 100
MAX_PDF_SIZE_BYTES = 50 * 1024 * 1024  # 50 MiB per file

# Setup Temporary Storage
TEMP_STORAGE = Path(os.getenv("TEMP_STORAGE", "./temp"))
TEMP_STORAGE.mkdir(parents=True, exist_ok=True)

logger = logging.getLogger(__name__)
router = APIRouter()


@router.post("/information-extraction")
async def document_information_extraction(
        ticket: str = Form(...),
        files: List[UploadFile] = File(...)
):
    # 1. Validasi ticket (Pydantic)
    try:
        ticket = TicketField.model_validate({"ticket": ticket}).ticket
    except ValidationError as e:
        raise HTTPException(status_code=400, detail=validation_error_message(e))

    if not files:
        raise HTTPException(
            status_code=400,
            detail="Tidak ada file yang diunggah. Lampirkan minimal satu file PDF.",
        )

    if len(files) > MAX_PDF_FILES_PER_REQUEST:
        raise HTTPException(
            status_code=400,
            detail=f"Terlalu banyak file sekaligus (maksimal {MAX_PDF_FILES_PER_REQUEST} PDF per request).",
        )

    # 2. Validasi nama & ekstensi PDF
    for file in files:
        if not file.filename:
            raise HTTPException(
                status_code=400,
                detail="Salah satu file tidak memiliki nama. Unggah ulang dengan nama file yang valid.",
            )
        if not file.filename.lower().endswith(".pdf"):
            raise HTTPException(
                status_code=400,
                detail=f"Hanya file PDF yang diizinkan: '{file.filename}'.",
            )

    # 3. Setup Folder Penyimpanan (Ticket Based)
    ticket_storage = TEMP_STORAGE / ticket

    try:
        # Cleanup folder lama jika ada (Fresh Start)
        if ticket_storage.exists():
            shutil.rmtree(ticket_storage)
        ticket_storage.mkdir(parents=True, exist_ok=True)

        file_paths = []

        # 4. Simpan File PDF ke Folder
        for file in files:
            file_path = Path(file.filename)
            stem = file_path.stem  # Contoh: "21B. P7" atau "22A.BAST"

            # Split by dot, ambil bagian terakhir
            parts = stem.split(".")

            # Ambil bagian terakhir (paling kanan) sebagai Doc Type
            if len(parts) >= 2:
                doc_type = parts[-1].strip()
            else:
                doc_type = stem.strip()

            # Bersihkan prefix angka di awal
            doc_type = re.sub(r'^\d+\s*', '', doc_type)

            # Validasi doc_type tidak kosong
            if not doc_type:
                raise ValueError(f"Cannot extract doc_type from: {file.filename}")

            # Naming: doc_type.pdf (langsung di root ticket)
            filename = f"{doc_type}.pdf"
            file_location = ticket_storage / filename

            # Save file fisik
            with open(file_location, "wb") as buffer:
                content = await file.read()
                if len(content) > MAX_PDF_SIZE_BYTES:
                    raise HTTPException(
                        status_code=400,
                        detail=(
                            f"File '{file.filename}' terlalu besar "
                            f"(maksimal {MAX_PDF_SIZE_BYTES // (1024 * 1024)} MiB per PDF)."
                        ),
                    )
                buffer.write(content)

            file_paths.append(str(file_location))

        # 5. Jalankan Proses Ekstraksi (Orchestrator)
        extraction_result = await run_document_extraction(
            file_paths,
            ticket_storage
        )

        # Validasi hasil ekstraksi
        if extraction_result is None:
            raise ValueError("Extraction function returned None")

        # 6. CLEANUP: Hapus File PDF, Sisakan JSON
        # ---------------------------------------------------------
        try:
            # Cari semua file berakhiran .pdf di folder tiket dan hapus
            for pdf_file in ticket_storage.glob("*.pdf"):
                try:
                    pdf_file.unlink()  # Menghapus file
                    logger.debug(f"Cleaned up temporary PDF: {pdf_file.name}")
                except OSError as e:
                    logger.warning(f"Error deleting {pdf_file.name}: {e}")

            logger.info(f"Cleanup completed for ticket {ticket}. PDFs removed, JSON kept.")

        except Exception as cleanup_error:
            # Error saat cleanup tidak boleh membatalkan response sukses ke user
            logger.error(f"General error during cleanup for ticket {ticket}: {cleanup_error}")
        # ---------------------------------------------------------

        # 7. Return Response ke User
        return {
            "status": extraction_result.get("status", "completed"),
            "ticket": ticket,
            "total_files": extraction_result.get("total_files", len(files)),
            "ocr_extraction_success": extraction_result.get("ocr_extraction_success", 0),
            "ground_truth_results": extraction_result.get("ground_truth_results", {})
        }

    except HTTPException:
        if ticket_storage.exists():
            shutil.rmtree(ticket_storage)
            logger.info(f"Storage cleaned up after client error for ticket {ticket}")
        raise
    except Exception as e:
        # Jika terjadi Error FATAL (sebelum return), hapus satu folder tiket
        if ticket_storage.exists():
            shutil.rmtree(ticket_storage)
            logger.info(f"Storage cleaned up due to error for ticket {ticket}")

        import traceback
        traceback.print_exc()

        raise HTTPException(
            status_code=500,
            detail=f"Error processing extraction: {str(e)}"
        )


@router.post("/review")
async def validate_review(
        ticket: str = Form(...),
        ground_truth: str = Form(...)
):
    try:
        ticket = TicketField.model_validate({"ticket": ticket}).ticket
    except ValidationError as e:
        raise HTTPException(status_code=400, detail=validation_error_message(e))

    try:
        GroundTruthJsonField.model_validate({"ground_truth": ground_truth})
    except ValidationError as e:
        raise HTTPException(status_code=400, detail=validation_error_message(e))

    gt_data = json.loads(ground_truth)

    # Load OCR extraction results dari root ticket
    ticket_storage = TEMP_STORAGE / ticket
    extraction_file = ticket_storage / "extraction_results.json"

    if not extraction_file.exists():
        raise HTTPException(
            status_code=404,
            detail=f"Extraction results not found for ticket {ticket}. "
                   f"Please run /information-extraction first."
        )

    try:
        with open(extraction_file, 'r', encoding='utf-8') as f:
            extraction_data = json.load(f)
    except Exception as e:
        raise HTTPException(
            status_code=500,
            detail=f"Failed to load extraction results: {str(e)}"
        )

    # Get OCR results
    ocr_results = extraction_data.get("extraction_results", {})

    if not ocr_results:
        raise HTTPException(
            status_code=400,
            detail="No OCR results found in extraction data"
        )

    logger.info(f"Starting unified review for ticket {ticket}")
    logger.info(f"OCR results available for: {list(ocr_results.keys())}")
    logger.info(f"Ground truth provided for: {list(gt_data.keys())}")

    try:
        results = await run_unified_review_orchestrator(
            ticket=ticket,
            ocr_results=ocr_results,
            ground_truth=gt_data,
            ticket_storage=ticket_storage
        )

        return {
            "ticket": ticket,
            "status": "completed",
            **results
        }

    except Exception as e:
        import traceback
        traceback.print_exc()

        raise HTTPException(
            status_code=500,
            detail=f"Error processing review: {str(e)}"
        )