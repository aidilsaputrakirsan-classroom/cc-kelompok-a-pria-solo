# Final Checklist — Modul 15 / UAS

Centang sebelum pertemuan 16 (UAS). Terakhir diperbarui: 2026-06-06.

## Repository

- [x] `README.md` lengkap dan up-to-date
- [x] `.env.example` di root + `backend/.env.example` + `frontend/.env.example`
- [x] `.gitignore` mencakup `.env`, `__pycache__`, `node_modules`, `vendor`
- [x] Tidak ada secret/API key hardcoded di kode sumber
- [ ] Tag `v3.0.0` dibuat setelah merge final polish

## Code & runtime

- [x] Semua services berjalan di Docker Compose (4 containers)
- [x] Laravel: login Open Admin, halaman utama, status page
- [x] Document Service: `/health`, `/metrics`, extraction & review
- [x] Gateway: routing + rate limiting (auth, API, general)
- [x] Structured logging JSON + correlation ID
- [x] `print()` di backend services diganti `logging`
- [ ] `console.log` debug di `frontend/public/js/*.js` — vendor diabaikan; custom JS boleh dibersihkan bertahap

## Security

- [x] Rate limiting Nginx aktif (`auth_limit`, `api_python_limit`, `general_limit`)
- [x] Respons 429 JSON dari gateway
- [x] CORS whitelist di FastAPI (`CORS_ORIGINS` / `ALLOWED_ORIGINS`)
- [x] Input validation Pydantic (ticket, ground_truth, PDF limits)
- [x] Password DB di `.env.example` memakai placeholder `CHANGE_ME`
- [x] Azure/OpenAI keys hanya via environment

## CI/CD

- [x] GitHub Actions CI pipeline (lint, test, build)
- [x] Deploy Railway pada push `main` (jika secrets di-set)
- [x] Production URL accessible (lihat README)

## Dokumentasi

- [x] [architecture.md](architecture.md)
- [x] [deployment-guide.md](deployment-guide.md)
- [x] [operations-guide.md](operations-guide.md)
- [x] [api-contract.md](api-contract.md)
- [x] [release-notes-m3.md](release-notes-m3.md)
- [x] [uas-presentation-outline.md](uas-presentation-outline.md)

## Presentasi UAS

- [ ] Slide 5–7 siap (Google Slides / PowerPoint)
- [x] Demo script tertulis (`uas-presentation-outline.md`)
- [ ] Backup video demo (~3 menit)
- [x] Pemahaman arsitektur keseluruhan (solo — semua peran)

## Verifikasi cepat

```bash
chmod +x scripts/verify-final.sh
./scripts/verify-final.sh
```

## Secret audit (manual)

```bash
grep -rn "password\|secret\|token\|api_key" \
  --include="*.py" --include="*.php" --include="*.yml" \
  backend/ frontend/app frontend/config services/ docker-compose.yml \
  | grep -v ".env.example" | grep -v "vendor" | grep -v "node_modules" | grep -v "__pycache__"
```

Harusnya hanya referensi `env()`, placeholder, atau dummy CI — bukan nilai asli.

## Sebelum hari UAS

- [ ] Pull kode terbaru dari `main`
- [ ] Test production URL
- [ ] Charger laptop + backup demo video
- [ ] Datang 15 menit lebih awal, test WiFi ruang UAS
