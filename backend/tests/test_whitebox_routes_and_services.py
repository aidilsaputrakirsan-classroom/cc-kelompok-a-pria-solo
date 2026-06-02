"""White-box tests for FastAPI branches in scenarios 25-40."""

from __future__ import annotations

import asyncio
import json
from pathlib import Path

import pytest

from app.api import routes
from app.services import advance_review_service
from app.utils.advance_review_utils import extract_json_from_llm_response


def _multipart_pdf(name: str = "21B.P7.pdf", content: bytes = b"%PDF-1.4\n%%EOF"):
    return ("files", (name, content, "application/pdf"))


def test_s25_information_extraction_ticket_validation_error(client, monkeypatch):
    monkeypatch.setattr(routes, "require_processing_available", lambda: None)
    response = client.post("/information-extraction", data={"ticket": "***"}, files=[_multipart_pdf()])
    assert response.status_code == 400


def test_s26_information_extraction_files_limit_error(client, monkeypatch):
    monkeypatch.setattr(routes, "require_processing_available", lambda: None)
    files = [_multipart_pdf(f"{i}.P7.pdf") for i in range(101)]
    response = client.post("/information-extraction", data={"ticket": "T-OK"}, files=files)
    assert response.status_code == 400


def test_s27_information_extraction_non_pdf_error(client, monkeypatch):
    monkeypatch.setattr(routes, "require_processing_available", lambda: None)
    response = client.post(
        "/information-extraction",
        data={"ticket": "T-OK"},
        files=[("files", ("data.xlsx", b"abc", "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"))],
    )
    assert response.status_code == 400


def test_s28_information_extraction_file_too_large_error(client, monkeypatch):
    monkeypatch.setattr(routes, "require_processing_available", lambda: None)
    too_large = b"x" * (routes.MAX_PDF_SIZE_BYTES + 1)
    response = client.post(
        "/information-extraction",
        data={"ticket": "T-LARGE"},
        files=[("files", ("21B.P7.pdf", too_large, "application/pdf"))],
    )
    assert response.status_code == 400


def test_s29_information_extraction_none_result_returns_500(client, monkeypatch):
    monkeypatch.setattr(routes, "require_processing_available", lambda: None)
    monkeypatch.setattr(routes, "run_document_extraction", lambda *args, **kwargs: None)
    response = client.post("/information-extraction", data={"ticket": "T-NONE"}, files=[_multipart_pdf()])
    assert response.status_code == 500


def test_s30_run_document_extraction_all_ocr_failed(monkeypatch, tmp_path):
    async def fake_ocr_all_documents(*_args, **_kwargs):
        return {"results": {}}

    monkeypatch.setattr("app.orchestrators.document_extraction_orchestrator.ocr_all_documents", fake_ocr_all_documents)
    result = asyncio.run(
        __import__("app.orchestrators.document_extraction_orchestrator", fromlist=["run_document_extraction"])
        .run_document_extraction(["a.pdf"], tmp_path)
    )
    assert result["status"] == "error"
    assert result["ground_truth_results"] == {}


def test_s31_extract_single_document_short_text_failed(monkeypatch):
    async def run():
        from app.orchestrators.document_extraction_orchestrator import extract_single_document

        monkeypatch.setattr(
            "app.orchestrators.document_extraction_orchestrator.extract_document_content_di",
            lambda *_args, **_kwargs: {"text_content": "short", "paragraphs_data": [], "words_data": []},
        )
        doc_type, ocr_result, status = await extract_single_document("dummy.pdf", "P7")
        return doc_type, ocr_result, status

    doc_type, ocr_result, status = asyncio.run(run())
    assert doc_type == "P7"
    assert ocr_result is None
    assert status == "failed"


def test_s32_review_extraction_results_not_found(client, monkeypatch, tmp_path):
    monkeypatch.setattr(routes, "require_processing_available", lambda: None)
    monkeypatch.setattr(routes, "TEMP_STORAGE", tmp_path)
    response = client.post("/review", data={"ticket": "NO-FILE", "ground_truth": "{}"})
    assert response.status_code == 404


def test_s33_review_ocr_results_empty(client, monkeypatch, tmp_path):
    monkeypatch.setattr(routes, "require_processing_available", lambda: None)
    monkeypatch.setattr(routes, "TEMP_STORAGE", tmp_path)
    ticket = "HAS-JSON"
    ticket_dir = tmp_path / ticket
    ticket_dir.mkdir(parents=True, exist_ok=True)
    (ticket_dir / "extraction_results.json").write_text(json.dumps({"extraction_results": {}}), encoding="utf-8")
    response = client.post("/review", data={"ticket": ticket, "ground_truth": "{}"})
    assert response.status_code == 400


def test_s34_review_orchestrator_exception_returns_500(client, monkeypatch, tmp_path):
    monkeypatch.setattr(routes, "require_processing_available", lambda: None)
    monkeypatch.setattr(routes, "TEMP_STORAGE", tmp_path)

    ticket = "ERR-ORCH"
    ticket_dir = tmp_path / ticket
    ticket_dir.mkdir(parents=True, exist_ok=True)
    (ticket_dir / "extraction_results.json").write_text(
        json.dumps({"extraction_results": {"P7": {"text_content": "valid text content here"}}}),
        encoding="utf-8",
    )

    async def raise_orchestrator(*_args, **_kwargs):
        raise RuntimeError("forced")

    monkeypatch.setattr(routes, "run_unified_review_orchestrator", raise_orchestrator)
    response = client.post("/review", data={"ticket": ticket, "ground_truth": "{}"})
    assert response.status_code == 500


def test_s35_extract_ground_truth_from_ocr_empty_text():
    result = asyncio.run(advance_review_service.extract_ground_truth_from_ocr("KL", "   "))
    assert result["status"] == "error"
    assert "Empty OCR text" in result["error"]


def test_s36_extract_ground_truth_from_ocr_missing_template(monkeypatch):
    monkeypatch.setattr(advance_review_service, "get_prompt_template", lambda *_args, **_kwargs: None)
    result = asyncio.run(advance_review_service.extract_ground_truth_from_ocr("UNKNOWN_DOC", "long enough text"))
    assert result["status"] == "error"
    assert "No extraction template found" in result["error"]


def test_s37_extract_json_from_llm_response_raises_value_error():
    with pytest.raises(ValueError):
        extract_json_from_llm_response("ini bukan json valid sama sekali")


def test_s38_review_single_document_missing_ocr_cache():
    result = asyncio.run(
        advance_review_service.review_single_document(
            filepath="dummy",
            doc_type="PR",
            ground_truth={},
            ocr_cache={},
        )
    )
    assert result["status"] == "error"
    assert "No OCR text available in cache" in result["error"]


def test_s39_review_single_document_invalid_structure(monkeypatch):
    monkeypatch.setattr(advance_review_service, "get_prompt_template", lambda *_args, **_kwargs: "Template {pr_document_text} {ground_truth_json}")

    class DummyChain:
        def run(self, **_kwargs):
            return '{"invalid":"shape"}'

    monkeypatch.setattr(advance_review_service, "LLMChain", lambda **_kwargs: DummyChain())
    monkeypatch.setattr(advance_review_service, "get_llm_instance", lambda *_args, **_kwargs: object())

    result = asyncio.run(
        advance_review_service.review_single_document(
            filepath="dummy",
            doc_type="PR",
            ground_truth={},
            ocr_cache={"PR": "some valid text for review"},
        )
    )
    assert result["status"] == "error"
    assert "Review validation failed" in result["error"]


def test_s40_review_single_document_quality_warning_success(monkeypatch):
    monkeypatch.setattr(advance_review_service, "get_prompt_template", lambda *_args, **_kwargs: "Template {pr_document_text} {ground_truth_json}")

    class DummyChain:
        def run(self, **_kwargs):
            return (
                '{"stage_1_uraian":{"review":"ok","keterangan":"Sudah benar. detail memadai untuk lolos struktur."},'
                '"stage_2_net_price":{"review":"ok","keterangan":"Sudah benar. detail memadai untuk lolos struktur."},'
                '"stage_3_tot_value":{"review":"ok","keterangan":"Sudah benar. detail memadai untuk lolos struktur."},'
                '"stage_4_total_pr":{"review":"ok","keterangan":"Sudah benar. detail memadai untuk lolos struktur."},'
                '"stage_5_month_sequence":{"review":"ok","keterangan":"Sudah benar. detail memadai untuk lolos struktur."},'
                '"stage_6_net_price_consistency":{"review":"ok","keterangan":"Sudah benar. detail memadai untuk lolos struktur."},'
                '"stage_7_tot_value_consistency":{"review":"ok","keterangan":"Sudah benar. detail memadai untuk lolos struktur."},'
                '"stage_8_net_equals_tot":{"review":"ok","keterangan":"Sudah benar. detail memadai untuk lolos struktur."},'
                '"stage_9_total_calculation":{"review":"ok","keterangan":"TODO"}}'
            )

    monkeypatch.setattr(advance_review_service, "LLMChain", lambda **_kwargs: DummyChain())
    monkeypatch.setattr(advance_review_service, "get_llm_instance", lambda *_args, **_kwargs: object())

    result = asyncio.run(
        advance_review_service.review_single_document(
            filepath="dummy",
            doc_type="PR",
            ground_truth={},
            ocr_cache={"PR": "some valid text for review"},
        )
    )
    assert result["status"] == "success"
    assert result["quality_warnings"] is not None
