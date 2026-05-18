# Release notes — Milestone 2 (Modul 11)

**Versi:** `v2.0`  
**Fase:** CI/CD & deployment (Minggu 9–11) — hardening & CD ke cloud.

## Ringkasan

Milestone ini menambahkan konfigurasi backend berbasis environment, pipeline CD ke Railway (opsional, memakai token), health check pasca-deploy di GitHub Actions, dokumentasi deployment & smoke test production, serta ringkasan deploy di Actions.

## Fitur & perubahan utama

- **Backend:** `backend/config.py` — `ENVIRONMENT`, `DEBUG`, `CORS_ORIGINS` (dengan fallback `ALLOWED_ORIGINS`), `LOG_LEVEL`, `DATABASE_URL`, `SECRET_KEY`, `TOKEN_EXPIRE_MINUTES`, `APP_VERSION`; integrasi di `app/main.py` (logging, CORS, versi API, payload `/health`).
- **CI/CD:** Job **Deploy to Railway** pada push ke `main` (setelah build Docker), ringkasan deployment (SHA, ref, timestamp), health check opsional ke `GET /health` bila secret `BACKEND_PRODUCTION_URL` di-set.
- **Dokumentasi:** `docs/deployment-guide.md`, `docs/production-test.md`, README (live demo placeholder, badge CI, roadmap 9–11).

## URL production

| Service | URL |
|---------|-----|
| Frontend (Laravel) | *(isi setelah deploy — mis. `https://….up.railway.app`)* |
| Backend (FastAPI) | *(isi setelah deploy)* |
| API docs (Swagger) | `{BACKEND_URL}/docs` |

## Tech stack (tetap)

- **Backend:** Python 3.12, FastAPI, Uvicorn, Azure DI/OCR, LangChain/OpenAI, PyMuPDF, dsb.
- **Frontend:** Laravel, Laravel Open Admin, MySQL (lokal/compose); produksi mengikuti konfigurasi Railway Anda.
- **CI:** GitHub Actions — lint (ruff), pytest backend, `php artisan test`, build Docker.
- **CD:** Railway CLI dari Actions (secret `RAILWAY_TOKEN`).

## Known issues

- Health check di CI membutuhkan secret `BACKEND_PRODUCTION_URL`; tanpa itu verifikasi HTTP ke cloud dilewati agar pipeline tetap lulus.
- `railway up` membutuhkan nama service (`backend`, `frontend`) dan proyek yang cocok dengan dashboard Railway; set `RAILWAY_PROJECT_ID` bila CLI meminta konteks proyek.
- Ukuran image Docker frontend besar (layer PHP/Laravel); build Actions dapat memakan waktu.

## Referensi

- [deployment-guide.md](deployment-guide.md)
- [production-test.md](production-test.md)
- Modul: `docs/2026-modul-praktikum-cloudcomputing/11-modul.md`
