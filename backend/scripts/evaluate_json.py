#!/usr/bin/env python3
"""
Evaluasi akurasi OCR: pasangan file JSON (hasil ekstraksi) dan TXT (ground truth).

Menemukan semua *.json di direktori kerja, mencocokkan {stem}.txt dari direktori
ground truth, mengekstrak text_content dari extraction_results (kunci jenis dokumen
dinamis), lalu menghitung similarity difflib.SequenceMatcher.
"""

from __future__ import annotations

import argparse
import json
import re
import sys
from dataclasses import dataclass
from difflib import SequenceMatcher
from pathlib import Path
from time import perf_counter


@dataclass
class DocumentEvalResult:
    no: int
    ticket_name: str
    doc_type: str
    original_char_count: int
    duration_seconds: float
    accuracy_percent: float
    status: str
    error: str = ""


def normalize_text(text: str, strip_page_markers: bool = True) -> str:
    cleaned = text
    if strip_page_markers:
        cleaned = re.sub(r"<<<\[[^\]]+\]-HALAMAN-\d+>>>", " ", cleaned)
    cleaned = cleaned.replace("\r\n", "\n").replace("\r", "\n")
    cleaned = re.sub(r"\s+", " ", cleaned).strip()
    return cleaned


def extract_ocr_payload(data: dict) -> tuple[str, str]:
    """
    Ambil (jenis_dokumen, teks_ocr) dari extraction_results tanpa hardcode nama kunci.

    Satu kunci  -> gunakan text_content dokumen tersebut.
    Beberapa kunci (paket tiket) -> gabungkan semua text_content menurut urutan di JSON
    agar dapat dibandingkan dengan ground truth tiket lengkap.
    """
    extraction_results = data.get("extraction_results")
    if not isinstance(extraction_results, dict) or not extraction_results:
        raise ValueError("Field extraction_results kosong atau tidak valid.")

    doc_types: list[str] = []
    text_parts: list[str] = []

    for doc_type, doc_data in extraction_results.items():
        if not isinstance(doc_data, dict):
            raise ValueError(f"Data dokumen untuk '{doc_type}' bukan objek JSON.")
        text_content = doc_data.get("text_content", "")
        if not isinstance(text_content, str):
            raise ValueError(f"Field text_content pada '{doc_type}' bukan string.")
        doc_types.append(str(doc_type))
        text_parts.append(text_content)

    if len(doc_types) == 1:
        label = doc_types[0]
    else:
        label = f"Paket ({len(doc_types)} dokumen)"
        print(
            f"[INFO] {len(doc_types)} jenis dokumen digabung: {', '.join(doc_types)}",
            file=sys.stderr,
        )

    merged_text = " ".join(text_parts)
    return label, merged_text


def similarity_ratio(a: str, b: str) -> float:
    return SequenceMatcher(None, a, b).ratio()


def discover_json_files(directory: Path) -> list[Path]:
    return sorted(directory.glob("*.json"), key=lambda p: p.name.lower())


def resolve_ground_truth(json_path: Path, ground_truth_dir: Path) -> Path:
    return ground_truth_dir / f"{json_path.stem}.txt"


def evaluate_pair(json_path: Path, ground_truth_path: Path) -> DocumentEvalResult:
    ticket_name = json_path.stem
    started = perf_counter()

    try:
        data = json.loads(json_path.read_text(encoding="utf-8"))
        doc_type, raw_ocr = extract_ocr_payload(data)
        raw_gt = ground_truth_path.read_text(encoding="utf-8")

        ocr_text = normalize_text(raw_ocr, strip_page_markers=True)
        gt_text = normalize_text(raw_gt, strip_page_markers=False)
        original_char_count = len(gt_text)

        accuracy_percent = similarity_ratio(ocr_text, gt_text) * 100.0
        duration_seconds = perf_counter() - started

        return DocumentEvalResult(
            no=0,
            ticket_name=ticket_name,
            doc_type=doc_type,
            original_char_count=original_char_count,
            duration_seconds=duration_seconds,
            accuracy_percent=accuracy_percent,
            status="ok",
        )
    except Exception as err:  # noqa: BLE001
        return DocumentEvalResult(
            no=0,
            ticket_name=ticket_name,
            doc_type="",
            original_char_count=0,
            duration_seconds=perf_counter() - started,
            accuracy_percent=0.0,
            status="error",
            error=str(err),
        )


def format_markdown_table(results: list[DocumentEvalResult]) -> str:
    lines = [
        "| No | Nama Dokumen (Tiket) | Jenis Dokumen | Jumlah Karakter Asli | "
        "Durasi Pemrosesan (Detik) | Tingkat Akurasi (%) |",
        "| ---: | --- | --- | ---: | ---: | ---: |",
    ]
    for row in results:
        accuracy = f"{row.accuracy_percent:.2f}" if row.status == "ok" else "—"
        duration = f"{row.duration_seconds:.4f}" if row.status == "ok" else "—"
        doc_type = row.doc_type if row.doc_type else "—"
        char_count = str(row.original_char_count) if row.status == "ok" else "—"
        lines.append(
            f"| {row.no} | {row.ticket_name} | {doc_type} | {char_count} | "
            f"{duration} | {accuracy} |"
        )
    return "\n".join(lines)


def format_summary(results: list[DocumentEvalResult]) -> str:
    ok_rows = [r for r in results if r.status == "ok"]
    failed = [r for r in results if r.status != "ok"]

    lines = ["## Ringkasan Statistik", ""]
    lines.append(f"- **Jumlah dokumen dievaluasi:** {len(results)}")
    lines.append(f"- **Berhasil:** {len(ok_rows)}")
    if failed:
        lines.append(f"- **Gagal:** {len(failed)}")
        for row in failed:
            lines.append(f"  - `{row.ticket_name}`: {row.error}")

    if ok_rows:
        accuracies = [r.accuracy_percent for r in ok_rows]
        avg_accuracy = sum(accuracies) / len(accuracies)
        best = max(ok_rows, key=lambda r: r.accuracy_percent)
        worst = min(ok_rows, key=lambda r: r.accuracy_percent)
        total_duration = sum(r.duration_seconds for r in ok_rows)

        lines.append(f"- **Rata-rata akurasi:** {avg_accuracy:.2f}%")
        lines.append(
            f"- **Akurasi tertinggi:** {best.accuracy_percent:.2f}% "
            f"(`{best.ticket_name}` / {best.doc_type})"
        )
        lines.append(
            f"- **Akurasi terendah:** {worst.accuracy_percent:.2f}% "
            f"(`{worst.ticket_name}` / {worst.doc_type})"
        )
        lines.append(f"- **Total durasi pemrosesan:** {total_duration:.4f} detik")
    else:
        lines.append("- **Rata-rata akurasi:** — (tidak ada dokumen valid)")

    return "\n".join(lines)


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Evaluasi batch akurasi OCR dari pasangan JSON + TXT ground truth."
    )
    parser.add_argument(
        "--json-dir",
        type=Path,
        default=Path("."),
        help="Direktori berisi file JSON hasil OCR (default: direktori kerja saat ini).",
    )
    parser.add_argument(
        "--ground-truth-dir",
        type=Path,
        default=None,
        help="Direktori berisi file TXT ground truth (default: sama dengan --json-dir).",
    )
    parser.add_argument(
        "--output-md",
        type=Path,
        default=None,
        help="Opsional: simpan laporan Markdown ke file.",
    )
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    json_dir = args.json_dir.resolve()
    ground_truth_dir = (
        args.ground_truth_dir.resolve()
        if args.ground_truth_dir is not None
        else json_dir
    )

    if not json_dir.is_dir():
        print(f"[ERROR] Direktori JSON tidak ditemukan: {json_dir}", file=sys.stderr)
        return 1
    if not ground_truth_dir.is_dir():
        print(
            f"[ERROR] Direktori ground truth tidak ditemukan: {ground_truth_dir}",
            file=sys.stderr,
        )
        return 1

    json_files = discover_json_files(json_dir)
    if not json_files:
        print(f"[ERROR] Tidak ada file *.json di {json_dir}", file=sys.stderr)
        return 1

    results: list[DocumentEvalResult] = []
    for idx, json_path in enumerate(json_files, start=1):
        gt_path = resolve_ground_truth(json_path, ground_truth_dir)
        if not gt_path.exists():
            results.append(
                DocumentEvalResult(
                    no=idx,
                    ticket_name=json_path.stem,
                    doc_type="",
                    original_char_count=0,
                    duration_seconds=0.0,
                    accuracy_percent=0.0,
                    status="error",
                    error=f"Ground truth tidak ditemukan: {gt_path}",
                )
            )
            continue

        row = evaluate_pair(json_path, gt_path)
        row.no = idx
        results.append(row)

    report_parts = [
        "## Hasil Evaluasi Akurasi OCR",
        "",
        format_markdown_table(results),
        "",
        format_summary(results),
    ]
    report = "\n".join(report_parts)
    print(report)

    if args.output_md is not None:
        args.output_md.parent.mkdir(parents=True, exist_ok=True)
        args.output_md.write_text(report + "\n", encoding="utf-8")
        print(f"\n[Laporan disimpan ke {args.output_md}]", file=sys.stderr)

    has_errors = any(r.status != "ok" for r in results)
    return 1 if has_errors else 0


if __name__ == "__main__":
    raise SystemExit(main())
