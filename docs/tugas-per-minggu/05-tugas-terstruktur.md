# Tugas 5: Optimasi Image & Dokumentasi Docker — Jawaban Berdasarkan Codebase (Magang)

**Modul:** 05-modul.md (BAGIAN C: TUGAS TERSTRUKTUR — *Tugas 5*)  
**Proyek:** pria-solo — AI Document Validator (magang)  
**Penyesuaian:** Tugas modul dijalankan berdasarkan struktur proyek nyata:
- `backend/` = FastAPI
- `frontend/` = Laravel + Open Admin

---

## 0. Ringkasan Deliverables yang Dibuat

1. `backend/Dockerfile`  
   - Base image `python:3.12-slim`
   - Layer build teroptimasi
   - Non-root user (`appuser`)
   - `HEALTHCHECK` ke endpoint `/health`
2. `backend/.dockerignore`  
   - Exclude secret, cache, VCS, docs/test yang tidak perlu di image
3. `docs/docker-cheatsheet.md`  
   - Command Docker yang dipakai di proyek `pria-solo`
4. `docs/image-comparison.md`  
   - Template perbandingan ukuran image Python
5. `Makefile` (root repo)  
   - Shortcut `build`, `run`, `health`, `push`, `clean`

---

## 1. Lead DevOps — Non-root User di Dockerfile

### Tujuan
Menerapkan best practice keamanan dengan menjalankan aplikasi sebagai non-root.

### Implementasi
- File: `backend/Dockerfile`
- Menambahkan:
  - `RUN useradd -m appuser`
  - `RUN chown -R appuser:appuser /app`
  - `USER appuser`

### Catatan Teknis
- `chown` penting agar user non-root tetap dapat membaca/menulis path aplikasi yang dibutuhkan runtime.

---

## 2. Lead Backend — Healthcheck di Dockerfile

### Tujuan
Container dapat dipantau status kesehatannya oleh Docker engine.

### Implementasi
- File: `backend/Dockerfile`
- Menambahkan:
  - install `curl` (khusus image slim)
  - `HEALTHCHECK` ke `http://127.0.0.1:8001/health`

### Verifikasi
```bash
docker build -t pria-solo-backend:v1 ./backend
docker run -d --name pria-solo-backend -p 8001:8001 --env-file ./backend/.env pria-solo-backend:v1
docker inspect --format='{{.State.Health.Status}}' pria-solo-backend
```

Endpoint `/health` sudah tersedia pada backend FastAPI (`app/main.py`), sehingga healthcheck dapat dipakai langsung.

---

## 3. Lead Frontend — `docs/docker-cheatsheet.md`

### Tujuan
Mendokumentasikan command Docker yang paling sering dipakai tim.

### Implementasi
- File: `docs/docker-cheatsheet.md`
- Isi mencakup:
  - build/run/log/exec/stop/rm
  - inspect/health/history
  - tag/push/pull Docker Hub
  - shortcut lewat `Makefile`
  - catatan integrasi Laravel frontend (port 8000) dengan FastAPI Docker (port 8001)

---

## 4. Lead QA & Docs — Perbandingan Ukuran Image

### Tujuan
Membandingkan `python:3.12`, `python:3.12-slim`, dan `python:3.12-alpine`.

### Implementasi
- File: `docs/image-comparison.md`
- Menyediakan:
  - langkah uji `docker pull` dan `docker images`
  - tabel hasil untuk diisi saat praktikum
  - analisis pemilihan `python:3.12-slim` untuk proyek ini

### Alasan Pemilihan `python:3.12-slim`
- lebih ringan dari full image;
- kompatibilitas dependency backend cukup baik untuk stack FastAPI + library data/AI;
- sesuai best practice Modul 5.

---

## 5. Lead CI/CD — Makefile Script Docker

### Tujuan
Mempermudah workflow Docker tim melalui command standar.

### Implementasi
- File: `Makefile` (root)
- Target utama:
  - `make build`
  - `make run`
  - `make logs`
  - `make health`
  - `make push DOCKERHUB_USERNAME=<username>`
  - `make clean`

---

## 6. Command Eksekusi Cepat (Checklist Praktikum)

```bash
# 1) Build image
make build

# 2) Run container
make run

# 3) Lihat health status
make health

# 4) Lihat log runtime
make logs
```

Jika semua berhasil, backend harus dapat diakses pada:
- `http://127.0.0.1:8001/docs`
- `http://127.0.0.1:8001/health`

---

## 7. Pemetaan Tugas Modul ke Proyek Magang

| Diminta Modul 5 (Bagian C) | Implementasi di `pria-solo` |
|-----------------------------|-------------------------------|
| Non-root user | `backend/Dockerfile` (`appuser`) |
| Healthcheck | `backend/Dockerfile` + endpoint `/health` FastAPI |
| Docker cheatsheet | `docs/docker-cheatsheet.md` |
| Bandingkan ukuran image | `docs/image-comparison.md` |
| Script build/run/push/clean | `Makefile` di root repo |

---

## 8. Penutup

Tugas 5 telah diadaptasi ke kebutuhan proyek intern `frontend/` dan `backend` tanpa memaksakan contoh generik modul. Fokus utamanya tetap sama dengan capaian pembelajaran Modul 5: Dockerfile yang baik, image yang efisien, observability dasar via healthcheck, dan dokumentasi operasional yang dapat dipakai tim.
