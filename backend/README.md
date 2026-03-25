<p align="center">
  <a href="https://fastapi.tiangolo.com"><img src="https://fastapi.tiangolo.com/img/logo-margin/logo-teal.png" alt="FastAPI"></a>
</p>

# Document Validator API - Backend

API Backend untuk sistem validasi dokumen berbasis AI yang menggunakan Azure Document Intelligence dan OpenAI untuk ekstraksi dan validasi data dokumen secara otomatis.

## 📋 Deskripsi Proyek

Sistem ini dirancang untuk memproses dokumen PDF, mengekstrak informasi menggunakan OCR (Optical Character Recognition), dan melakukan validasi otomatis terhadap data yang diekstrak. Proyek ini merupakan bagian dari sistem verifikasi dokumen untuk Telkom.

### Fitur Utama

- ✅ **Ekstraksi Informasi Dokumen**: Ekstraksi otomatis teks dan data dari file PDF menggunakan Azure Document Intelligence
- ✅ **Validasi Multi-Layer**: Validasi typo, format tanggal, dan nilai harga
- ✅ **Review Berbasis AI**: Menggunakan LangChain dan OpenAI untuk review dokumen tingkat lanjut
- ✅ **Pemrosesan Asinkron**: Menangani multiple dokumen secara concurrent dengan performa tinggi
- ✅ **Manajemen Tiket**: Sistem berbasis tiket untuk tracking dan organizing dokumen
- ✅ **Ground Truth Validation**: Membandingkan hasil ekstraksi dengan data referensi

## 🛠️ Tech Stack

### Framework & Web Server
- **FastAPI** (v0.116.1) - Modern async web framework untuk Python
- **Uvicorn** (v0.35.0) - ASGI server untuk production
- **Starlette** (v0.47.2) - ASGI framework (basis FastAPI)

### AI & Document Processing
- **Azure AI Document Intelligence** (v1.0.2) - OCR dan ekstraksi dokumen
- **Azure Cognitive Services Vision** (v0.9.1) - Computer vision capabilities
- **LangChain** (v0.3.27) - Framework untuk orkestrasi LLM
- **OpenAI** (v1.100.2) - GPT models untuk analisis dokumen
- **LangSmith** (v0.4.14) - Monitoring dan debugging LLM

### PDF & Image Processing
- **PyMuPDF** (v1.26.3) - Parsing dan manipulasi PDF
- **pdf2image** (v1.17.0) - Konversi PDF ke gambar
- **pypdfium2** (v4.30.0) - PDF rendering
- **Pillow** (v11.3.0) - Image processing

### Data Validation & Serialization
- **Pydantic** (v2.11.7) - Data validation dan schema
- **pydantic-settings** (v2.10.1) - Configuration management
- **orjson** (v3.11.2) - Fast JSON serialization

### Database & Data Processing
- **SQLAlchemy** (v2.0.43) - SQL toolkit dan ORM
- **Pandas** (v2.3.2) - Data manipulation dan analysis
- **NumPy** (v2.2.6) - Numerical computing
- **SciPy** (v1.16.1) - Scientific computing

### Development & Testing
- **Pytest** (v8.4.1) - Testing framework
- **python-dotenv** (v1.1.1) - Environment variable management
- **colorlog** (v6.9.0) - Colored logging output

## 📁 Struktur Proyek

```
backend/
├── app/
│   ├── api/
│   │   ├── __init__.py
│   │   └── routes.py              # API endpoints
│   ├── orchestrators/
│   │   ├── __init__.py
│   │   ├── document_extraction_orchestrator.py    # Orkestrasi ekstraksi
│   │   ├── document_validator_orchestrator.py     # Orkestrasi validasi
│   │   ├── document_advance_orchestrator.py       # Advanced review
│   │   └── unified_review_orchestrator.py         # Unified review workflow
│   ├── services/
│   │   ├── __init__.py
│   │   ├── ocr_service.py                        # Azure OCR integration
│   │   ├── advance_review_service.py             # AI review service
│   │   ├── date_validation_service.py            # Validasi tanggal
│   │   ├── price_validation_service.py           # Validasi harga
│   │   └── typo_validation_service.py            # Validasi typo
│   ├── schemas/
│   │   ├── __init__.py
│   │   └── document_validator_schema.py          # Pydantic schemas
│   ├── utils/
│   │   ├── pdf_utils.py                          # PDF processing utilities
│   │   ├── ground_truth_calculation_utils.py     # Ground truth logic
│   │   ├── date_validation_utils.py              # Date helpers
│   │   └── advance_review_utils.py               # Review helpers
│   └── main.py                                    # Application entry point
├── temp/                                          # Temporary storage (git-ignored)
├── .env                                           # Environment variables (git-ignored)
├── .env.example                                   # Environment template
├── .gitignore
├── requirements.txt                               # Python dependencies
└── README.md
```

## 🏗️ Arsitektur Sistem

Sistem ini menggunakan **Layered Architecture** dengan pemisahan tanggung jawab yang jelas:

### 1. API Layer (`app/api/`)
- Menangani HTTP requests dan responses
- Validasi input dari user
- Routing dan endpoint management

### 2. Orchestrator Layer (`app/orchestrators/`)
- Mengatur workflow dan business logic
- Koordinasi antar services
- Manajemen concurrent processing dengan thread pools dan semaphores
- Error handling dan retry logic

### 3. Service Layer (`app/services/`)
- Implementasi business logic spesifik
- Integrasi dengan external services (Azure, OpenAI)
- Validasi data (typo, date, price)
- OCR dan text extraction

### 4. Utility Layer (`app/utils/`)
- Helper functions dan utilities
- PDF processing
- Data transformation
- Preprocessing untuk bahasa Indonesia

## 🚀 Instalasi & Setup

### Prerequisites
- Python 3.9 atau lebih tinggi
- Azure Account dengan Document Intelligence service
- OpenAI API key
- pip (Python package manager)

### Langkah Instalasi

1. **Clone Repository**
```bash
git clone <repository-url>
cd backend
```

2. **Buat Virtual Environment**
```bash
python -m venv venv
source venv/bin/activate  # Linux/Mac
# atau
venv\Scripts\activate     # Windows
```

3. **Install Dependencies**
```bash
pip install -r requirements.txt
```

4. **Setup Environment Variables**
```bash
cp .env.example .env
```

Edit file `.env` dan isi dengan credentials Anda:
```env
AZURE_DI_PROJESSAI_ENDPOINT=your_azure_endpoint
AZURE_DI_PROJESSAI_KEY=your_azure_key
OPENAI_API_KEY=your_openai_key
TEMP_STORAGE=./temp
```

5. **Jalankan Server**  
   Gunakan port **8001** jika Laravel sudah memakai **8000** (setup standar monorepo pria-solo).
```bash
uvicorn app.main:app --reload --host 0.0.0.0 --port 8001
```

Server akan berjalan di `http://127.0.0.1:8001`

## 📡 API Endpoints

### 1. Health Check
```http
GET /
GET /health
```

**Response:**
```json
{
  "status": "healthy",
  "timestamp": "2026-02-27T10:00:00"
}
```

### 2. Information Extraction
```http
POST /information-extraction
Content-Type: multipart/form-data
```

**Parameters:**
- `ticket` (string, required): Ticket ID untuk tracking
- `files` (file[], required): Array of PDF files

**Response:**
```json
{
  "status": "completed",
  "ticket": "TICKET-001",
  "total_files": 3,
  "ocr_extraction_success": 3,
  "ground_truth_results": {
    "P7": { "extracted_data": {...} },
    "BAST": { "extracted_data": {...} }
  }
}
```

### 3. Document Review
```http
POST /review
Content-Type: multipart/form-data
```

**Parameters:**
- `ticket` (string, required): Ticket ID dari ekstraksi sebelumnya
- `ground_truth` (string, required): JSON string dengan data referensi

**Response:**
```json
{
  "ticket": "TICKET-001",
  "status": "completed",
  "validation_results": {
    "typo_checker": [...],
    "date_validator": [...],
    "price_validator": [...]
  },
  "advance_review": {...}
}
```

## 🔄 Workflow Sistem

### Alur Ekstraksi Dokumen

```
1. Upload PDF Files
   ↓
2. Validasi File (PDF only)
   ↓
3. Simpan ke Ticket Storage
   ↓
4. Extract Document Type dari Filename
   ↓
5. OCR Processing (Azure Document Intelligence)
   ↓
6. Ground Truth Calculation
   ↓
7. Simpan Hasil ke JSON
   ↓
8. Cleanup PDF Files
   ↓
9. Return Response
```

### Alur Review & Validasi

```
1. Load OCR Results dari Ticket Storage
   ↓
2. Parse Ground Truth dari Request
   ↓
3. Typo Validation (AI-powered)
   ↓
4. Date Validation (Format & Logic)
   ↓
5. Price Validation (Numerical & Format)
   ↓
6. Advanced Review (LangChain + OpenAI)
   ↓
7. Aggregate Results
   ↓
8. Return Validation Report
```

## 🔧 Konfigurasi

### Environment Variables

| Variable | Deskripsi | Required |
|----------|-----------|----------|
| `AZURE_DI_PROJESSAI_ENDPOINT` | Azure Document Intelligence endpoint URL | Yes |
| `AZURE_DI_PROJESSAI_KEY` | Azure Document Intelligence API key | Yes |
| `OPENAI_API_KEY` | OpenAI API key untuk GPT models | Yes |
| `TEMP_STORAGE` | Path untuk temporary file storage | No (default: `./temp`) |

### CORS Configuration

CORS mem-whitelist **origin browser** (halaman Laravel), bukan port API Python:
- `http://127.0.0.1:8000` dan `http://localhost:8000` (Laravel di port 8000)

Atur variabel `ALLOWED_ORIGINS` di `.env` atau edit default di `app/main.py`.

## 🧪 Testing

Jalankan tests dengan pytest:

```bash
pytest
```

Untuk coverage report:
```bash
pytest --cov=app --cov-report=html
```