# Panduan setup — Pria Solo (AI Document Validator)

Panduan ini menjelaskan langkah dari **clone repository** sampai **backend FastAPI** dan **frontend Laravel** berjalan di mesin lokal (Windows dengan Laragon, atau OS lain). Ikuti urutan di bawah.

---

## 1. Prasyarat

| Komponen | Versi / catatan |
|----------|------------------|
| Git | Terpasang di PATH |
| Python | 3.10+ |
| PHP & Composer | Sesuai `frontend/composer.json` (mis. PHP 8.x) |
| MySQL | Untuk database Laravel |
| pip | Untuk dependensi Python |

**Akun / kunci API (magang):** isi di `backend/.env` setelah menyalin dari `.env.example` — Azure Document Intelligence, OpenAI, dll., sesuai kebutuhan fitur ekstraksi dan review AI.

---

## 2. Clone repository

```bash
git clone <url-repo-anda> pria-solo
cd pria-solo
```

---

## 3. Backend (FastAPI)

```bash
cd backend
python -m venv .venv
```

**Windows (cmd):** `.venv\Scripts\activate`  
**Windows (Git Bash) / Linux / macOS:** `source .venv/bin/activate`

```bash
pip install -r requirements.txt
copy .env.example .env
# atau: cp .env.example .env
```

Edit `backend/.env`:

- Isi kunci Azure / OpenAI sesuai proyek.
- `TEMP_STORAGE` — folder kerja sementara (default `./temp` relatif ke cwd saat menjalankan uvicorn).
- `ALLOWED_ORIGINS` — **origin halaman Laravel di browser** (bukan port API Python). Setup standar proyek ini: Laravel `http://127.0.0.1:8000` → isi `http://127.0.0.1:8000,http://localhost:8000`.

Jalankan API (**port 8001** agar tidak bentrok dengan Laravel di 8000):

```bash
cd backend
uvicorn app.main:app --reload --host 0.0.0.0 --port 8001
```

- **Swagger UI:** http://127.0.0.1:8001/docs  
- **Health:** http://127.0.0.1:8001/health  

Pastikan tidak ada error di konsol saat startup.

---

## 4. Frontend (Laravel + Open Admin)

Buka terminal **baru** (backend tetap berjalan).

```bash
cd frontend
composer install
copy .env.example .env
php artisan key:generate
```

Edit `frontend/.env`:

| Variabel | Keterangan |
|----------|------------|
| `DB_*` | Host, nama database, user, password MySQL |
| `URL_VM_PYTHON` | Base URL FastAPI **tanpa** slash akhir, mis. `http://127.0.0.1:8001` — dipakai saat Laravel/queue memanggil `POST /information-extraction` dan alur terkait |
| `APP_URL` | URL publik aplikasi Laravel (mis. `http://127.0.0.1:8000`) |

Migrasi database:

```bash
php artisan migrate
```

Jalankan server:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Buka browser: **http://127.0.0.1:8000** (Laravel; FastAPI harus berjalan terpisah di **8001**).

Login admin Open Admin sesuai konfigurasi proyek Anda. Fitur validasi dokumen membutuhkan **backend Python aktif** dan **`URL_VM_PYTHON` benar**.

---

## 5. Antrean (opsional)

Jika upload memakai **queue** (`QUEUE_CONNECTION` bukan `sync`), jalankan worker:

```bash
cd frontend
php artisan queue:work
```

Tanpa worker, pastikan queue disetel `sync` untuk pengujian lokal sederhana.

---

## 6. Verifikasi cepat

1. `GET http://127.0.0.1:8001/health` → `status: healthy`
2. Buka halaman validasi dokumen di Laravel → coba alur upload (PDF) dengan tiket uji
3. Jika CORS error di browser saat memanggil FastAPI langsung, periksa `ALLOWED_ORIGINS` di `backend/.env`

---

## 7. Dokumen terkait

- Daftar endpoint & alur: [api-dokumen-validasi-ai.md](api-dokumen-validasi-ai.md)
- Ringkasan curl (FastAPI): [api-documentation.md](api-documentation.md)
- Hasil uji API: [api-test-results.md](api-test-results.md)
- Hasil uji UI: [ui-test-results.md](ui-test-results.md)

---

*Dokumen ini mendukung Tugas 4 Modul 4 (setup guide) disesuaikan dengan proyek magang AI Document Validator.*
