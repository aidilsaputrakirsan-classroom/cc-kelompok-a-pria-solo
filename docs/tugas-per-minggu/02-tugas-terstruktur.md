# Tugas 2: Tambah Endpoint & Lengkapi Testing — Jawaban Berdasarkan Codebase (Magang)

**Modul:** 02-modul.md (BAGIAN C: TUGAS TERSTRUKTUR, baris 919–964)  
**Proyek:** pria-solo — AI Document Validator (magang)  
**Penyesuaian:** Tugas tersebut disesuaikan dengan magang; seluruh jawaban mengacu pada **frontend dan backend di codebase ini**.

---

## Konteks Proyek

- **Backend:** FastAPI (`backend/app/`) — **Document Validator API**: ekstraksi dokumen (OCR via Azure), validasi/review (typo, tanggal, harga, advance review AI). Tidak ada resource `items` atau PostgreSQL; data sementara di file (mis. `extraction_results.json`), data persist di frontend (MySQL).
- **Frontend:** Laravel + Open Admin (`frontend/`) — memanggil backend untuk upload PDF, ekstraksi, dan submit review; data tiket, ground truth, dan hasil review disimpan di MySQL.
- **Dokumentasi API lengkap:** `docs/api-dokumen-validasi-ai.md`.

---

## 1. Lead Backend — Endpoint (disetarakan dengan kerja magang)

### Tugas modul (referensi)
Tambah endpoint `GET /items/stats` (total items, total value, item termahal/termurah).

### Penyesuaian ke codebase
Proyek ini **tidak memakai resource `items`**. Backend menyediakan endpoint berikut (dari `backend/app/main.py` dan `backend/app/api/routes.py`):

| Method | Endpoint | Deskripsi |
|--------|----------|------------|
| `GET` | `/` | Info dasar API |
| `GET` | `/health` | Health check |
| `GET` | `/team` | Informasi tim (modul) |
| `POST` | `/information-extraction` | Upload PDF, ekstraksi OCR + ground truth |
| `POST` | `/review` | Validasi & advance review terhadap hasil ekstraksi |

**Jawaban:** Tidak ada `GET /items/stats` karena domain magang adalah validasi dokumen. Yang “ditambah” dan didokumentasikan sesuai modul adalah **endpoint backend yang ada** di atas. Jika diminta tambah endpoint mirip “statistik”, bisa ditambah misalnya `GET /stats` yang mengembalikan ringkasan dari data yang memang tersedia (mis. jumlah ekstraksi/review per periode dari log atau dari frontend API), tetapi itu opsional dan tidak wajib untuk pemenuhan Tugas 2 yang disesuaikan magang.

---

## 2. Lead Frontend — Dokumentasi Hasil Testing API

### Tugas modul
Buat file `docs/api-test-results.md` berisi screenshot/dokumentasi hasil testing semua endpoint (Swagger/Thunder Client).

### Jawaban berdasarkan codebase
Berikut **daftar endpoint backend** yang harus ditest dan didokumentasikan di `docs/api-test-results.md`. Isi dokumen tersebut dapat mengikuti struktur di bawah (tinggal tambah screenshot/copy-paste response nyata).

**Base URL backend:** `http://localhost:8000`  
**Swagger UI:** `http://localhost:8000/docs`

#### Backend endpoints yang ada di codebase

| No | Method | URL | Request | Response (sukses) | Status |
|----|--------|-----|---------|-------------------|--------|
| 1 | GET | `/` | — | `{"message":"Document Validator API","status":"running"}` | 200 |
| 2 | GET | `/health` | — | `{"status":"healthy","timestamp":"..."}` | 200 |
| 3 | GET | `/team` | — | `{"team":"pria-solo","members":[...]}` | 200 |
| 4 | POST | `/information-extraction` | `multipart/form-data`: `ticket` (string), `files` (file[] PDF) | `{"status","ticket","total_files","ocr_extraction_success","ground_truth_results"}` | 200 |
| 5 | POST | `/review` | `multipart/form-data`: `ticket` (string), `ground_truth` (JSON string) | `{"ticket","status", ...results dari orchestrator}` | 200 |

**Detail request/response** (untuk kolom “Request” dan “Response” di tabel, serta contoh body): lihat **`docs/api-dokumen-validasi-ai.md`**.

**Langkah testing yang disarankan:**
1. GET `/`, GET `/health`, GET `/team` — pastikan 200 dan body sesuai.
2. POST `/information-extraction`: kirim `ticket` (mis. `TEST-001`) dan satu file PDF; catat response (status, `total_files`, `ground_truth_results`).
3. POST `/review`: kirim `ticket` sama dan `ground_truth` (JSON object string); catat response (status, validation/advance results).

Di `docs/api-test-results.md` tambahkan: tanggal test, tool (Swagger/Thunder Client), dan screenshot atau copy-paste response untuk setiap endpoint di atas.

---

## 3. Lead DevOps — `.env.example`, `.gitignore`, script setup

### Tugas modul
- `backend/.env.example` lengkap  
- Pastikan `.env` di `.gitignore`  
- Script `setup.sh` untuk install dependencies  

### Jawaban berdasarkan codebase

#### 3.1 `backend/.env.example` (yang ada di codebase)

Isi saat ini (`backend/.env.example`):

```
AZURE_OCR_PROJESSAI_KEY=""
AZURE_OCR_PROJESSAI_ENDPOINT=""
AZURE_DI_PROJESSAI_ENDPOINT=""
AZURE_DI_PROJESSAI_KEY=""
TEMP_STORAGE=""
OPENAI_API_KEY=""
```

- **Lengkap** dalam artian: semua variabel yang dipakai backend (Azure OCR/DI, storage, OpenAI) sudah tercantum. Agar lebih “lengkap” secara dokumentasi, bisa ditambah komentar singkat per baris (nama variabel, wajib/opsional, contoh nilai). Tidak ada `DATABASE_URL` karena backend tidak memakai PostgreSQL.

#### 3.2 `.gitignore`

Di **root** `.gitignore` tercantum:
- `backend/.env`
- `frontend/.env`

Jadi **`.env` sudah diabaikan** untuk backend dan frontend.

#### 3.3 Script `setup.sh`

Belum ada di codebase. Rekomendasi isi (sesuai Getting Started di README):

```bash
#!/bin/bash
# Setup pria-solo (backend + frontend) — sesuaikan path bila perlu

set -e
echo "=== Backend ==="
cd backend
pip install -r requirements.txt
[ -f .env ] || cp .env.example .env
echo "Backend: isi backend/.env (AZURE_*, OPENAI_API_KEY, TEMP_STORAGE), lalu: uvicorn app.main:app --reload --port 8000"
cd ..

echo "=== Frontend ==="
cd frontend
composer install
[ -f .env ] || cp .env.example .env
echo "Frontend: php artisan key:generate, atur DB_* di .env, php artisan migrate, php artisan serve --port 8080"
cd ..
```

Simpan di **root** repo (atau di `backend/` jika hanya backend). Sesuaikan dengan struktur folder dan perintah yang dipakai di lingkungan magang.

---

## 4. Lead QA & Docs — Update README section API

### Tugas modul
Update `README.md`: dokumentasikan semua endpoint (method, URL, request body, response example).

### Jawaban berdasarkan codebase

**README.md** saat ini berisi: Tim, Tech Stack, Architecture, Getting Started (backend & frontend), Roadmap. Belum ada section **API** yang memuat tabel endpoint.

**Dokumentasi detail** (request body, response example, error code) sudah ada di **`docs/api-dokumen-validasi-ai.md`** untuk backend dan frontend.

**Rekomendasi isi section API di README** (berdasarkan endpoint yang benar-benar ada di codebase):

```markdown
## 📡 API (Backend FastAPI)

Base URL: `http://localhost:8000`  
Dokumentasi interaktif: http://localhost:8000/docs

| Method | Endpoint | Deskripsi |
|--------|----------|------------|
| GET | `/` | Info API |
| GET | `/health` | Health check |
| GET | `/team` | Info tim (modul) |
| POST | `/information-extraction` | Upload PDF, ekstraksi OCR + ground truth |
| POST | `/review` | Validasi & advance review (butuh hasil ekstraksi dulu) |

Detail parameter, request/response body, dan kode error: **[docs/api-dokumen-validasi-ai.md](docs/api-dokumen-validasi-ai.md)**.
```

Ini memenuhi “dokumentasikan semua endpoint” dengan mengacu ke file yang sudah ada di codebase.

---

## 5. Lead CI/CD — Schema database (dari codebase)

### Tugas modul
Buat file `docs/database-schema.md`: gambarkan schema database (tabel, kolom, tipe) dalam format Markdown.

### Jawaban berdasarkan codebase

**Schema lengkap (tabel, kolom, tipe, relasi, dan diagram) untuk AI Document Validator ada di [docs/database-schema.md](../database-schema.md).**

Backend **tidak memakai database**; tidak ada tabel `items` atau PostgreSQL. Data persist ada di **frontend (Laravel, MySQL)**. Ringkasan tabel di bawah diambil dari **`frontend/database/migrations/`**; detail lengkap dan diagram relasi lihat **docs/database-schema.md**.

#### Tabel yang relevan untuk fitur Document Validator

| Tabel | Kolom (utama) | Tipe / Keterangan |
|-------|----------------|-------------------|
| **companies** | id, name, address | PK; nama & alamat perusahaan |
| **tickets** | id, ticket_number, company_id, project_title, type | PK; FK company_id; type: Perpanjangan / Non-Perpanjangan |
| **ground_truths** | id, ticket_id, doc_type, extracted_data | PK; FK ticket_id; unique(ticket_id, doc_type); JSON extracted_data |
| **advance_review_results** | id, ground_truth_id, doc_type, status, error_message, review_data | PK; FK ground_truth_id; JSON review_data |
| **typo_errors** | id, ticket_id, doc_type, typo_word, correction_word, created_at | PK; FK ticket_id |
| **ticket_notes** | id, ticket_id, notes | PK; FK ticket_id; unique ticket_id; JSON notes |

Tabel lain di migrations (users, admin, cache, sessions, date_validations, price_validations, projess_logs, dll.) tercantum di **[docs/database-schema.md](../database-schema.md)** (beserta diagram relasi dan definisi kolom lengkap).

**Ringkasan isi** (contoh format di `docs/database-schema.md`):

```markdown
# Schema Database (Frontend MySQL)

Backend tidak memakai database. Semua data persist di **frontend (Laravel, MySQL)**. Migrations: `frontend/database/migrations/`.

## companies
| Kolom    | Tipe         | Keterangan |
|----------|--------------|------------|
| id       | bigint, PK   | —          |
| name     | string       | —          |
| address  | string, null | —          |
| timestamps | —          | —          |

## tickets
| Kolom         | Tipe         | Keterangan        |
|---------------|--------------|-------------------|
| id            | bigint, PK   | —                 |
| ticket_number | string(50)   | unique            |
| company_id    | FK companies | onDelete cascade |
| project_title | string, null | —                 |
| type          | enum         | Perpanjangan, Non-Perpanjangan |
| timestamps    | —            | —                 |

## ground_truths
| Kolom          | Tipe       | Keterangan           |
|----------------|------------|----------------------|
| id             | bigint, PK | —                    |
| ticket_id      | FK tickets | onDelete cascade     |
| doc_type       | string(50) | —                    |
| extracted_data | json       | —                    |
| timestamps     | —          | unique(ticket_id, doc_type) |

## advance_review_results
| Kolom          | Tipe            | Keterangan       |
|----------------|-----------------|------------------|
| id             | bigint, PK      | —                |
| ground_truth_id| FK ground_truths| onDelete cascade |
| doc_type       | string(50)      | —                |
| status         | string(20)      | —                |
| error_message  | text, null      | —                |
| review_data    | json, null      | —                |
| timestamps     | —               | —                |

## typo_errors
| Kolom          | Tipe       | Keterangan   |
|----------------|------------|--------------|
| id             | bigint, PK | —            |
| ticket_id      | FK tickets | onDelete cascade |
| doc_type       | string     | —            |
| typo_word      | string     | —            |
| correction_word| string    | —            |
| created_at     | timestamp  | —            |

## ticket_notes
| Kolom      | Tipe       | Keterangan   |
|------------|------------|--------------|
| id         | bigint, PK | —            |
| ticket_id  | FK tickets | unique       |
| notes      | json       | —            |
| timestamps | —          | —            |

---

### Relasi Antar Tabel

Relasi digambarkan dari **parent** (satu) ke **child** (banyak), dengan **onDelete cascade** artinya jika parent dihapus, baris child ikut terhapus.

```
companies (1) ─────────────< tickets (N)
    │                              │
    │                              ├──< ground_truths (N) ────< advance_review_results (N)
    │                              ├──< typo_errors (N)
    │                              ├──< ticket_notes (1)   [unique: satu tiket maks. satu catatan]
    │                              ├──< date_validations (N)
    │                              └──< price_validations (N)
```

| Relasi | Tabel child | Kolom FK | Tabel parent | Jenis | Keterangan |
|--------|-------------|----------|--------------|--------|-------------|
| Company → Tickets | tickets | company_id | companies | One-to-Many | Satu perusahaan punya banyak tiket. Hapus company → semua tiketnya ikut terhapus. |
| Ticket → Ground truths | ground_truths | ticket_id | tickets | One-to-Many | Satu tiket punya banyak ground truth (satu per doc_type). unique(ticket_id, doc_type). |
| Ground truth → Advance review results | advance_review_results | ground_truth_id | ground_truths | One-to-Many | Satu ground truth punya banyak hasil advance review. |
| Ticket → Typo errors | typo_errors | ticket_id | tickets | One-to-Many | Daftar typo per tiket (per doc_type). |
| Ticket → Ticket notes | ticket_notes | ticket_id | tickets | One-to-One | Satu tiket maksimal satu catatan (unique ticket_id). |
| Ticket → Date validations | date_validations | ticket_id | tickets | One-to-Many | Hasil validasi tanggal per tiket. |
| Ticket → Price validations | price_validations | ticket_id | tickets | One-to-Many | Hasil validasi harga per tiket. |

**Relasi lain di codebase:**

- **comments:** relasi **self-referential** — `child_id` → `comments.id`. Satu komentar bisa punya satu parent komentar (thread).
- **auto_draft_obls:** `auto_draft_id` → `auto_drafts.id`. Satu auto_draft punya banyak auto_draft_obl (One-to-Many).

**Alur data untuk fitur Document Validator:**  
`companies` → **tickets** (satu tiket per batch upload) → **ground_truths** (data referensi per doc_type) → **advance_review_results** (hasil review AI per ground truth). Data validasi (typo, tanggal, harga) dan catatan tiket mengacu langsung ke **tickets**.

Schema lengkap (termasuk date_validations, price_validations, relasi, dan diagram) ada di **[docs/database-schema.md](../database-schema.md)**.

---

## Ringkasan Pemenuhan Tugas 2 (disesuaikan magang)

| Peran | Tugas modul | Penyesuaian codebase | Status |
|-------|-------------|----------------------|--------|
| Lead Backend | GET /items/stats | Endpoint backend yang ada: GET /, /health, /team; POST /information-extraction, /review (dokumentasi di sini & di api-dokumen-validasi-ai.md) | ✅ Disetarakan |
| Lead Frontend | docs/api-test-results.md | File [docs/api-test-results.md](../api-test-results.md) sudah dibuat: daftar endpoint, request/response contoh, tempat screenshot. Tinggal isi tanggal test & screenshot saat testing. | ✅ Selesai |
| Lead DevOps | .env.example lengkap, .env di .gitignore, setup.sh | .env.example & .gitignore sudah sesuai. setup.sh belum ada; rekomendasi isi ada di section 3.3 di atas. | ⚠️ Sebagian (setup.sh opsional) |
| Lead QA & Docs | README section API | Section **📡 API (Backend FastAPI)** sudah ditambahkan di README: tabel endpoint + link ke [docs/api-dokumen-validasi-ai.md](../api-dokumen-validasi-ai.md) & [docs/api-test-results.md](../api-test-results.md). | ✅ Selesai |
| Lead CI/CD | docs/database-schema.md | File [docs/database-schema.md](../database-schema.md) berisi schema lengkap AI Document Validator (tabel, kolom, relasi, diagram). | ✅ Selesai |

---

## Informasi pengumpulan (mengacu modul)

| Item | Keterangan |
|------|------------|
| **Deadline** | Sebelum pertemuan 3 |
| **Format** | Push ke repository tim (GitHub Classroom) |
| **Yang dikumpulkan** | Dokumentasi API (README + api-dokumen-validasi-ai.md), api-test-results.md, .env.example, setup.sh (opsional), database-schema.md; setiap anggota punya ≥1 commit |
| **Penilaian** | Fungsionalitas endpoint, kelengkapan dokumentasi, partisipasi commit |

---

*Dokumen ini menjawab Tugas 2 Modul 2 sepenuhnya berdasarkan **frontend dan backend di codebase pria-solo**, dengan penyesuaian ke konteks magang (AI Document Validator).*
