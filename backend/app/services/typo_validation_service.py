import json
import os
import re
import time
import warnings

from typing import Dict, List
from app.utils.prompt_templates import get_prompt_template
from app.services.ocr_service import find_word_bounding_box
from app.utils.preprocessor_utils import clean_typo_results
from dotenv import load_dotenv
from langchain.chains import LLMChain
from langchain.globals import set_llm_cache
from langchain.prompts import PromptTemplate
from langchain_openai.chat_models import ChatOpenAI

warnings.filterwarnings('ignore')

load_dotenv()

openai_api_key = os.getenv("OPENAI_API_KEY")

set_llm_cache(None)

def select_model(model_type: str) -> ChatOpenAI:

    if model_type == "gpt-4o-mini":
        llm = ChatOpenAI(
            openai_api_key=openai_api_key,
            model="gpt-4o-mini",
            temperature=0,
            frequency_penalty=0.2,
            presence_penalty=0.1,
            response_format={"type": "json_object"},
            cache=False
        )

        return llm

    elif model_type == "gpt-5-mini":

        llm = ChatOpenAI(
            openai_api_key=os.getenv("OPENAI_API_KEY"),
            model="gpt-5-mini",
            temperature=0,
            top_p=1,
            response_format={"type": "json_object"},
            cache=False
        )

        return llm

llm = select_model("gpt-5-mini")

# Prompt yang diperbaiki dengan instruksi lebih jelas
template = get_prompt_template("REVIEW", "TYPO")

prompt_template = PromptTemplate(
    input_variables=['document_text'],
    template=template
)

chain = LLMChain(
    llm=llm,
    prompt=prompt_template
)


def extract_json_from_text(text):
    """
    Ekstrak JSON dari response yang mungkin mengandung teks tambahan
    """
    text = text.strip()

    # Coba parse langsung dulu
    try:
        return json.loads(text)
    except json.JSONDecodeError:
        pass

    # Cari JSON block dengan regex
    patterns = [
        r'\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}',  # Nested JSON
        r'\{.*?\}',  # Simple JSON
    ]

    for pattern in patterns:
        matches = re.findall(pattern, text, re.DOTALL)
        for match in matches:
            try:
                parsed = json.loads(match)
                if isinstance(parsed, dict):
                    return parsed
            except json.JSONDecodeError:
                continue

    # Jika masih gagal, coba bersihkan markdown code blocks
    if '```json' in text:
        json_text = text.split('```json')[1].split('```')[0].strip()
        try:
            return json.loads(json_text)
        except json.JSONDecodeError:
            pass

    if '```' in text:
        json_text = text.split('```')[1].split('```')[0].strip()
        try:
            return json.loads(json_text)
        except json.JSONDecodeError:
            pass

    raise ValueError("Tidak dapat mengekstrak JSON valid dari response")


def validate_json_structure(data):
    """
    Validasi struktur JSON yang lebih ketat
    """
    if not isinstance(data, dict):
        return False, "Response bukan dict"

    # Cek required key
    if "typos" not in data:
        return False, "Missing key: typos"

    # Cek tipe data
    if not isinstance(data["typos"], list):
        return False, f"typos harus list, dapat: {type(data['typos'])}"

    # Validasi setiap typo item
    for i, typo in enumerate(data["typos"]):
        if not isinstance(typo, dict):
            return False, f"typos[{i}] bukan dict"

        required_keys = ["typo_word", "correction_word", "page"]
        for key in required_keys:
            if key not in typo:
                return False, f"typos[{i}] missing key: {key}"

        if not isinstance(typo["typo_word"], str):
            return False, f"typos[{i}].typo_word bukan string"

        if not isinstance(typo["correction_word"], str):
            return False, f"typos[{i}].correction_word bukan string"

        if not isinstance(typo["page"], (int, float)):
            return False, f"typos[{i}].page bukan number"

        # Convert float to int jika perlu
        if isinstance(typo["page"], float):
            typo["page"] = int(typo["page"])

    return True, "Valid"


def add_bounding_boxes_to_typos(typos: List[Dict], words_data: List[Dict], min_confidence: float = 0.87) -> List[Dict]:
    """
    Menambahkan bounding box ke setiap typo dan menghapus typo yang tidak memiliki bounding box
    atau confidence level di bawah threshold

    Args:
        typos (list): List typo dari hasil LLM
        words_data (list): Data kata-kata dari OCR
        min_confidence (float): Minimum confidence level (default: 0.87)

    Returns:
        list: List typo yang sudah dilengkapi dengan bounding box dan memenuhi confidence threshold
    """
    valid_typos = []
    removed_count = 0
    low_confidence_count = 0

    for typo in typos:
        typo_word = typo['typo_word']
        page = typo['page']

        # Cari bounding box untuk typo_word
        bounding_boxes = find_word_bounding_box(words_data, typo_word, case_sensitive=False)

        # Filter bounding box yang sesuai dengan page
        page_specific_boxes = [bbox for bbox in bounding_boxes if bbox['page'] == page]

        # Jika tidak ada bounding box yang ditemukan, skip typo ini
        if not page_specific_boxes:
            removed_count += 1
            continue

        # Filter berdasarkan confidence level
        high_confidence_boxes = [
            bbox for bbox in page_specific_boxes
            if bbox.get('confidence', 0) >= min_confidence
        ]

        # Jika tidak ada bounding box dengan confidence tinggi, skip typo ini
        if not high_confidence_boxes:
            low_confidence_count += 1
            continue

        # Ambil bounding box pertama jika ada multiple match
        selected_bbox = high_confidence_boxes[0]

        # Tambahkan bounding box dan confidence ke typo
        typo_with_bbox = {
            'typo_word': typo['typo_word'],
            'correction_word': typo['correction_word'],
            'page': typo['page'],
            'bbox': selected_bbox['bbox'],
            'confidence': selected_bbox['confidence']  # Tambahkan confidence info
        }

        valid_typos.append(typo_with_bbox)

    return valid_typos


def validate_text_typos(text_dictionary: Dict, max_retries: int = 3, min_confidence: float = 0.87) -> List[Dict]:

    document_text = text_dictionary['text_content']
    words_data = text_dictionary.get('words_data', [])

    for attempt in range(max_retries):
        try:
            # Jalankan chain
            result = chain.run(document_text=document_text)

            print(result)

            # Extract JSON dari response
            json_data = extract_json_from_text(result)

            # Validasi struktur
            is_valid, message = validate_json_structure(json_data)

            if is_valid:
                # Ambil list typos
                typos = json_data['typos']

                # Post-processing: Bersihkan false positives
                if typos:
                    # Bersihkan dulu dari false positives
                    typos = clean_typo_results(typos)

                    # Lalu tambahkan bounding box dengan filter confidence
                    typos = add_bounding_boxes_to_typos(typos, words_data, min_confidence=min_confidence)

                # Return list typos (bisa kosong atau berisi objek typo)
                return typos
            else:
                print(f"✗ Attempt {attempt + 1}: {message}")

        except ValueError as e:
            print(f"✗ Attempt {attempt + 1}: {str(e)}")

        except json.JSONDecodeError as e:
            print(f"✗ Attempt {attempt + 1}: JSON decode error - {str(e)}")

        except Exception as e:
            print(f"✗ Attempt {attempt + 1}: Unexpected error - {str(e)}")

        # Tunggu sebelum retry (exponential backoff)
        if attempt < max_retries - 1:
            wait_time = (attempt + 1) * 1.5
            time.sleep(wait_time)

    return []
