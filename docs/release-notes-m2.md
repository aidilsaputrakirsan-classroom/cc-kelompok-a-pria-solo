# Release notes — Milestone 2 (Modul 11)

**Versi:** `v2.0`  
**Tanggal:** 2026-05-25  
**Fase:** CI/CD & deployment (Minggu 9–11) — hardening & CD ke cloud.

## Ringkasan

Milestone ini menambahkan konfigurasi backend berbasis environment, deploy full-stack ke **Railway** (MySQL + Laravel frontend + FastAPI backend), pipeline CD di GitHub Actions, health check pasca-deploy, dokumentasi deployment & smoke test production, serta ringkasan deploy di Actions.

## Fitur & perubahan utama

- **Backend:** `backend/config.py` — `ENVIRONMENT`, `DEBUG`, `CORS_ORIGINS` (fallback `ALLOWED_ORIGINS`), `LOG_LEVEL`, `DATABASE_URL`, `SECRET_KEY`, `TOKEN_EXPIRE_MINUTES`, `APP_VERSION`; integrasi di `app/main.py`. Default `CHUNK_SIZE=10` saat env tidak diset (hindari crash boot di Railway).
- **Frontend:** `frontend/.env.production` — referensi `APP_URL` dan `URL_VM_PYTHON` untuk production (nilai publik, tanpa secret).
- **CI/CD:** Job **Deploy to Railway** pada push ke `main` (setelah build Docker), deployment summary (SHA, ref, timestamp), health check ke `GET /health` bila secret `BACKEND_PRODUCTION_URL` di-set.
- **Dokumentasi:** `docs/deployment-guide.md`, `docs/production-test.md`, README live demo, roadmap minggu 9–11 ✅.

## URL production

| Service | URL |
|---------|-----|
| Frontend (Laravel) | https://cc-kelompok-a-pria-solo-production.up.railway.app |
| Open Admin (login) | https://cc-kelompok-a-pria-solo-production.up.railway.app/projess/auth/login |
| Backend (FastAPI) | https://backend-production-bdd8.up.railway.app |
| Health | https://backend-production-bdd8.up.railway.app/health |
| API docs (Swagger) | https://backend-production-bdd8.up.railway.app/docs |

## Tech stack (tetap)

- **Backend:** Python 3.12, FastAPI, Uvicorn, Azure DI/OCR, LangChain/OpenAI, PyMuPDF, dsb.
- **Frontend:** Laravel 8, Laravel Open Admin, MySQL (Railway managed).
- **CI:** GitHub Actions — lint (ruff), pytest backend, `php artisan test`, build Docker.
- **CD:** Railway CLI dari Actions (`RAILWAY_TOKEN`, `RAILWAY_PROJECT_ID`).

## Known issues

- `/health` mengembalikan `database: not_applicable` — backend FastAPI tidak memakai DB Railway pada endpoint health; data aplikasi utama di MySQL Laravel.
- Health check di CI dilewati jika secret `BACKEND_PRODUCTION_URL` belum di-set di GitHub.
- Fitur OCR/AI membutuhkan `OPENAI_*` dan `AZURE_*` di variabel backend Railway.

## GitHub Actions secrets (disarankan)

| Secret | Nilai contoh |
|--------|----------------|
| `RAILWAY_TOKEN` | Token dari railway.app/account/tokens |
| `RAILWAY_PROJECT_ID` | ID proyek Railway |
| `BACKEND_PRODUCTION_URL` | `https://backend-production-bdd8.up.railway.app` |

## Referensi

- [deployment-guide.md](deployment-guide.md)
- [production-test.md](production-test.md)
- Modul: `docs/2026-modul-praktikum-cloudcomputing/11-modul.md`
