# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned

- CI pipeline (GitHub Actions) for automated lint and tests on pull requests (Modul 10–11).

---

## [1.0.0] — 2026-04-30

Milestone **1.0** (UTS / akhir Fase 1–2): full-stack **AI Document Validator** — FastAPI backend, Laravel + Open Admin frontend, stack berbasis Docker, dan dokumentasi untuk demo serta tugas praktikum Komputasi Awan (SI ITK).

### Added

#### Backend (FastAPI)

- REST API validasi dokumen: `POST /information-extraction` (upload PDF, OCR/ekstraksi), `POST /review` (validasi & advance review setelah ekstraksi).
- Endpoint pendukung: `GET /`, `GET /health`, `GET /team`.
- Integrasi **Azure AI Document Intelligence**, **LangChain** & **OpenAI** untuk ekstraksi dan review AI.
- Pemrosesan PDF/gambar (**PyMuPDF**, **pdf2image**, **Pillow**), validasi **Pydantic**, **SQLAlchemy** + MySQL.
- Dokumentasi OpenAPI di `/docs`.
- `Dockerfile` dan contoh environment (`backend/.env.example`, `backend/.env.docker.example`).

#### Frontend (Laravel + Open Admin)

- Antarmuka admin dan alur validasi dokumen ke FastAPI (**Guzzle**, `URL_VM_PYTHON`).
- Autentikasi session untuk area admin dan validasi dokumen.
- `Dockerfile` dan contoh environment (`frontend/.env.example`, `frontend/.env.docker.example`).

#### DevOps & tooling

- **Docker Compose**: MySQL 8, backend (8001), frontend (8000), volume DB, healthcheck.
- **Makefile**: lifecycle Compose, `migrate`, build/tag/push **Docker Hub**, `image-sizes`, target backend tunggal (legacy).
- Target PR (Modul 9): `make lint`, `make test`, `make pr-check`.

#### Dokumentasi

- `README.md` — arsitektur, stack, Docker, Makefile, Docker Hub, keamanan ringkas, tabel API.
- `docs/setup-guide.md`, `docs/api-dokumen-validasi-ai.md`, `docs/api-documentation.md`, `docs/api-test-results.md`, `docs/uts-demo-script.md`.

#### Rilis container (Modul 7)

- Image Docker Hub `dynofr/pria-solo-backend` dan `dynofr/pria-solo-frontend` tag **`v1`** / **`latest`** (digest tercatat di README).

### Security

- CORS whitelist **`ALLOWED_ORIGINS`** (bukan `*`).
- Secret hanya via environment; `*.env.docker` tidak di-commit.

### Changed

- N/A (baseline rilis pertama).

### Fixed

- N/A.

### Removed

- N/A.
