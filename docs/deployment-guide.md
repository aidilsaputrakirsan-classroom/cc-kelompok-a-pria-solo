# Deployment Guide (Modul 11)

Panduan deploy aplikasi full-stack **FastAPI (`backend/`) + Laravel (`frontend/`)** ke **Railway**, secrets di **GitHub Actions**, dan CD otomatis setelah merge ke `main`.

## 1. Railway — ringkas

1. Login ke [Railway](https://railway.app/) dengan GitHub.
2. **New Project** → **Empty Project** → beri nama (mis. `cloudapp-pria-solo`).
3. **Add Service** → **Database** → **PostgreSQL** (opsional jika backend nanti memakai Postgres; stack lokal saat ini memakai MySQL untuk Laravel — sesuaikan dengan layanan yang Anda pasang di Railway).
4. **Add Service** → **GitHub Repo** → pilih repo ini.
5. Buat **dua** layanan dari repo yang sama (atau satu per satu):
   - **Backend:** **Root Directory** `backend`, builder **Dockerfile**, nama service disarankan: `backend`.
   - **Frontend:** **Root Directory** `frontend`, builder **Dockerfile**, nama service disarankan: `frontend`.
6. Di masing-masing service → **Settings** → **Networking** → **Generate Domain**; catat URL HTTPS publik.

Variabel penting (tab **Variables**), disesuaikan dengan stack Anda:

### Backend (Railway)

| Variable | Contoh / catatan |
|----------|-------------------|
| `ENVIRONMENT` | `production` |
| `SECRET_KEY` | Generate aman, mis. `python -c "import secrets; print(secrets.token_hex(32))"` |
| `CORS_ORIGINS` atau `ALLOWED_ORIGINS` | Origin Laravel production, dipisah koma (mis. `https://your-frontend.up.railway.app`) |
| `DATABASE_URL` | Jika memakai Postgres di Railway: `${{Postgres.DATABASE_URL}}` |
| `OPENAI_API_KEY`, `AZURE_*`, `TEMP_STORAGE`, dll. | Sesuai kebutuhan fitur AI/OCR |

### Frontend (Railway)

| Variable | Contoh / catatan |
|----------|------------------|
| `APP_URL` | URL publik frontend (Laravel) |
| `URL_VM_PYTHON` | Base URL backend FastAPI **tanpa** slash akhir |
| Variabel `DB_*` / `MYSQL_*` | Sesuai database yang dipakai Laravel di cloud |

## 2. GitHub — secrets untuk CD

Di repo: **Settings** → **Secrets and variables** → **Actions** → **New repository secret**:

| Secret | Keterangan |
|--------|------------|
| `RAILWAY_TOKEN` | Dari [railway.app/account/tokens](https://railway.app/account/tokens) — wajib agar job **Deploy to Railway** menjalankan CLI. |
| `RAILWAY_PROJECT_ID` | ID proyek Railway (disarankan). Buka project di Railway → **Settings** → **General** → **Project ID**. Menghindari error `railway up` non-interaktif. |
| `BACKEND_PRODUCTION_URL` | Base URL backend di production **tanpa** slash akhir (mis. `https://cloudapp-backend-xxx.up.railway.app`). Dipakai job **Health check (production)** setelah deploy. Jika kosong, health check ke cloud dilewati (CI tetap lulus). |

> Jangan pernah meng-commit token atau `.env` produksi ke Git.

## 3. Alur CI/CD (ringkas)

- **Pull request ke `main`:** lint, test backend, test frontend, build Docker — **tanpa** deploy.
- **Push ke `main`** (biasanya setelah merge PR): langkah yang sama + job **Deploy to Railway** (jika `RAILWAY_TOKEN` ada) + ringkasan di **Summary** tab Actions + opsional **GET `/health`** jika `BACKEND_PRODUCTION_URL` di-set.

Workflow: [.github/workflows/ci.yml](../.github/workflows/ci.yml).

## 4. Rollback manual (Lead DevOps)

Jika deploy terbaru bermasalah di production:

1. **Railway:** buka service yang bermasalah → **Deployments** → pilih deployment sebelumnya yang sehat → **Redeploy** / rollback sesuai UI Railway (istilah dapat berubah; intinya deploy ulang dari revisi image/commit yang stabil).
2. **Git:** revert commit yang bermasalah di branch `main`, lalu push (atau buat PR revert), agar pipeline berikutnya mendeploy revisi stabil.
3. **Database:** jika migrasi Laravel merusak data, pulihkan dari backup/snapshot DB jika tersedia — jangan mengandalkan rollback container saja untuk konsistensi data.

Setelah rollback, jalankan smoke test di production (lihat [production-test.md](production-test.md)).

## 5. Troubleshooting singkat

| Gejala | Arah cek |
|--------|----------|
| Deploy gagal di Actions | Log job **Deploy to Railway**; pastikan `RAILWAY_TOKEN` dan nama service (`backend` / `frontend`) cocok dengan Railway. |
| `railway up` tidak tahu project | Set secret `RAILWAY_PROJECT_ID`. |
| Health check gagal | Pastikan `BACKEND_PRODUCTION_URL` benar dan `/health` mengembalikan HTTP 200; cek log service backend di Railway. |
| CORS di browser | Pastikan `CORS_ORIGINS` / `ALLOWED_ORIGINS` backend memuat origin frontend production. |

## 6. Referensi modul

- Modul praktikum: `docs/2026-modul-praktikum-cloudcomputing/11-modul.md`
- Release Milestone 2: [release-notes-m2.md](release-notes-m2.md)
