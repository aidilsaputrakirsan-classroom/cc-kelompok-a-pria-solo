# UTS Demo Script — PRIA SOLO (Dyno Fadillah Ramadhani, 10231033)

Skrip ini mengikuti struktur tugas Modul 7 untuk demo live: Compose, aplikasi full-stack (Laravel Open Admin + FastAPI), dan persistensi data.

**Port default:** Laravel `http://localhost:8000`, FastAPI `http://localhost:8001`, MySQL host `localhost:3307` (opsional, untuk debug dari host).

---

## 1. Setup (±2 menit)

- Buka terminal di **root** repositori (`pria-solo`).
- Pastikan file env ada (sekali saja setelah clone):
  - `cp backend/.env.docker.example backend/.env.docker` — isi `AZURE_*`, `OPENAI_API_KEY`, `TEMP_STORAGE` jika demo butuh ekstraksi AI.
  - `cp frontend/.env.docker.example frontend/.env.docker` — sesuaikan `APP_KEY` jika tidak memakai contoh.
- Jalankan stack:
  - `make build` **atau** `docker compose up --build -d`
- Migrasi database Laravel (sekali setelah volume DB baru):
  - `make migrate` **atau** `docker compose exec frontend php artisan migrate --force`
- Verifikasi:
  - `docker compose ps` — `db` **healthy**, `backend` **healthy**, `frontend` **healthy** (bisa butuh ±1 menit setelah start).

---

## 2. Frontend — Laravel Open Admin (±5 menit)

- Buka **http://localhost:8000**
- Login admin (tunjukkan autentikasi session).
- Buka fitur validasi dokumen / alur upload sesuai modul magang (tunjukkan UI, loading/error jika ada).
- Tunjukkan bahwa permintaan ke FastAPI dilakukan **dari server Laravel** (`URL_VM_PYTHON=http://backend:8001` di dalam Compose).

---

## 3. Backend — FastAPI (±3 menit)

- Buka **http://localhost:8001/docs** (Swagger UI).
- Tunjukkan endpoint dokumentasi: `/`, `/health`, `/team`, `/information-extraction`, `/review`.
- Panggil **GET `/health`** (dari Swagger atau browser) — status OK.
- Jika kredensial Azure/OpenAI sudah diisi: tunjukkan satu alur singkat ekstraksi/review; jika tidak, jelaskan bahwa env production/demo diisi saat presentasi.

---

## 4. Docker & Compose (±3 menit)

- `docker compose ps` — jelaskan tiga service: **db**, **backend**, **frontend**, jaringan `pria-solo-network`, volume `pria-solo-mysql-data`.
- Jelaskan singkat: **healthcheck** MySQL + **depends_on** `service_healthy` agar backend tidak start sebelum DB siap.
- `docker compose down` (tanpa `-v`) — container hilang, **volume data tetap**.
- `docker compose up -d` — stack jalan lagi.
- Login Laravel lagi — **data admin/migrasi tetap ada** (asal tidak `docker compose down -v`).
- `docker compose logs --tail 30 backend` — tunjukkan log aplikasi.

---

## 5. Code walkthrough singkat (±2 menit)

- `docker-compose.yml` — services, `healthcheck`, `depends_on`, volume, network.
- `backend/Dockerfile` — multi-stage, `wait-for-db.sh`, healthcheck.
- `frontend/Dockerfile` — multi-stage Composer + runtime PHP, `artisan serve`.

---

## Total perkiraan: ±15 menit

Sesuaikan urutan dengan rubrik dosen. Jika ditanya viva: siapkan jawaban untuk **image vs container**, **multi-stage build**, **mengapa named volume**, dan **beda `depends_on` dengan healthcheck**.
