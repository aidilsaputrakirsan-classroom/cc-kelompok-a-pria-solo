# Release Notes — Milestone 3 (Final / UAS)

## Version: 3.0.0

**Release Date:** 2026-06-06  
**Tag:** `v3.0.0`  
**Fase:** Modul 15 — Final Polish (security, dokumentasi, persiapan UAS)

## Ringkasan

Milestone 3 menyelesaikan hardening keamanan, dokumentasi profesional, dan kesiapan demo UAS untuk **PRIA Solo — AI Document Validator**. Arsitektur tetap: Laravel + Open Admin (web & auth) + FastAPI document-service + Nginx gateway + MySQL, dengan observability dari Modul 14.

## Fitur & perubahan utama (Modul 15)

### Security Hardening

- Rate limiting Nginx diperluas:
  - **5 req/s** — OpenAdmin login (`/projess/auth/login`)
  - **20 req/s** — Document API (`/api/python/*`)
  - **30 req/s** — traffic Laravel umum
- Respons **HTTP 429** JSON konsisten saat rate limit terpicu
- Secret audit: tidak ada API key/password hardcoded di source; kredensial via `.env` / GitHub Secrets
- Root `.env.example` + password placeholder `CHANGE_ME` di contoh env
- Docker Compose MySQL memakai variabel `${MYSQL_*}` (bukan nilai tetap di production)

### Code Quality

- `print()` di service validasi/OCR diganti structured `logging`
- `backend/pyproject.toml` — konfigurasi Black, isort, ruff

### Dokumentasi

- `docs/api-contract.md` — kontrak API formal
- `docs/uas-presentation-outline.md` — outline slide & demo script UAS
- `docs/final-checklist.md` — checklist akhir sebelum UAS
- README diperbarui: arsitektur, security, evolution journey, roadmap

### Operasional

- `scripts/verify-final.sh` — skrip verifikasi end-to-end lokal

## Arsitektur (final)

| Service | Peran |
|---------|--------|
| `gateway` (Nginx :8080) | Reverse proxy, rate limiting, routing |
| `frontend` (Laravel :8000) | Open Admin, session auth, UI, job ke FastAPI |
| `document-service` (FastAPI :8001) | OCR, ekstraksi, AI review |
| `db` (MySQL 8 :3307) | Database aplikasi |

## Statistik proyek

| Metric | Nilai |
|--------|-------|
| Total containers (Compose) | 4 |
| FastAPI endpoints (utama) | 8+ (`/health`, `/metrics`, `/stats`, `/public`, `/information-extraction`, `/review`, …) |
| Backend unit tests (pytest) | 5+ files |
| Frontend tests (PHPUnit) | 2+ |
| CI pipeline jobs | lint, test, build, deploy, health check |
| Total commits (repo) | ~45 |
| Merged PRs | ~9 |

## Known issues

- Ukuran image Docker frontend besar (~7.5 GB) — dampak waktu build/push
- FastAPI `/health` menampilkan `database: not_applicable` — DB utama di Laravel/MySQL
- Fitur OCR/AI membutuhkan `AZURE_*` dan `OPENAI_API_KEY` di environment Railway
- FastAPI tidak memakai JWT; akses production mengandalkan jaringan internal + Laravel sebagai caller

## URL production

| Service | URL |
|---------|-----|
| Frontend | https://cc-kelompok-a-pria-solo-production.up.railway.app |
| Open Admin | https://cc-kelompok-a-pria-solo-production.up.railway.app/projess/auth/login |
| Backend API | https://backend-production-bdd8.up.railway.app |
| Health | https://backend-production-bdd8.up.railway.app/health |

## Kontribusi

| Nama | NIM | Peran | Area utama |
|------|-----|-------|------------|
| Dyno Fadillah Ramadhani | 10231033 | Lead Backend / Frontend / DevOps / QA | Full-stack, CI/CD, dokumentasi |

## Referensi

- [architecture.md](architecture.md)
- [api-contract.md](api-contract.md)
- [deployment-guide.md](deployment-guide.md)
- [operations-guide.md](operations-guide.md)
- [final-checklist.md](final-checklist.md)
- Modul: `docs/2026-modul-praktikum-cloudcomputing/15-modul.md`
