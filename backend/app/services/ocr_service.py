import logging
import os
import re
import warnings

import fitz
from app.utils.preprocessor_utils import preprocess_ocr_indonesian
from azure.ai.documentintelligence import DocumentIntelligenceClient
from azure.core.credentials import AzureKeyCredential
from dotenv import load_dotenv

logging.getLogger("azure").setLevel(logging.ERROR)

warnings.filterwarnings('ignore')
load_dotenv()

azure_di_endpoint = os.getenv("AZURE_DI_PROJESSAI_ENDPOINT")
azure_di_key = os.getenv("AZURE_DI_PROJESSAI_KEY")

document_intelligence_client = DocumentIntelligenceClient(
    endpoint=azure_di_endpoint,
    credential=AzureKeyCredential(azure_di_key)
)


def extract_text_based_pdf(filepath: str, doc_name: str = None):

    lines = []

    # Default doc_name jika tidak diberikan
    if not doc_name:
        from pathlib import Path
        doc_name = Path(filepath).stem

    try:
        doc = fitz.open(filepath)

        for page_num in range(len(doc)):
            page = doc.load_page(page_num)
            page_number = page_num + 1  # 1-indexed

            # Add page marker
            lines.append(f'<<<[{doc_name}]-HALAMAN-{page_number}>>>')

            # Gunakan dict mode untuk detail posisi
            text_dict = page.get_text("dict")

            for block in text_dict["blocks"]:
                if "lines" in block:  # Text block
                    for line in block["lines"]:
                        line_text = ""
                        for span in line["spans"]:
                            line_text += span["text"]
                        if line_text.strip():
                            lines.append(line_text.strip())

        doc.close()

        # Join all lines dengan spasi
        text_content = ' '.join(lines)

        return {
            'text_content': text_content
        }

    except Exception as e:
        print(f"Error Membuka PDF: {e}")
        return {
            'text_content': ''
        }


def extract_document_content_di(filepath: str, doc_name: str = None):

    # Default doc_name jika tidak diberikan
    if not doc_name:
        from pathlib import Path
        doc_name = Path(filepath).stem

    try:
        # Baca file PDF lokal
        with open(filepath, "rb") as f:
            pdf_content = f.read()

        # Analisis dokumen
        poller = document_intelligence_client.begin_analyze_document(
            model_id="prebuilt-read",
            body=pdf_content,
            content_type="application/pdf"
        )

        result = poller.result()

        text_paragraphs = []
        paragraphs_data = []

        # STEP 1: Build paragraphs data dengan span mapping
        paragraph_spans = []

        for idx, paragraph in enumerate(result.paragraphs):
            if paragraph.spans:
                span = paragraph.spans[0]
                start_offset = span.offset
                end_offset = span.offset + span.length

                # PREPROCESSING: Bersihkan paragraph dari karakter aneh
                cleaned_paragraph = preprocess_ocr_indonesian(paragraph.content)

                if cleaned_paragraph.strip():
                    # Get page and bounding region
                    page_number = 1
                    polygon = []
                    if paragraph.bounding_regions:
                        page_number = paragraph.bounding_regions[0].page_number
                        polygon = paragraph.bounding_regions[0].polygon

                    paragraph_spans.append({
                        'id': idx,
                        'start': start_offset,
                        'end': end_offset,
                        'content': cleaned_paragraph,
                        'page': page_number,
                        'polygon': polygon
                    })

        # STEP 2: Build paragraphs_data structure dengan nested words
        for para_span in paragraph_spans:
            paragraphs_data.append({
                'paragraph_id': para_span['id'],
                'page': para_span['page'],
                'content': para_span['content'],
                'bounding_region': para_span['polygon'],
                'words': []  # Will be populated in next step
            })

        # STEP 3: Ekstraksi words dan map ke paragraph yang sesuai
        for page in result.pages:
            page_number = page.page_number

            for word in page.words:
                # Konversi polygon ke rectangle format [x, y, width, height]
                if word.polygon and len(word.polygon) >= 8:
                    x_coords = [word.polygon[i] for i in range(0, 8, 2)]
                    y_coords = [word.polygon[i] for i in range(1, 8, 2)]
                    x_min, x_max = min(x_coords), max(x_coords)
                    y_min, y_max = min(y_coords), max(y_coords)
                    rectangle = [x_min, y_min, x_max - x_min, y_max - y_min]
                else:
                    rectangle = [0, 0, 0, 0]

                # PREPROCESSING: Bersihkan kata dari karakter aneh
                cleaned_word = preprocess_ocr_indonesian(word.content)

                # Ambil confidence level dari word
                confidence = word.confidence if hasattr(word, 'confidence') else 1.0

                # Hanya simpan jika kata masih ada setelah dibersihkan
                if cleaned_word.strip():
                    # MAPPING: Cari paragraph mana yang mengandung word ini
                    word_offset = word.span.offset

                    for para_span in paragraph_spans:
                        if para_span['start'] <= word_offset < para_span['end']:
                            # Tambahkan word ke paragraph yang sesuai
                            para_id = para_span['id']

                            # Cari paragraph di paragraphs_data
                            for para_data in paragraphs_data:
                                if para_data['paragraph_id'] == para_id:
                                    para_data['words'].append({
                                        'word': cleaned_word,
                                        'rectangle': rectangle,
                                        'confidence': confidence
                                    })
                                    break
                            break

        # STEP 4: Build text_content untuk validation (dengan penanda halaman dan nama dokumen)
        current_page = 1
        text_paragraphs.append(f'<<<[{doc_name}]-HALAMAN-{current_page}>>>')

        for para_data in paragraphs_data:
            # Cek apakah paragraph ini di halaman baru
            if para_data['page'] > current_page:
                current_page = para_data['page']
                text_paragraphs.append(f'<<<[{doc_name}]-HALAMAN-{current_page}>>>')

            # Tambahkan content paragraph
            text_paragraphs.append(para_data['content'])

        # Gabungkan semua paragraphs menjadi teks lengkap
        text_content = ' '.join(text_paragraphs)

        # STEP 5: Build flat words_data untuk typo checker (backward compatibility)
        words_data = []
        for para_data in paragraphs_data:
            for word in para_data['words']:
                words_data.append({
                    'page': para_data['page'],
                    'word': word['word'],
                    'rectangle': word['rectangle'],
                    'confidence': word['confidence'],
                    'paragraph_id': para_data['paragraph_id']
                })

        return {
            'text_content': text_content,
            'paragraphs_data': paragraphs_data,
            'words_data': words_data  # Untuk typo checker
        }

    except FileNotFoundError:
        print(f"Error: File {filepath} tidak ditemukan")
        return None
    except Exception as e:
        print(f"Error saat ekstraksi dokumen: {str(e)}")
        return None


def find_bounding_box_with_context(paragraphs_data, full_context, target_numeric, fuzzy_threshold=0.7):

    try:
        if not paragraphs_data or not full_context or not target_numeric:
            return [{'found': False, 'error': 'Invalid input parameters'}]

        full_context_lower = full_context.lower().strip()
        all_matches = []

        # STEP 1: Cari SEMUA paragraph dengan exact match
        matched_paras = []
        for para in paragraphs_data:
            if full_context_lower in para['content'].lower():
                matched_paras.append(para)
                # TIDAK ADA BREAK - lanjut cek paragraph lainnya

        # STEP 2: Jika tidak ada exact match, cari dengan partial match
        if not matched_paras:
            for para in paragraphs_data:
                overlap = calculate_text_overlap(para['content'], full_context)
                if overlap >= fuzzy_threshold:
                    matched_paras.append(para)
                    # TIDAK ADA BREAK - lanjut cek paragraph lainnya

        if not matched_paras:
            return [{
                'found': False,
                'error': 'Context not found in any paragraph',
                'searched_context': full_context[:100] + '...'
            }]

        # STEP 3: Untuk SETIAP paragraph yang match, cari target_numeric
        for matched_para in matched_paras:
            # Buat regex pattern yang flexible untuk handle spasi
            target_pattern = re.escape(target_numeric).replace(r'\-', r'\s*-\s*')

            words = matched_para['words']

            # Cari SEMUA kemunculan dalam paragraph ini
            i = 0
            while i < len(words):
                matched_rectangles = []

                # Gabungkan beberapa words untuk checking
                window_size = min(15, len(words) - i)
                combined_text = ' '.join([w['word'] for w in words[i:i + window_size]])

                # Cek apakah target_numeric ada dalam combined text
                if re.search(target_pattern, combined_text, re.IGNORECASE):
                    # Extract rectangles dari words yang mengandung digit
                    digits_found = ''
                    target_digits = target_numeric.replace('-', '').replace(' ', '')

                    start_idx = i
                    for j in range(i, i + window_size):
                        word = words[j]['word']
                        # Jika word mengandung digit, masukkan ke matched
                        if re.search(r'\d', word):
                            matched_rectangles.append(words[j]['rectangle'])
                            digits_found += re.sub(r'\D', '', word)

                            # Update i untuk skip words yang sudah diproses
                            i = j + 1

                            # Stop jika sudah dapat semua digit
                            if len(digits_found) >= len(target_digits):
                                break

                    # Jika dapat rectangles, simpan hasil
                    if matched_rectangles:
                        union_bbox = calculate_union_rectangle(matched_rectangles)

                        all_matches.append({
                            'found': True,
                            'paragraph_id': matched_para['paragraph_id'],
                            'page': matched_para['page'],
                            'bbox': union_bbox,
                            'matched_words_count': len(matched_rectangles),
                            'paragraph_content': matched_para['content'][:200] + '...'
                        })

                        # TIDAK ADA BREAK - lanjut cari kemunculan berikutnya
                        continue

                # Increment i jika tidak ada match
                i += 1

        # Jika tidak ada match sama sekali
        if not all_matches:
            return [{
                'found': False,
                'error': 'Target numeric not found in any matched paragraph',
                'matched_paragraphs_count': len(matched_paras)
            }]

        return all_matches

    except Exception as e:
        print(f"Error saat pencarian bounding box dengan context: {str(e)}")
        return [{'found': False, 'error': str(e)}]


def calculate_union_rectangle(rectangles):

    if not rectangles:
        return {'x': 0, 'y': 0, 'width': 0, 'height': 0}

    # Convert [x, y, width, height] to (x_min, y_min, x_max, y_max)
    x_mins = [r[0] for r in rectangles]
    y_mins = [r[1] for r in rectangles]
    x_maxs = [r[0] + r[2] for r in rectangles]
    y_maxs = [r[1] + r[3] for r in rectangles]

    # Calculate union
    x_min = min(x_mins)
    y_min = min(y_mins)
    x_max = max(x_maxs)
    y_max = max(y_maxs)

    return {
        'x': x_min,
        'y': y_min,
        'width': x_max - x_min,
        'height': y_max - y_min
    }


def calculate_text_overlap(text1, text2):

    text1_lower = text1.lower().strip()
    text2_lower = text2.lower().strip()

    # Simple word-based overlap
    words1 = set(text1_lower.split())
    words2 = set(text2_lower.split())

    if not words2:
        return 0.0

    intersection = words1.intersection(words2)
    overlap_ratio = len(intersection) / len(words2)

    return overlap_ratio


def find_word_bounding_box(words_data, search_word, case_sensitive=False):

    try:
        if not words_data or not search_word:
            return []

        found_words = []
        search_term = search_word if case_sensitive else search_word.lower()

        for word_data in words_data:
            word_content = word_data['word']
            compare_word = word_content if case_sensitive else word_content.lower()

            if search_term == compare_word or search_term in compare_word:
                rect = word_data['rectangle']
                found_words.append({
                    'page': word_data['page'],
                    'word': word_data['word'],
                    'bbox': {
                        'x': rect[0],
                        'y': rect[1],
                        'width': rect[2],
                        'height': rect[3]
                    },
                    'confidence': word_data.get('confidence', 1.0)
                })

        return found_words

    except Exception as e:
        print(f"Error saat pencarian bounding box: {str(e)}")
        return []

# print(extract_document_content_di(r"C:\Users\ASUS\Documents\Dokumen Review\NPK\7.NPK (9).pdf")['text_content'])