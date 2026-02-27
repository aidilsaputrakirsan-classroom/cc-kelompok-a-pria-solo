from enum import Enum
from typing import Dict, Any, Optional, List

from pydantic import BaseModel


# Document Validation
class ValidationStatus(str, Enum):
    PROCESSING = "processing"
    COMPLETED = "completed"
    ERROR = "error"
    ACCEPTED = "accepted"


class ValidatorResult(BaseModel):
    """Base model for individual validator results"""
    success: Optional[bool] = None
    message: Optional[str] = None
    data: Optional[Dict[str, Any]] = None
    error: Optional[str] = None


class FileValidationResult(BaseModel):
    """Result for a single file validation"""
    typo_checker: List[Dict[str, Any]]
    price_validator: List[Dict[str, Any]]
    date_validator: List[Dict[str, Any]]
    text_extracted: bool = True
    file_size: Optional[str] = None
    file_path: Optional[str] = None
    status: str = "success"
    processing_error: Optional[str] = None


class ValidationResponse(BaseModel):
    """Response for single file validation"""
    filename: str
    status: ValidationStatus
    results: FileValidationResult
    message: str = "Validation completed"
    processing_time: Optional[float] = None


class MultiFileValidationResponse(BaseModel):
    total_files: int
    main_filename: str = ""
    ticket: str
    version: int
    status: str
    results: dict
    message: str
    failed_files: int = 0
    successful_files: int = 0
    processing_time: Optional[float] = None

    # Metadata baru untuk extracted PDF
    extracted_pdf_path: Optional[str] = None
    page_mapping: Dict[int, int] = {}
    total_pages_in_original: Optional[int] = None
    total_pages_with_errors: Optional[int] = None


class AsyncTaskResponse(BaseModel):
    """Response for async task creation"""
    task_id: str
    status: ValidationStatus
    total_files: int
    message: str


class AsyncTaskStatus(BaseModel):
    """Status of async task"""
    status: ValidationStatus
    total_files: int
    completed_files: int = 0
    results: Dict[str, FileValidationResult] = {}
    created_at: str
    completed_at: Optional[str] = None
    error_at: Optional[str] = None
    message: str = "Processing..."
    processing_errors: Optional[Dict[str, str]] = None


class HealthResponse(BaseModel):
    """Health check response"""
    status: str = "healthy"
    timestamp: str
    version: str = "1.0.0"


class ErrorResponse(BaseModel):
    """Standard error response"""
    error: str
    detail: str
    status_code: int
    timestamp: str


# Information Extraction
class FileExtractionResult(BaseModel):
    """Schema untuk hasil ekstraksi per file"""
    filename: str
    file_type: str
    status: str
    extracted_data: dict = None
    error: str = None


class InformationExtractionResponse(BaseModel):
    """Schema untuk response endpoint"""
    total_files: int
    successful: int
    failed: int
    status: str
    supported_file_types: List[str]
    results: dict
    message: str
    processing_time: str = None
