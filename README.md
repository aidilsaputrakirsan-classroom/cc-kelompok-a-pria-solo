# ☁️ Cloud App - Pria Solo

Aplikasi full-stack untuk mata kuliah Komputasi Awan (SI ITK). Backend FastAPI melayani REST API (Document Validator), frontend Laravel dengan Open Admin sebagai antarmuka yang mengonsumsi API. Proyek ini dikerjakan secara mandiri dalam konteks magang di , Telkom Regional IV Kalimantan Timur dengan ketentuan modul tetap mengacu pada struktur tugas berkelompok; seluruh peran tim diampu oleh satu anggota.

## 👥 Tim

| Nama   | NIM      | Peran            |
|--------|----------|------------------|
| Dyno Fadillah Ramadhani | 10231033 | Lead Backend     |
| Dyno Fadillah Ramadhani | 10231033 | Lead Frontend    |
| Dyno Fadillah Ramadhani | 10231033 | Lead DevOps      |
| Dyno Fadillah Ramadhani | 10231033 | Lead QA & Docs   |

## 🛠️ Tech Stack

Berdasarkan struktur proyek di `backend/` dan `frontend/`:

### Backend (`backend/`)
| Teknologi | Fungsi |
|-----------|--------|
| FastAPI | REST API & web framework |
| Uvicorn | ASGI server |
| Azure AI Document Intelligence | OCR & ekstraksi dokumen |
| LangChain & OpenAI | AI review & orkestrasi LLM |
| PyMuPDF, pdf2image, Pillow | Pemrosesan PDF & gambar |
| Pydantic | Validasi data & schema |
| SQLAlchemy | ORM & akses database |
| python-dotenv | Konfigurasi environment |
| Pytest | Testing |

### Frontend (`frontend/`)
| Teknologi | Fungsi |
|-----------|--------|
| Laravel (PHP 8) | Web application framework |
| Laravel Open Admin | Admin panel & backend UI |
| Blade | Template engine |
| Eloquent ORM | Database (MySQL) |
| Guzzle HTTP | HTTP client ke backend API |
| Open Admin extensions | CKEditor, Log Viewer, Media Manager, Config, Reporter, Scheduling, dll. |

### Infrastruktur & DevOps
| Teknologi | Fungsi |
|-----------|--------|
| Docker | Containerization |
| GitHub Actions | CI/CD |
| Railway/Render | Cloud deployment |

## 🏗️ Architecture

```
[Laravel + Open Admin Frontend] <--HTTP REST--> [FastAPI Backend] <--SQL--> [Database]
       (Blade, Eloquent, MySQL)                      (Document Validator API)    (MySQL)
```


## 🚀 Getting Started

### Prasyarat
- **Backend:** Python 3.10+, pip
- **Frontend:** PHP 7.3+ / 8.0+ (sesuai composer.json), Composer, MySQL
- Git

### Backend
```bash
cd backend
pip install -r requirements.txt
# Opsional: salin .env dari .env.example dan isi AZURE_DI_*, OPENAI_API_KEY, TEMP_STORAGE
uvicorn app.main:app --reload --port 8000
```
API: http://localhost:8000 — Docs: http://localhost:8000/docs

### Frontend
```bash
cd frontend
composer install
cp .env.example .env
php artisan key:generate
# Atur DB_CONNECTION=mysql, DB_DATABASE, DB_USERNAME, DB_PASSWORD di .env
php artisan migrate
php artisan serve --port 8080
```
Aplikasi: http://localhost:8080 (pastikan backend berjalan di port 8000)

## 📡 API (Backend FastAPI)

**Base URL:** `http://localhost:8000`  
**Dokumentasi interaktif:** http://localhost:8000/docs

| Method | Endpoint | Deskripsi |
|--------|----------|------------|
| GET | `/` | Info API |
| GET | `/health` | Health check |
| GET | `/team` | Info tim (modul) |
| POST | `/information-extraction` | Upload PDF, ekstraksi OCR + ground truth |
| POST | `/review` | Validasi & advance review (butuh hasil ekstraksi dulu) |

Detail parameter, request/response body, dan kode error: **[docs/api-dokumen-validasi-ai.md](docs/api-dokumen-validasi-ai.md)**. Hasil testing API: **[docs/api-test-results.md](docs/api-test-results.md)**.

## 📅 Roadmap

| Minggu | Target                  | Status |
|--------|-------------------------|--------|
| 1      | Setup & Hello World     | ✅     |
| 2      | REST API + Database     |   ✅   |
| 3      | React Frontend          | ⬜     |
| 4      | Full-Stack Integration  | ⬜     |
| 5-7    | Docker & Compose        | ⬜     |
| 8      | UTS Demo                | ⬜     |
| 9-11   | CI/CD Pipeline          | ⬜     |
| 12-14  | Microservices           | ⬜     |
| 15-16  | Final & UAS             | ⬜     |