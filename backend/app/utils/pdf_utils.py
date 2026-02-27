import os
from pathlib import Path
from typing import List, Union, Dict

import fitz
from dotenv import load_dotenv

load_dotenv()
TOTAL_CHUNKS = int(os.getenv("CHUNK_SIZE"))


def split_pdf(
        original_filename: str,
        input_pdf: Union[str, Path],
        output_folder: Union[str, Path],
        pages_per_chunk: int = TOTAL_CHUNKS
) -> Dict[str, str]:

    input_pdf = Path(input_pdf)
    output_folder = Path(output_folder)
    output_folder.mkdir(parents=True, exist_ok=True)

    doc = fitz.open(input_pdf)
    total_pages = doc.page_count
    file_mapping = {}

    stem = input_pdf.stem
    chunk_count = 0

    for start_page in range(0, total_pages, pages_per_chunk):
        chunk_count += 1
        end_page = min(start_page + pages_per_chunk, total_pages)

        output_doc = fitz.open()
        output_doc.insert_pdf(doc, from_page=start_page, to_page=end_page - 1)

        chunk_filename = f"{stem}_part_{chunk_count}.pdf"
        chunk_path = output_folder / chunk_filename
        output_doc.save(str(chunk_path))
        output_doc.close()

        file_mapping[str(chunk_path)] = original_filename

    doc.close()
    return file_mapping


def extract_pdf_pages(
        input_pdf: Union[str, Path],
        output_pdf: Union[str, Path],
        pages: Union[List[int], List[str]]
) -> bool:
    input_pdf = Path(input_pdf)
    output_pdf = Path(output_pdf)

    if not input_pdf.exists():
        return False

    try:
        page_list = []
        for page in pages:
            if isinstance(page, str):
                page = page.strip()
                if '-' in page:
                    start, end = map(int, page.split('-'))
                    page_list.extend(range(start, end + 1))
                else:
                    page_list.append(int(page))
            else:
                page_list.append(int(page))

        page_list = sorted(set(page_list))

        doc = fitz.open(input_pdf)
        total_pages = doc.page_count

        valid_pages = [p for p in page_list if 1 <= p <= total_pages]
        if not valid_pages:
            doc.close()
            return False

        output_doc = fitz.open()
        for page_num in valid_pages:
            output_doc.insert_pdf(doc, from_page=page_num - 1, to_page=page_num - 1)

        output_pdf.parent.mkdir(parents=True, exist_ok=True)
        output_doc.save(output_pdf)

        doc.close()
        output_doc.close()
        return True

    except Exception:
        return False


def get_total_pages(pdf_path: Union[str, Path]) -> int:
    """Get total pages dari PDF"""
    try:
        doc = fitz.open(pdf_path)
        total = doc.page_count
        doc.close()
        return total
    except Exception:
        return 0
