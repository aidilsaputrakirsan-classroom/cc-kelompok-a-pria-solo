import copy
from typing import Dict, Any, Set


def merge_validation_results(
        chunk_results: Dict[str, Any],
        original_filename: str
) -> Dict[str, Any]:

    merged = {
        "typo_checker": [],
        "price_validator": [],
        "date_validator": [],
        "text_extracted": True,
        "status": "success",
        "processing_error": None
    }

    # Gabungkan dari semua chunk
    for chunk_path, chunk_result in chunk_results.items():
        # Merge typo_checker
        if chunk_result.get('typo_checker'):
            if isinstance(chunk_result['typo_checker'], list):
                merged['typo_checker'].extend(chunk_result['typo_checker'])
            elif isinstance(chunk_result['typo_checker'], dict) and 'error' not in chunk_result['typo_checker']:
                merged['typo_checker'].append(chunk_result['typo_checker'])

        # Merge price_validator
        if chunk_result.get('price_validator'):
            if isinstance(chunk_result['price_validator'], list):
                merged['price_validator'].extend(chunk_result['price_validator'])
            elif isinstance(chunk_result['price_validator'], dict) and 'error' not in chunk_result['price_validator']:
                merged['price_validator'].append(chunk_result['price_validator'])

        # Merge date_validator
        if chunk_result.get('date_validator'):
            if isinstance(chunk_result['date_validator'], list):
                merged['date_validator'].extend(chunk_result['date_validator'])
            elif isinstance(chunk_result['date_validator'], dict) and 'error' not in chunk_result['date_validator']:
                merged['date_validator'].append(chunk_result['date_validator'])

        # Update status jika ada error
        if chunk_result.get('status') != 'success':
            merged['status'] = chunk_result['status']
            if chunk_result.get('processing_error'):
                merged['processing_error'] = chunk_result['processing_error']

    # Sort by page number untuk kemudahan
    merged['typo_checker'].sort(key=lambda x: x.get('page', 0))
    merged['price_validator'].sort(
        key=lambda x: x['bounding_box'][0].get('page', 0) if x.get('bounding_box') else 0
    )
    merged['date_validator'].sort(
        key=lambda x: x['bounding_box'][0].get('page', 0) if x.get('bounding_box') else 0
    )

    return {original_filename: merged}


def get_all_error_pages(validation_results: Dict[str, Any]) -> Set[int]:

    error_pages = set()

    for file_result in validation_results.values():
        # Dari typo_checker
        if 'typo_checker' in file_result and isinstance(file_result['typo_checker'], list):
            for typo in file_result['typo_checker']:
                if 'page' in typo and isinstance(typo['page'], int):
                    error_pages.add(typo['page'])

        # Dari price_validator
        if 'price_validator' in file_result and isinstance(file_result['price_validator'], list):
            for price in file_result['price_validator']:
                if 'bounding_box' in price and isinstance(price['bounding_box'], list):
                    for bbox in price['bounding_box']:
                        if 'page' in bbox and isinstance(bbox['page'], int):
                            error_pages.add(bbox['page'])

        # Dari date_validator
        if 'date_validator' in file_result and isinstance(file_result['date_validator'], list):
            for date in file_result['date_validator']:
                if 'bounding_box' in date and isinstance(date['bounding_box'], list):
                    for bbox in date['bounding_box']:
                        if 'page' in bbox and isinstance(bbox['page'], int):
                            error_pages.add(bbox['page'])

    return error_pages


def create_page_mapping(error_pages: Set[int]) -> Dict[int, int]:

    sorted_pages = sorted(error_pages)
    mapping = {original_page: idx + 1 for idx, original_page in enumerate(sorted_pages)}
    return mapping


def add_page_in_file_to_results(
        validation_results: Dict[str, Any],
        page_mapping: Dict[int, int]
) -> Dict[str, Any]:

    results = copy.deepcopy(validation_results)

    for file_result in results.values():
        # Update typo_checker
        if 'typo_checker' in file_result and isinstance(file_result['typo_checker'], list):
            for typo in file_result['typo_checker']:
                if 'page' in typo:
                    typo['page_in_file'] = page_mapping.get(typo['page'])

        # Update price_validator
        if 'price_validator' in file_result and isinstance(file_result['price_validator'], list):
            for price in file_result['price_validator']:
                if 'bounding_box' in price and isinstance(price['bounding_box'], list):
                    for bbox in price['bounding_box']:
                        if 'page' in bbox:
                            bbox['page_in_file'] = page_mapping.get(bbox['page'])

        # Update date_validator
        if 'date_validator' in file_result and isinstance(file_result['date_validator'], list):
            for date in file_result['date_validator']:
                if 'bounding_box' in date and isinstance(date['bounding_box'], list):
                    for bbox in date['bounding_box']:
                        if 'page' in bbox:
                            bbox['page_in_file'] = page_mapping.get(bbox['page'])

    return results
