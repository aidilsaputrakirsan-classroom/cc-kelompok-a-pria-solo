# Tugas 6: Optimasi & Dokumentasi Multi-Container — Jawaban Berdasarkan Codebase (Magang)

**Modul:** `06-modul.md` (BAGIAN C: TUGAS TERSTRUKTUR — Tugas 6)  
**Proyek:** `pria-solo` — AI Document Validator  
**Penyesuaian konteks:** Struktur proyek magang berbeda dari contoh modul (frontend React + PostgreSQL), sehingga implementasi mengikuti codebase nyata: frontend Laravel/OpenAdmin + MySQL.

---

## 0) Ringkasan Deliverables

1. `backend/Dockerfile`  
   - Diubah menjadi **multi-stage build** (builder + runtime)
   - Runtime hanya copy venv + source, non-root user
   - Entrypoint pakai `wait-for-db.sh`
2. `backend/scripts/wait-for-db.sh`  
   - Cek readiness MySQL via `mysqladmin ping` sebelum start `uvicorn`
3. `backend/.env.docker`  
   - Environment khusus Docker network (`db` sebagai hostname)
4. `backend/.env.example`  
   - Ditambahkan variabel opsional untuk setup Docker
5. `frontend/nginx.conf`  
   - Production-ready config (deliverable hardening): gzip, security headers, custom error pages
6. `frontend/Dockerfile` dan `frontend/.dockerignore`
   - Menjalankan frontend Laravel di container terpisah
7. `docs/docker-architecture.md`  
   - Arsitektur 3-container lengkap (network, volume, ports, env)
8. `docs/image-comparison.md`  
   - Template dokumentasi ukuran image sebelum vs sesudah optimasi
9. `scripts/docker-run.sh`  
   - Script helper `start|stop|status|logs` untuk setup manual Modul 6

---

## 1) Lead DevOps — Backend Multi-Stage Build

### Tujuan
- Mengecilkan ukuran image backend dan memisahkan fase build dari runtime.
- Menyesuaikan target Modul 6: image runtime lebih efisien.

### Implementasi
- File: `backend/Dockerfile`
- Perubahan utama:
  - `FROM python:3.12-slim AS builder` -> buat virtual environment + install dependencies
  - `FROM python:3.12-slim` runtime -> copy `/opt/venv` + source code
  - install `postgresql-client` di runtime untuk kebutuhan `pg_isready`
  - tetap gunakan non-root user (`appuser`)

### Dampak
- Layer build dependencies tidak ikut penuh ke runtime stage.
- Startup siap diintegrasikan ke network `cloudnet` + database container.

---

## 2) Lead Backend — Startup Script Cek Database Ready

### Tujuan
Mencegah backend crash saat startup ketika MySQL belum siap menerima koneksi.

### Implementasi
- File: `backend/scripts/wait-for-db.sh`
- Mekanisme:
  - Jika `DATABASE_URL` tidak diset -> skip check (tetap kompatibel untuk mode lokal non-docker)
  - Jika diset -> loop `mysqladmin ping -h db -P 3306`
  - Retry dengan batas (`DB_WAIT_RETRIES`) dan interval (`DB_WAIT_SLEEP`)
- Dockerfile menggunakan:
  - `ENTRYPOINT ["wait-for-db.sh"]`
  - `CMD ["uvicorn", ...]`

---

## 3) Lead Frontend — Production-ready Nginx Config

### Tujuan
Memenuhi requirement Modul 6 untuk hardening frontend web server production.

### Implementasi
- File: `frontend/nginx.conf`
- Fitur yang ditambahkan:
  - `gzip` compression
  - security headers: `X-Frame-Options`, `X-Content-Type-Options`
  - custom error pages: `404` dan `50x`
  - static asset caching + `index.html` no-cache
  
Selain hardening nginx sebagai deliverable, frontend utama dijalankan dari container Laravel (`frontend/Dockerfile`) agar konsisten dengan stack proyek.

---

## 4) Lead QA & Docs — Dokumen Arsitektur Docker

### Tujuan
Menyediakan dokumentasi single-source untuk arsitektur multi-container minggu 6.

### Implementasi
- File: `docs/docker-architecture.md`
- Konten:
  - Diagram Mermaid 3-container (`frontend`, `backend`, `db`)
  - Penjelasan ports, network `cloudnet`, volume `mysqldata`
  - Environment variables penting
  - Startup sequence dan checklist verifikasi

---

## 5) Lead CI/CD — Bandingkan Ukuran Image & Publikasi

### Tujuan
Mendokumentasikan hasil optimasi image dan persiapan push ke Docker Hub.

### Implementasi
- File: `docs/image-comparison.md`
- Isi:
  - langkah build before/after
  - tabel hasil ukuran image
  - command tag/push untuk backend + frontend

---

## 6) Command Verifikasi Utama

```bash
# Build backend optimized image
docker build -t pria-solo-backend:v2 ./backend

# Run DB
docker network create cloudnet
docker run -d --name db --network cloudnet \
  -e MYSQL_ROOT_PASSWORD=root123 \
  -e MYSQL_DATABASE=cloudapp \
  -e MYSQL_USER=clouduser \
  -e MYSQL_PASSWORD=cloudpass \
  -p 3307:3306 \
  -v mysqldata:/var/lib/mysql \
  mysql:8.0

# Run backend with docker env
docker run -d --name backend --network cloudnet \
  --env-file ./backend/.env.docker \
  -p 8001:8001 \
  pria-solo-backend:v2

# Run frontend Laravel
docker build -t pria-solo-frontend:v1 ./frontend
docker run -d --name frontend --network cloudnet \
  --env-file ./frontend/.env.docker \
  -p 3000:8000 \
  pria-solo-frontend:v1
```

---

## 7) Status Verifikasi Runtime (2026-04-01) ✅ COMPLETE

**Backend (FastAPI):**
- ✅ Image `pria-solo-backend:v2` berhasil dibangun (1.12 GB)
- ✅ Container berhasil start dengan `wait-for-db.sh` yang sudah diperbaiki
- ✅ MySQL readiness check berhasil: `[wait-for-db] MySQL is ready.`
- ✅ Uvicorn berjalan pada `http://0.0.0.0:8001`
- ✅ Health endpoint responsif: `{"status":"healthy","timestamp":"2026-04-01T03:21:15.109259"}`
- ✅ Container status: `Up 5 minutes (healthy)`

**Frontend (Laravel):**
- ✅ Image `pria-solo-frontend:v1` berhasil dibangun dan dioptimasi (7.59 GB, turun dari 15.4 GB)
- ✅ File `frontend/.env.docker` dibuat dengan konfigurasi Docker network (`DB_HOST=db`, `URL_VM_PYTHON=http://backend:8001`)
- ✅ Container berjalan: `Up 3 minutes`
- ✅ Laravel dev server aktif: `http://0.0.0.0:8000`
- ✅ HTTP endpoint responsif: Status 200
- ✅ Optimasi: Enhanced `.dockerignore`, cleanup cache, remove unnecessary files (50.7% size reduction)

**Database (MySQL):**
- ✅ Container `db` berjalan dengan volume `mysqldata`
- ✅ Port mapping `3307:3306` untuk akses host
- ✅ Dapat diakses dari container lain di network `cloudnet`
- ✅ MySQL ready: `/usr/sbin/mysqld: ready for connections. Version: '8.0.45'`

**Network Verification:**
```
db (172.18.0.2/16)
backend (172.18.0.3/16)
frontend (172.18.0.4/16)
```

**Perbaikan Kritis pada `wait-for-db.sh`:**
- Menambahkan flag `--skip-ssl` untuk menghindari error `TLS/SSL error: self-signed certificate` dari MySQL 8.0
- Menggunakan `timeout 5` untuk mencegah `mysqladmin ping` hang
- Fungsi `trim_cr()` menghapus CRLF dari env vars (Windows compatibility)
- Menambahkan `CHUNK_SIZE=10` di `backend/.env.docker` untuk fix `TypeError: int() argument must be a string`

---

## 8) Catatan Adaptasi untuk Proyek Magang

- Modul 6 mencontohkan frontend React + PostgreSQL, sedangkan proyek `pria-solo` memakai frontend Laravel/OpenAdmin + MySQL.
- Adaptasi ini mempertahankan tujuan pembelajaran inti: multi-stage build, network-based hostname (`db`), startup reliability, volume persistence, dan dokumentasi arsitektur.
