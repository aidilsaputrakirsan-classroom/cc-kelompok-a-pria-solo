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

## 🐳 Docker Compose (Modul 7)

Satu perintah menjalankan **MySQL 8**, **FastAPI** (port **8001**), dan **Laravel** (port **8000**). Data MySQL disimpan di volume bernama `pria-solo-mysql-data` sehingga tetap ada setelah `docker compose down` (tanpa `-v`).

### Prasyarat

- Docker Desktop (atau Docker Engine + Compose plugin)
- Salin file environment (satu kali setelah clone):

```bash
cp backend/.env.docker.example backend/.env.docker
cp frontend/.env.docker.example frontend/.env.docker
```

Isi `backend/.env.docker` dengan kredensial yang diperlukan (mis. `AZURE_*`, `OPENAI_API_KEY`, `TEMP_STORAGE`). File `*.env.docker` **tidak** di-commit (lihat `.gitignore`).

### Perintah cepat

```bash
make build          # atau: docker compose up --build -d
docker compose ps   # db + backend harus healthy; frontend mengikuti
make migrate        # pertama kali / setelah volume DB baru: php artisan migrate --force
```

- **Laravel:** http://localhost:8000  
- **FastAPI docs:** http://localhost:8001/docs  
- **MySQL dari host (opsional):** `localhost:3307` (user `clouduser`, DB `cloudapp`)

### Makefile (ringkas)

| Target | Fungsi |
|--------|--------|
| `make up` | `docker compose up -d` |
| `make build` | Build image + start (`docker compose up --build -d`) |
| `make down` | Stop & hapus container + network (volume **tetap**) |
| `make logs` / `make logs-backend` | Log semua service / backend saja |
| `make ps` | Status service |
| `make clean` | `docker compose down -v` + prune (⚠️ data DB hilang) |
| `make migrate` | Migrasi Laravel di container `frontend` |
| `make shell-backend` / `make shell-db` | Shell backend / MySQL |
| `make compose-images` | Build image `backend` + `frontend` dari Compose |
| `make compose-push-latest DOCKERHUB_USERNAME=... TAG=v1` | Tag + push kedua image ke Docker Hub (`v1` + `latest`) |
| `make image-sizes` | Tampilkan ukuran image backend/frontend |

### Docker Hub (Modul 7 CI/CD)

Module meminta push image ke Docker Hub dengan tag `latest`. Untuk stack ini, image yang dipush adalah:

- `<username>/pria-solo-backend:<tag>` dan `<username>/pria-solo-backend:latest`
- `<username>/pria-solo-frontend:<tag>` dan `<username>/pria-solo-frontend:latest`

Perintah:

```bash
# 1) Login sekali
docker login

# 2) Build dua image dari compose
make compose-images

# 3) Tag + push (versi + latest)
make compose-push-latest DOCKERHUB_USERNAME=yourusername TAG=v1

# 4) Cek ukuran image (untuk dokumentasi tugas)
make image-sizes
```

Image publik (Docker Hub — akun `dynofr`):

- [dynofr/pria-solo-backend](https://hub.docker.com/r/dynofr/pria-solo-backend) — tag `v1` dan `latest`
- [dynofr/pria-solo-frontend](https://hub.docker.com/r/dynofr/pria-solo-frontend) — tag `v1` dan `latest`

Pull contoh:

```bash
docker pull dynofr/pria-solo-backend:v1
docker pull dynofr/pria-solo-frontend:v1
```

Tabel ukuran (build lokal terakhir; layer sama dengan yang di-push):

| Image | Tag | Size |
|------|-----|------|
| `dynofr/pria-solo-backend` | `v1` / `latest` | `1.12GB` |
| `dynofr/pria-solo-frontend` | `v1` / `latest` | `7.56GB` |

Digest manifest (referensi reproducible):

| Image:tag | Digest |
|-----------|--------|
| `dynofr/pria-solo-backend:v1` | `sha256:a9e2f9c1f722dfee0b886c4dea4fba7e755bcf9364e19dfb8b909b23b12b521d` |
| `dynofr/pria-solo-frontend:v1` | `sha256:a2a192a0399f559c162bbf740092725a99e05d61d2e08109fd5983d7a0d4151a` |

### Demo UTS

Langkah demi langkah untuk presentasi: **[docs/uts-demo-script.md](docs/uts-demo-script.md)**

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
| 5-7    | Docker & Compose        | ✅     |
| 8      | UTS Demo                | ⬜     |
| 9-11   | CI/CD Pipeline          | ⬜     |
| 12-14  | Microservices           | ⬜     |
| 15-16  | Final & UAS             | ⬜     |