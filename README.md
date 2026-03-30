# ☁️ AI Document Validator App - PRIA SOLO

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

Ringkas di bawah ini; **langkah lengkap** (clone → `.env` → DB → port): **[docs/setup-guide.md](docs/setup-guide.md)**.

### Prasyarat
- **Backend:** Python 3.10+, pip
- **Frontend:** PHP 7.3+ / 8.0+ (sesuai composer.json), Composer, MySQL
- Git

### Backend
```bash
cd backend
pip install -r requirements.txt
# Salin .env dari .env.example — isi AZURE_*, OPENAI_API_KEY, TEMP_STORAGE, ALLOWED_ORIGINS
uvicorn app.main:app --reload --port 8001
```
API: http://127.0.0.1:8001 — Docs: http://127.0.0.1:8001/docs

### Frontend
```bash
cd frontend
composer install
cp .env.example .env
php artisan key:generate
# Atur DB_* di .env dan URL_VM_PYTHON=http://127.0.0.1:8001 (base URL FastAPI, tanpa slash akhir)
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```
Aplikasi: http://127.0.0.1:8000 (pastikan backend FastAPI berjalan di port 8001)

## 🔐 Authentication & security model

- **Laravel (Open Admin):** pengguna admin masuk lewat mekanisme autentikasi Laravel (session). Akses halaman validasi dokumen dan rute admin dilindungi oleh guard/middleware aplikasi.
- **FastAPI (Document Validator):** endpoint `POST /information-extraction` dan `POST /review` **tidak** memakai JWT pada proyek magang ini; yang memanggil ke FastAPI adalah **server Laravel** (mis. job `ProcessAdvanceUploadJob` dengan Guzzle) menggunakan base URL dari **`URL_VM_PYTHON`** di `.env`. Untuk production, pembatasan jaringan (hanya jaringan internal / reverse proxy) disarankan.
- **CORS:** daftar origin di **`ALLOWED_ORIGINS`** pada `backend/.env` (whitelist), bukan `*`.

## 📡 API (Backend FastAPI)

**Base URL:** `http://127.0.0.1:8001`  
**Dokumentasi interaktif:** http://127.0.0.1:8001/docs

| Method | Endpoint | Deskripsi |
|--------|----------|------------|
| GET | `/` | Info API |
| GET | `/health` | Health check |
| GET | `/team` | Info tim (modul) |
| POST | `/information-extraction` | Upload PDF, ekstraksi OCR + ground truth |
| POST | `/review` | Validasi & advance review (butuh hasil ekstraksi dulu) |

- Detail alur + rute Laravel: **[docs/api-dokumen-validasi-ai.md](docs/api-dokumen-validasi-ai.md)**
- Ringkasan + **contoh cURL**: **[docs/api-documentation.md](docs/api-documentation.md)**
- Hasil testing API: **[docs/api-test-results.md](docs/api-test-results.md)**
- Jawaban tugas terstruktur Modul 4 (magang): **[docs/tugas-per-minggu/04-tugas-terstruktur.md](docs/tugas-per-minggu/04-tugas-terstruktur.md)**

## 📅 Roadmap

| Minggu | Target                  | Status |
|--------|-------------------------|--------|
| 1      | Setup & Hello World     | ✅     |
| 2      | REST API + Database     |   ✅   |
| 3      | UI Laravel + Open Admin | ✅     |
| 4      | Full-Stack Integration  | ✅     |
| 5-7    | Docker & Compose        | ⬜     |
| 8      | UTS Demo                | ⬜     |
| 9-11   | CI/CD Pipeline          | ⬜     |
| 12-14  | Microservices           | ⬜     |
| 15-16  | Final & UAS             | ⬜     |