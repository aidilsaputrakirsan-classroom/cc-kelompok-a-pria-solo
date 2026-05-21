# Production smoke test (Modul 11)

Isi tabel setelah URL production (Railway atau platform lain) tersedia. Tandai **Status** dengan ✅ atau ❌.

## Alur smoke test

1. Buka URL **frontend** production — halaman load tanpa error fatal.
2. Login admin (sesuai mekanisme Laravel/Open Admin).
3. Akses alur validasi dokumen yang dipakai di demo (upload / ekstraksi / review sesuai fitur yang diaktifkan).
4. **GET** `{BACKEND_BASE}/health` — `status` harus `healthy` (HTTP 200).

## Dev vs production

| Test | Development (localhost) | Production (Railway / cloud) | Status |
|------|-------------------------|--------------------------------|--------|
| Backend `GET /health` | | | |
| Frontend halaman utama / admin | | | |
| Alur validasi dokumen (ringkas) | | | |
| Laravel → FastAPI (`URL_VM_PYTHON`) | | | |
| CORS tidak memblokir origin prod | | | |

## Catatan & tanggal

- Tanggal tes: _______________
- URL frontend production: _______________
- URL backend production: _______________

## Common issues (modul)

| Gejala | Penyebab umum | Tindakan |
|--------|----------------|----------|
| CORS error | Origin frontend tidak ada di `CORS_ORIGINS` / `ALLOWED_ORIGINS` | Update variabel backend di Railway |
| 502 Bad Gateway | Backend crash / port salah | Cek deploy logs; pastikan `PORT`/uvicorn listen sesuai image |
| Frontend memanggil API localhost | `URL_VM_PYTHON` / env produksi salah | Set ke URL backend HTTPS production |
| Health check CI gagal | URL salah atau cold start | Pastikan `BACKEND_PRODUCTION_URL` benar; pertimbangkan menaikkan jeda setelah deploy |
