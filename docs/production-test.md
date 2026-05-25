# Production smoke test (Modul 11)

Smoke test production Railway — tim **pria-solo**, Milestone 2.

## Alur smoke test

1. Buka URL **frontend** production — halaman load tanpa error fatal.
2. Login admin (Open Admin).
3. Akses alur validasi dokumen yang dipakai di demo (upload / ekstraksi / review sesuai fitur yang diaktifkan).
4. **GET** `{BACKEND_BASE}/health` — `status` harus `healthy` (HTTP 200).

## Dev vs production

| Test | Development (localhost) | Production (Railway) | Status |
|------|-------------------------|----------------------|--------|
| Backend `GET /health` | `http://127.0.0.1:8001/health` | [https://backend-production-bdd8.up.railway.app/health](https://backend-production-bdd8.up.railway.app/health) | ✅ |
| Frontend halaman utama / admin | `http://127.0.0.1:8000` | [https://cc-kelompok-a-pria-solo-production.up.railway.app](https://cc-kelompok-a-pria-solo-production.up.railway.app) | ✅ |
| Alur validasi dokumen (ringkas) | Lokal | Production (sesuai fitur aktif) | ✅ |
| Laravel → FastAPI (`URL_VM_PYTHON`) | `http://127.0.0.1:8001` | `https://backend-production-bdd8.up.railway.app` | ✅ |
| CORS tidak memblokir origin prod | N/A lokal | Origin frontend di `ALLOWED_ORIGINS` / `CORS_ORIGINS` backend | ✅ |
| Open Admin CSS / layout | Lokal | Production (`APP_URL`, `ADMIN_HTTPS`) | ✅ |

## Catatan & tanggal

- **Tanggal tes:** 2026-05-25
- **URL frontend production:** https://cc-kelompok-a-pria-solo-production.up.railway.app
- **URL backend production:** https://backend-production-bdd8.up.railway.app
- **Health sample:** `{"status":"healthy","service":"backend","version":"1.0.0","database":"not_applicable",...}`

## Common issues (modul)

| Gejala | Penyebab umum | Tindakan |
|--------|----------------|----------|
| CORS error | Origin frontend tidak ada di `CORS_ORIGINS` / `ALLOWED_ORIGINS` | Update variabel backend di Railway |
| 502 Bad Gateway | Backend crash / port salah | Cek deploy logs; pastikan port **8001** (backend) / **8000** (frontend) |
| Frontend memanggil API localhost | `URL_VM_PYTHON` salah | Set ke `https://backend-production-bdd8.up.railway.app` |
| Health check CI gagal | Secret `BACKEND_PRODUCTION_URL` kosong | Set ke base URL backend tanpa slash akhir |
| Open Admin tanpa CSS | `APP_URL` masih localhost | Set `APP_URL` + `ADMIN_HTTPS=true`, lalu `php artisan config:clear` |
