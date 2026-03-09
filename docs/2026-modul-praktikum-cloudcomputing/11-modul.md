# MODUL 11: CONTINUOUS DEPLOYMENT — AUTOMATED DEPLOY KE CLOUD

---

**Mata Kuliah:** Komputasi Awan  
**Program Studi:** Sistem Informasi - Institut Teknologi Kalimantan  
**SKS:** 3 (1 Kuliah + 2 Project)  
**Pertemuan:** 11 dari 16  
**Fase:** 🟠 CI/CD & Deployment (Minggu 9-11) — **Pertemuan Terakhir Fase CI/CD**  

---

## Prasyarat

Sebelum memulai pertemuan ini, pastikan:
- [x] CI Pipeline dari Modul 10 berjalan di GitHub Actions (semua tests passing)
- [x] Branch protection aktif: PR membutuhkan CI pass + review
- [x] Sudah punya akun di **Railway** (https://railway.app/) — sign up via GitHub
- [x] Sudah membaca materi deployment (Modul 10 Bagian D4)

> ⚠️ **Buat akun Railway SEKARANG jika belum!**  
> 1. Buka https://railway.app/
> 2. Klik **Login** → **Login with GitHub**
> 3. Authorize Railway
> 4. Anda mendapat **$5/bulan free credit** (trial plan) — cukup untuk mata kuliah ini
>
> **Alternatif:** Jika Railway bermasalah, gunakan **Render** (https://render.com/) — gratis untuk static site dan web service.

---

## Capaian Pembelajaran

### Sub-CPMK
Setelah menyelesaikan pertemuan ini, mahasiswa mampu:
1. Membedakan Continuous Delivery dan Continuous Deployment
2. Men-deploy aplikasi full-stack (backend + frontend + database) ke cloud platform
3. Mengelola secrets dan environment variables di GitHub Actions dan Railway
4. Mengkonfigurasi CD pipeline yang otomatis deploy setelah CI berhasil
5. Memahami konsep production environment dan perbedaannya dengan development

### Indikator Pencapaian
- Aplikasi backend berjalan di Railway dengan URL publik (misal: `https://cloudapp-backend-xxx.up.railway.app`)
- Database PostgreSQL tersedia di Railway dan terhubung ke backend
- Frontend ter-deploy dan bisa mengakses backend di cloud
- CD pipeline: merge ke `main` → CI pass → auto deploy ke Railway
- README memiliki link ke production URL

---

## Pembagian Fokus Tim Pertemuan Ini

| Peran | Fokus Utama | Juga Membantu |
|-------|-------------|---------------|
| **Lead DevOps** | Setup Railway project, konfigurasi deployment | — |
| **Lead Backend** | Pastikan backend berjalan di Railway, fix runtime errors | Env vars backend |
| **Lead Frontend** | Deploy frontend, konfigurasi API URL production | Env vars frontend |
| **Lead QA & Docs** | Testing production URL end-to-end, update README | Dokumentasi deployment |
| **Lead CI/CD** *(5 orang)* | Tulis CD workflow di GitHub Actions | Bantu debug deployment |

---

# BAGIAN A: PEMBEKALAN TEORI (50 Menit)

## 1. CI vs CD — Apa Bedanya? (15 menit)

### 1.1 CI/CD Pipeline Lengkap

```mermaid
flowchart LR
    subgraph CI ["🟢 Continuous Integration (Modul 10)"]
        PUSH["git push"] --> TEST["Run Tests"]
        TEST --> BUILD["Build Image"]
    end

    subgraph CD_DELIVERY ["🟡 Continuous Delivery"]
        BUILD --> STAGING["Deploy ke Staging"]
        STAGING --> APPROVAL{Manual Approval}
        APPROVAL -->|✅ Approved| PROD1["Deploy ke Production"]
    end

    subgraph CD_DEPLOYMENT ["🟠 Continuous Deployment"]
        BUILD --> PROD2["Auto Deploy<br/>ke Production"]
    end

    style CI fill:#E2EFDA,stroke:#548235
    style CD_DELIVERY fill:#FFF2CC,stroke:#BF8F00
    style CD_DEPLOYMENT fill:#FCE4D6,stroke:#BF8F00
```

| Konsep | Definisi | Kapan Deploy? | Risk Level |
|--------|----------|---------------|------------|
| **Continuous Integration** | Build + test otomatis setiap merge | Tidak deploy | Rendah |
| **Continuous Delivery** | CI + deploy ke staging otomatis, production **manual** | Setelah approval | Sedang |
| **Continuous Deployment** | CI + deploy ke production **otomatis** setiap merge ke main | Langsung setelah CI pass | Tinggi (butuh test kuat) |

> 💡 **Analogi:**  
> Bayangkan pabrik roti. **CI** adalah quality control — setiap roti dicek sebelum dikemas. **Continuous Delivery** adalah roti sudah dikemas dan siap kirim, tapi menunggu manajer menelepon kurir. **Continuous Deployment** adalah roti otomatis langsung dikirim ke toko begitu lolos QC — tidak ada campur tangan manusia.

### 1.2 Apa yang Kita Bangun

Untuk mata kuliah ini, kita mengimplementasikan **Continuous Delivery** (dengan opsi manual trigger deploy). Alasannya:
- Lebih aman untuk tim yang baru belajar
- Ada kesempatan review sebelum production deploy
- Tetap bisa di-upgrade ke full Continuous Deployment nanti

```mermaid
flowchart TD
    PR["Pull Request"] --> CI["🟢 CI: Tests + Build"]
    CI -->|Pass ✅| MERGE["Merge ke main"]
    MERGE --> CD["🟠 CD: Deploy ke Railway"]
    CD --> PROD["🌐 Production<br/>https://app.railway.app"]

    CI -->|Fail ❌| BLOCK["🔴 Merge diblokir"]

    style CI fill:#E2EFDA,stroke:#548235
    style CD fill:#FFF2CC,stroke:#BF8F00
    style PROD fill:#70AD47,color:#fff
    style BLOCK fill:#FF6B6B,color:#fff
```

---

## 2. Cloud Platform — Railway (15 menit)

### 2.1 Apa itu Railway?

**Railway** adalah Platform-as-a-Service (PaaS) yang memudahkan deploy aplikasi ke cloud. Railway menangani infrastruktur (server, network, SSL, scaling) sehingga kita fokus pada kode.

> 💡 **Ingat Modul 1?** Railway adalah contoh **PaaS** — kita hanya upload aplikasi, Railway urus sisanya (server, OS, network, SSL certificate, domain).

### 2.2 Kenapa Railway?

| Aspek | Railway | Alternatif (Render, Fly.io) |
|-------|---------|--------------------------|
| **Free tier** | $5/bulan credit (trial) | Render: free tier terbatas |
| **Deployment** | Auto dari GitHub | Auto dari GitHub |
| **Database** | PostgreSQL built-in | Perlu setup terpisah |
| **Docker support** | ✅ Deploy dari Dockerfile | ✅ |
| **CI/CD** | ✅ Auto deploy on push | ✅ |
| **Kemudahan** | Sangat mudah (link GitHub repo → done) | Mudah |

### 2.3 Arsitektur di Railway

```mermaid
flowchart TD
    subgraph RAILWAY ["☁️ Railway Cloud"]
        BE_SVC["🐍 Backend Service<br/>FastAPI<br/>cloudapp-backend.railway.app"]
        FE_SVC["⚛️ Frontend Service<br/>React (static/Nginx)<br/>cloudapp-frontend.railway.app"]
        DB_SVC["🗄️ PostgreSQL<br/>Managed Database<br/>Internal: postgresql://..."]
        
        BE_SVC -->|"DATABASE_URL"| DB_SVC
    end

    USER["👤 User Browser"] -->|"HTTPS"| FE_SVC
    FE_SVC -->|"API calls"| BE_SVC

    subgraph GITHUB ["🐱 GitHub"]
        REPO["Repository"] -->|"Auto deploy<br/>on push to main"| BE_SVC
        REPO -->|"Auto deploy"| FE_SVC
    end

    style RAILWAY fill:#DEEBF7,stroke:#2E75B6
    style GITHUB fill:#E2D9F3,stroke:#7B68A5
```

---

## 3. Secrets Management (10 menit)

### 3.1 Masalah: Secrets di Production

Di development, kita pakai file `.env` untuk secrets. Tapi di cloud:
- File `.env` **tidak di-push** ke GitHub (ada di `.gitignore`)
- Railway/cloud platform butuh secrets via **environment variables di dashboard**
- GitHub Actions juga butuh secrets untuk deployment

### 3.2 Di Mana Secrets Disimpan?

```mermaid
flowchart TD
    subgraph LOCAL ["💻 Development (Laptop)"]
        ENV[".env file<br/>(di .gitignore)"]
    end

    subgraph GITHUB_S ["🐱 GitHub"]
        GH_SECRETS["Repository Secrets<br/>(Settings → Secrets)"]
    end

    subgraph RAILWAY_S ["☁️ Railway"]
        RW_VARS["Environment Variables<br/>(Dashboard → Variables)"]
    end

    ENV -.- NOTE1["DB_URL=localhost:5432<br/>SECRET_KEY=dev-key"]
    GH_SECRETS -.- NOTE2["RAILWAY_TOKEN=xxx<br/>(untuk CD pipeline)"]
    RW_VARS -.- NOTE3["DATABASE_URL=postgresql://...<br/>SECRET_KEY=prod-key-random"]

    style LOCAL fill:#E2EFDA,stroke:#548235
    style GITHUB_S fill:#E2D9F3,stroke:#7B68A5
    style RAILWAY_S fill:#DEEBF7,stroke:#2E75B6
```

| Lokasi | Apa yang disimpan | Siapa yang akses |
|--------|-------------------|------------------|
| `.env` (lokal) | DB URL lokal, dev secrets | Developer di laptop |
| **GitHub Secrets** | Railway API token, deploy credentials | GitHub Actions workflow |
| **Railway Variables** | Production DB URL, JWT secret, CORS origins | Aplikasi di cloud |

### 3.3 GitHub Repository Secrets

GitHub Secrets di-enkripsi dan **tidak bisa dilihat** setelah disimpan — hanya bisa dipakai di workflow via `${{ secrets.NAMA_SECRET }}`.

```yaml
# Cara pakai di workflow
steps:
  - name: Deploy
    env:
      RAILWAY_TOKEN: ${{ secrets.RAILWAY_TOKEN }}
    run: railway up
```

> ⚠️ **JANGAN PERNAH** hardcode secrets di file workflow. Gunakan `${{ secrets.* }}` selalu.

---

## 4. Production vs Development (10 menit)

### 4.1 Perbedaan Environment

| Aspek | Development | Production |
|-------|-------------|------------|
| **Database** | PostgreSQL lokal (localhost:5432) | Railway managed PostgreSQL (URL unik) |
| **Backend URL** | `http://localhost:8000` | `https://xxx.railway.app` |
| **Frontend URL** | `http://localhost:5173` | `https://xxx.railway.app` |
| **CORS** | Allow localhost | Allow production domain saja |
| **Debug mode** | ✅ Aktif (reload, detailed errors) | ❌ Off (security, performance) |
| **SSL/HTTPS** | ❌ HTTP | ✅ HTTPS (otomatis dari Railway) |
| **Secret key** | `dev-secret-key` | Random 64-char string |
| **Data** | Dummy/test data | Real user data |

### 4.2 Konfigurasi Berbasis Environment

```mermaid
flowchart LR
    APP["🐍 FastAPI App"] --> CHECK{"os.environ<br/>['ENVIRONMENT']?"}
    CHECK -->|development| DEV["DB: localhost<br/>Debug: ON<br/>CORS: localhost:5173"]
    CHECK -->|production| PROD["DB: Railway URL<br/>Debug: OFF<br/>CORS: production domain"]

    style DEV fill:#E2EFDA,stroke:#548235
    style PROD fill:#DEEBF7,stroke:#2E75B6
```

> 📝 **Key Insight:** Aplikasi yang sama, kode yang sama — hanya **environment variables** yang berbeda. Ini adalah prinsip **12-Factor App Factor III: Config** yang sudah dibahas di Modul 4.

---

# BAGIAN B: WORKSHOP LAB (170 Menit)

## Workshop 11.1: Setup Railway Project (30 menit)

### Langkah 1: Buat Project di Railway

1. Buka https://railway.app/dashboard
2. Klik **New Project**
3. Pilih **Empty Project**
4. Beri nama: `cloudapp-team-XX` (ganti XX dengan nomor tim)

### Langkah 2: Tambah PostgreSQL Database

1. Di dalam project, klik **+ Add Service** → **Database** → **Add PostgreSQL**
2. Railway otomatis membuat PostgreSQL instance
3. Klik service PostgreSQL → tab **Variables**
4. Catat (atau copy) variable: `DATABASE_URL`

```
postgresql://postgres:XXXXX@containers-xxx.railway.internal:5432/railway
```

> 📝 `DATABASE_URL` ini akan dipakai backend untuk connect ke database. Format: `postgresql://user:password@host:port/dbname`

### Langkah 3: Deploy Backend

1. Klik **+ Add Service** → **GitHub Repo**
2. Pilih repository tim Anda
3. Railway akan mendeteksi Dockerfile di `backend/`

**Konfigurasi service:**
- **Service name:** `backend`
- **Root Directory:** `/backend` (penting! agar Railway hanya build folder backend)
- **Builder:** Dockerfile

4. Klik tab **Settings** → scroll ke **Networking**
5. Klik **Generate Domain** → catat URL yang diberikan (misal: `cloudapp-backend-xxx.up.railway.app`)

### Langkah 4: Set Environment Variables Backend

Klik tab **Variables** pada service backend, lalu tambahkan:

| Variable | Value | Catatan |
|----------|-------|---------|
| `DATABASE_URL` | `${{Postgres.DATABASE_URL}}` | Reference ke PostgreSQL service |
| `SECRET_KEY` | (generate random) | Gunakan: `python -c "import secrets; print(secrets.token_hex(32))"` |
| `CORS_ORIGINS` | `https://cloudapp-frontend-xxx.up.railway.app` | URL frontend (set setelah frontend deploy) |
| `ENVIRONMENT` | `production` | Untuk conditional config |

> 💡 **Tips:** Railway mendukung **variable referencing** dengan syntax `${{ServiceName.VARIABLE_NAME}}`. Ini lebih aman dan otomatis ter-update.

### Langkah 5: Verifikasi Backend

Buka URL backend di browser: `https://cloudapp-backend-xxx.up.railway.app/health`

Response yang diharapkan:
```json
{
  "status": "healthy",
  "service": "backend",
  "version": "1.0.0",
  "database": "connected"
}
```

Jika muncul error, cek:

| Gejala | Kemungkinan Penyebab | Solusi |
|--------|---------------------|--------|
| `Application failed to start` | Dockerfile error, missing dependency | Cek tab **Deploy Logs** di Railway |
| `500 Internal Server Error` | Database belum connect | Cek `DATABASE_URL` di Variables |
| `CORS error` saat akses dari browser | CORS belum dikonfigurasi | Set `CORS_ORIGINS` dengan URL frontend |
| `ModuleNotFoundError` | Library tidak ada di requirements.txt | Tambahkan, push, Railway auto-redeploy |

> ✅ **Checkpoint:** Endpoint `/health` mengembalikan status `healthy` dan database `connected`.

---

## Workshop 11.2: Deploy Frontend (25 menit)

### Langkah 1: Update API URL untuk Production

Sebelum deploy, frontend perlu tahu URL backend production.

File: `frontend/.env.production`

```
VITE_API_URL=https://cloudapp-backend-xxx.up.railway.app
```

> 📝 Vite secara otomatis menggunakan `.env.production` saat `npm run build`. File ini BOLEH di-commit karena hanya berisi URL publik (bukan secrets).

### Langkah 2: Pastikan Frontend Build Berhasil Secara Lokal

```bash
cd frontend
npm run build
```

Verifikasi folder `dist/` terbuat tanpa error.

### Langkah 3: Deploy Frontend di Railway

**Opsi A: Deploy via Railway (Dockerfile)**

1. Di project Railway, klik **+ Add Service** → **GitHub Repo**
2. Pilih repository yang sama
3. **Root Directory:** `/frontend`
4. **Builder:** Dockerfile (menggunakan multi-stage build dari Modul 6)
5. **Generate Domain** → catat URL frontend

**Opsi B: Deploy via Railway (Static Site — lebih ringan)**

Jika frontend sudah di-build menjadi file statis, Railway bisa serve langsung tanpa Docker:

1. **+ Add Service** → **GitHub Repo**
2. **Root Directory:** `/frontend`
3. Klik tab **Settings**:
   - **Build Command:** `npm ci && npm run build`
   - **Start Command:** `npx serve dist -s -l 3000`
4. Tambah environment variable: `PORT=3000`
5. **Generate Domain**

> 📝 Opsi A (Dockerfile/Nginx) lebih robust untuk production. Opsi B lebih cepat setup. Pilih yang lebih sesuai untuk tim Anda.

### Langkah 4: Update CORS di Backend

Sekarang frontend sudah punya URL, update CORS di backend:

1. Buka service backend di Railway → tab **Variables**
2. Update `CORS_ORIGINS`:
   ```
   https://cloudapp-frontend-xxx.up.railway.app
   ```
3. Railway akan auto-redeploy backend

### Langkah 5: Verifikasi Frontend

1. Buka URL frontend di browser
2. Cek apakah bisa register, login, dan CRUD items

```mermaid
flowchart LR
    USER["👤 Browser"] -->|HTTPS| FE["⚛️ Frontend<br/>Railway"]
    FE -->|"API Request<br/>HTTPS"| BE["🐍 Backend<br/>Railway"]
    BE -->|"SQL"| DB["🗄️ PostgreSQL<br/>Railway"]

    style FE fill:#E2EFDA,stroke:#548235
    style BE fill:#DEEBF7,stroke:#2E75B6
    style DB fill:#FFF2CC,stroke:#BF8F00
```

> ✅ **Checkpoint:** Frontend di cloud bisa melakukan CRUD operations ke backend di cloud. Data tersimpan di PostgreSQL Railway.

---

## Workshop 11.3: Setup CD Pipeline (40 menit)

### Langkah 1: Generate Railway Token

1. Buka https://railway.app/account/tokens
2. Klik **Create Token**
3. Beri nama: `github-actions-deploy`
4. Copy token (hanya ditampilkan sekali!)

### Langkah 2: Simpan Token di GitHub Secrets

1. Buka repository → **Settings** → **Secrets and variables** → **Actions**
2. Klik **New repository secret**
3. Tambahkan:

| Name | Value |
|------|-------|
| `RAILWAY_TOKEN` | (paste token dari step 1) |

### Langkah 3: Install Railway CLI di Workflow

Update file `.github/workflows/ci.yml` — tambahkan job CD setelah job `build-docker`:

```yaml
  # ================================
  # JOB 4: Deploy ke Railway
  # ================================
  deploy:
    name: 🚀 Deploy to Railway
    runs-on: ubuntu-latest
    needs: [test-backend, test-frontend, build-docker]
    if: github.ref == 'refs/heads/main' && github.event_name == 'push'
    # ↑ HANYA deploy saat push ke main (bukan saat PR)

    steps:
      - name: 📥 Checkout code
        uses: actions/checkout@v4

      - name: 🚂 Install Railway CLI
        run: npm install -g @railway/cli

      - name: 🚀 Deploy Backend
        env:
          RAILWAY_TOKEN: ${{ secrets.RAILWAY_TOKEN }}
        run: |
          cd backend
          railway up --service backend --detach

      - name: 🚀 Deploy Frontend
        env:
          RAILWAY_TOKEN: ${{ secrets.RAILWAY_TOKEN }}
        run: |
          cd frontend
          railway up --service frontend --detach

      - name: ✅ Deploy complete
        run: |
          echo "✅ Deployment successful!"
          echo "Backend: https://cloudapp-backend-xxx.up.railway.app"
          echo "Frontend: https://cloudapp-frontend-xxx.up.railway.app"
```

### Langkah 4: Pahami Alur CD

```mermaid
flowchart TD
    subgraph TRIGGER ["🔔 Triggers"]
        PR_PUSH["Push di PR branch"] --> CI_ONLY["Hanya CI<br/>(test + build)"]
        MAIN_PUSH["Push/Merge ke main"] --> CI_CD["CI + CD<br/>(test + build + deploy)"]
    end

    subgraph PIPELINE ["🔄 Pipeline"]
        T1["🐍 test-backend"]
        T2["⚛️ test-frontend"]
        T1 --> BUILD["🐳 build-docker"]
        T2 --> BUILD
        BUILD --> DEPLOY["🚀 deploy<br/>(hanya jika push ke main)"]
    end

    CI_ONLY --> T1
    CI_ONLY --> T2
    CI_CD --> T1
    CI_CD --> T2

    style CI_ONLY fill:#E2EFDA,stroke:#548235
    style CI_CD fill:#FFF2CC,stroke:#BF8F00
    style DEPLOY fill:#70AD47,color:#fff
```

Perhatikan kunci keamanan di workflow:
```yaml
if: github.ref == 'refs/heads/main' && github.event_name == 'push'
```
Artinya: deploy **HANYA** terjadi saat kode sudah merge ke `main`. PR branch hanya menjalankan CI (test + build), BUKAN deploy.

### Langkah 5: Push & Trigger CD

```bash
git checkout main
git pull origin main
git checkout -b feature/add-cd-pipeline

# Update workflow file
git add .github/workflows/ci.yml
git commit -m "feat: add CD pipeline — auto deploy to Railway on main push

- Add deploy job: runs after all tests pass
- Deploy backend and frontend via Railway CLI
- Only triggers on push to main (not on PR)
- Railway token stored in GitHub Secrets"

git push origin feature/add-cd-pipeline
```

1. Buat PR → CI berjalan (tanpa deploy ✅)
2. Review & merge PR
3. Setelah merge → CI + CD berjalan → deploy otomatis ke Railway

### Langkah 6: Monitor Deployment

1. Buka GitHub → tab **Actions** → lihat workflow run terbaru
2. Klik job **🚀 Deploy to Railway** → lihat log
3. Buka Railway dashboard → cek status deployment

> ✅ **Checkpoint:** Merge ke main memicu CI + CD. Setelah pipeline selesai, perubahan otomatis live di Railway.

---

## Workshop 11.4: Production Testing (25 menit)

### Langkah 1: Smoke Test Checklist

Setelah deploy, lakukan **smoke test** — test cepat untuk memastikan fitur kritis berfungsi:

```mermaid
flowchart TD
    START([🟢 Buka URL Frontend Production]) --> LOAD["1️⃣ Halaman load<br/>tanpa error?"]
    LOAD --> REGISTER["2️⃣ Register user baru"]
    REGISTER --> LOGIN["3️⃣ Login dengan user tersebut"]
    LOGIN --> CREATE["4️⃣ Create item baru"]
    CREATE --> READ["5️⃣ Item muncul di daftar?"]
    READ --> UPDATE["6️⃣ Edit item"]
    UPDATE --> DELETE["7️⃣ Hapus item"]
    DELETE --> HEALTH["8️⃣ GET /health<br/>→ status: healthy?"]
    HEALTH --> END([🟢 PRODUCTION OK ✅])

    style START fill:#70AD47,color:#fff
    style END fill:#70AD47,color:#fff
```

### Langkah 2: Bandingkan Dev vs Production

| Test | Development (localhost) | Production (Railway) | Status |
|------|------------------------|---------------------|--------|
| Backend `/health` | ✅ | ✅ / ❌ | |
| Register user | ✅ | ✅ / ❌ | |
| Login | ✅ | ✅ / ❌ | |
| Create item | ✅ | ✅ / ❌ | |
| Read items | ✅ | ✅ / ❌ | |
| Update item | ✅ | ✅ / ❌ | |
| Delete item | ✅ | ✅ / ❌ | |
| Search | ✅ | ✅ / ❌ | |

Isi tabel ini dan dokumentasikan di `docs/production-test.md`.

### Common Production Issues

| Gejala | Penyebab | Solusi |
|--------|----------|--------|
| CORS error di browser console | CORS_ORIGINS tidak sesuai URL frontend | Update env var di Railway |
| `502 Bad Gateway` | Backend crash / port salah | Cek deploy logs, pastikan `PORT` env var |
| Login gagal (token error) | SECRET_KEY berbeda antara deploy | Pastikan SECRET_KEY konsisten di env vars |
| Data hilang setelah redeploy | Database volume tidak persist | Gunakan Railway PostgreSQL service (bukan SQLite) |
| Frontend blank page | API_URL masih localhost | Update `.env.production`, rebuild |

> ✅ **Checkpoint:** Semua 8 langkah smoke test pass di production URL.

---

## Workshop 11.5: Update README & Dokumentasi (15 menit)

### Update README.md

Tambahkan section baru di README:

```markdown
## 🌐 Live Demo

| Service | URL |
|---------|-----|
| Frontend | [https://cloudapp-frontend-xxx.up.railway.app](https://cloudapp-frontend-xxx.up.railway.app) |
| Backend API | [https://cloudapp-backend-xxx.up.railway.app](https://cloudapp-backend-xxx.up.railway.app) |
| API Docs (Swagger) | [https://cloudapp-backend-xxx.up.railway.app/docs](https://cloudapp-backend-xxx.up.railway.app/docs) |

## 🔄 CI/CD

![CI Pipeline](https://github.com/OWNER/REPO/actions/workflows/ci.yml/badge.svg)

Pipeline otomatis berjalan saat push ke main:
1. ✅ Test backend (pytest)
2. ✅ Test frontend (Vitest)
3. ✅ Build Docker images
4. 🚀 Deploy ke Railway
```

### Buat docs/deployment-guide.md

```markdown
# Deployment Guide

## Railway Setup
1. Login ke Railway via GitHub
2. Buat project baru
3. Tambah PostgreSQL database service
4. Deploy backend (root: /backend)
5. Deploy frontend (root: /frontend)

## Environment Variables

### Backend (Railway)
| Variable | Contoh Value |
|----------|-------------|
| DATABASE_URL | ${{Postgres.DATABASE_URL}} |
| SECRET_KEY | (random hex 64 chars) |
| CORS_ORIGINS | https://frontend-url.railway.app |
| ENVIRONMENT | production |

### Frontend (Railway)
| Variable | Contoh Value |
|----------|-------------|
| VITE_API_URL | https://backend-url.railway.app |

### GitHub Secrets
| Secret | Keterangan |
|--------|-----------|
| RAILWAY_TOKEN | Token dari railway.app/account/tokens |

## Troubleshooting
(isi berdasarkan pengalaman deploy tim)
```

> ✅ **Checkpoint:** README memiliki link production dan CI/CD badge. Deployment guide lengkap.

---

## Workshop 11.6: Commit & Verify (15 menit)

### Struktur Repository Akhir

```
cloud-team-XX/
├── .github/
│   ├── CODEOWNERS
│   ├── pull_request_template.md
│   └── workflows/
│       └── ci.yml                     ← Updated (+ deploy job)
├── backend/
│   ├── main.py
│   ├── ... (semua file backend)
│   ├── tests/
│   ├── Dockerfile
│   └── .dockerignore
├── frontend/
│   ├── src/
│   │   └── ...
│   ├── .env.production                ← Baru (production API URL)
│   ├── Dockerfile
│   └── .dockerignore
├── docs/
│   ├── deployment-guide.md            ← Baru
│   ├── production-test.md             ← Baru
│   ├── retrospective-m1.md
│   └── ...
├── docker-compose.yml
├── Makefile
└── README.md                          ← Updated (live URLs, CI/CD badge)
```

### Commit & Push via PR

```bash
git checkout -b feature/add-cd-deploy

git add .github/workflows/ci.yml
git add frontend/.env.production
git add docs/deployment-guide.md docs/production-test.md
git add README.md

git commit -m "feat: add CD pipeline and deploy full-stack to Railway

- Extend CI workflow with deploy job (Railway CLI)
- Deploy backend + frontend + PostgreSQL to Railway
- Add .env.production for frontend API URL
- Add deployment-guide.md and production-test.md
- Update README with live URLs and CI/CD section"

git push origin feature/add-cd-deploy
```

Buat PR → review → merge. Setelah merge, CD pipeline akan otomatis deploy.

> ✅ **Checkpoint Akhir Workshop:** Aplikasi full-stack berjalan di cloud. CD pipeline otomatis. README memiliki live URL.

---

# BAGIAN C: TUGAS TERSTRUKTUR (60 Menit)

> 📝 **Kumpulkan sebelum pertemuan 12** via Pull Request.
>
> ⚠️ **Ini adalah tugas terakhir fase CI/CD!** Pastikan deployment stabil sebelum masuk ke Microservices.

---

## Tugas 11: Production Hardening & Milestone 2

### Pembagian Tugas

| Anggota | Branch Name | Tugas | Detail |
|---------|-------------|-------|--------|
| **Lead Backend** | `feature/production-config` | Buat konfigurasi berbasis environment | Buat file `backend/config.py` yang membaca env vars dengan default values. Pisahkan config dev vs prod: DEBUG mode, CORS origins, log level. Pastikan app tidak crash jika env var missing (fallback ke default). |
| **Lead Frontend** | `feature/production-build` | Optimasi frontend untuk production | Pastikan `.env.production` benar. Tambah error boundary React (tampilkan pesan user-friendly jika API error). Verifikasi build size (`npm run build` → cek `dist/` size). |
| **Lead DevOps** | `feature/deploy-workflow-improve` | Tambah health check di CD pipeline | Setelah deploy, tambah step yang memanggil `/health` endpoint dan verifikasi response. Jika health check gagal, workflow gagal (alert). Tambahkan rollback manual instruction di deployment guide. |
| **Lead QA & Docs** | `docs/milestone2-release` | Buat release notes Milestone 2 | Buat `docs/release-notes-m2.md`: fitur yang sudah ada, URL production, known issues, tech stack. Update roadmap di README (week 9-11 ✅). Tag release: `git tag v2.0`. |
| **Lead CI/CD** *(5 orang)* | `feature/deploy-notification` | Tambah notifikasi deployment | Tambah step di workflow yang menampilkan deployment summary (URLs, commit SHA, timestamp) sebagai PR comment atau output. |

### Contoh: Backend Config

File: `backend/config.py`

```python
import os

class Settings:
    """Application settings — dibaca dari environment variables."""
    
    ENVIRONMENT: str = os.getenv("ENVIRONMENT", "development")
    DEBUG: bool = ENVIRONMENT == "development"
    
    # Database
    DATABASE_URL: str = os.getenv(
        "DATABASE_URL", 
        "postgresql://postgres:postgres@localhost:5432/cloudapp"
    )
    
    # Auth
    SECRET_KEY: str = os.getenv("SECRET_KEY", "dev-secret-key-change-in-production")
    ACCESS_TOKEN_EXPIRE_MINUTES: int = int(os.getenv("TOKEN_EXPIRE_MINUTES", "30"))
    
    # CORS
    CORS_ORIGINS: list = os.getenv(
        "CORS_ORIGINS", 
        "http://localhost:5173,http://localhost:3000"
    ).split(",")
    
    # Logging
    LOG_LEVEL: str = os.getenv("LOG_LEVEL", "DEBUG" if DEBUG else "INFO")

settings = Settings()
```

### Contoh: Health Check di CD

```yaml
      - name: 🏥 Health check
        run: |
          echo "Waiting for deployment to be ready..."
          sleep 30
          
          HEALTH_URL="https://cloudapp-backend-xxx.up.railway.app/health"
          HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" $HEALTH_URL)
          
          if [ "$HTTP_STATUS" == "200" ]; then
            echo "✅ Health check passed! (HTTP $HTTP_STATUS)"
          else
            echo "❌ Health check FAILED! (HTTP $HTTP_STATUS)"
            echo "Check Railway dashboard for logs."
            exit 1
          fi
```

### Deliverable Akhir Fase CI/CD

Sebelum masuk ke Microservices (minggu 12), pastikan:

```mermaid
flowchart TD
    subgraph CHECKLIST ["✅ Fase CI/CD Checklist"]
        C1["Git: GitHub Flow (branch + PR + review)"]
        C2["CI: Tests otomatis (pytest + Vitest)<br/>Build Docker otomatis"]
        C3["CD: Auto deploy ke Railway setelah merge<br/>Health check pass"]
        C4["Production: Full-stack app live<br/>URL publik, HTTPS, database cloud"]
        C5["Docs: README + deployment guide<br/>+ release notes v2.0"]
    end

    C1 --> READY([🟢 SIAP MICROSERVICES!])
    C2 --> READY
    C3 --> READY
    C4 --> READY
    C5 --> READY

    style CHECKLIST fill:#E2EFDA,stroke:#548235
    style READY fill:#70AD47,color:#fff
```

### Informasi Pengumpulan

| Item | Keterangan |
|------|------------|
| **Deadline** | Sebelum pertemuan 12 dimulai |
| **Format** | Pull Request ke repository tim — HARUS lulus CI |
| **Yang dinilai** | Config env-based, health check CI, docs lengkap, production stabil, semua anggota ≥1 PR |
| **Penilaian khusus** | Tim yang production URL berfungsi tanpa error saat dicek dosen → bonus |

---

# BAGIAN D: BELAJAR MANDIRI (230 Menit)

> 📚 **Tidak dikumpulkan**, tetapi sangat penting untuk pemahaman.

---

## D1. Membaca Referensi (60 menit)

### Bacaan Wajib
1. **12-Factor App — Semua 12 Faktor**  
   https://12factor.net/  
   (Baca keseluruhan — prinsip-prinsip ini menjadi fondasi arsitektur cloud-native)

2. **Microservices by Martin Fowler** (persiapan minggu depan)  
   https://martinfowler.com/articles/microservices.html  
   (Artikel klasik yang mendefinisikan microservices)

3. **Railway Documentation — Deployments**  
   https://docs.railway.app/guides/deployments  
   (Referensi deployment Railway)

### Bacaan Tambahan
- GitHub Actions — Secrets Documentation — https://docs.github.com/en/actions/security-for-github-actions/security-guides/using-secrets-in-github-actions
- Render Documentation (alternatif) — https://docs.render.com/
- 12-Factor App Summary — https://www.clearlytech.com/2014/01/04/12-factor-apps-plain-english/

---

## D2. Video Tutorial (60 menit)

1. **"Microservices Explained in 5 Minutes"** — IBM Technology (YouTube, ~5 min)
   - Overview microservices vs monolith

2. **"Microservices Architecture"** — TechWorld with Nana (YouTube, ~20 min)
   - Arsitektur, komunikasi antar service, pros & cons

3. **"Deploy to Railway"** — cari di YouTube (~10 min)
   - Tutorial deploy aplikasi ke Railway

4. **"12-Factor App"** — cari di YouTube (~15 min)
   - Penjelasan visual 12 faktor

> ⚠️ **Video Microservices SANGAT penting!** Minggu depan kita mulai memecah monolith menjadi microservices. Pahami konsepnya sebelum hands-on.

---

## D3. Latihan Mandiri (60 menit)

### Soal Pilihan Ganda

**1.** Perbedaan utama Continuous Delivery dan Continuous Deployment adalah:
- [ ] a. Continuous Delivery tidak perlu testing
- [ ] b. Continuous Deployment hanya untuk mobile app
- [ ] c. Continuous Delivery membutuhkan approval manual sebelum deploy ke production
- [ ] d. Tidak ada perbedaan, keduanya sama

**2.** Railway termasuk dalam kategori cloud service model:
- [ ] a. IaaS (Infrastructure as a Service)
- [ ] b. SaaS (Software as a Service)
- [ ] c. FaaS (Function as a Service)
- [ ] d. PaaS (Platform as a Service)

**3.** `${{ secrets.RAILWAY_TOKEN }}` di GitHub Actions berguna untuk:
- [ ] a. Menampilkan token di log agar bisa di-debug
- [ ] b. Menyimpan token di repository sebagai file
- [ ] c. Membuat token baru setiap workflow run
- [ ] d. Mengakses secret yang terenkripsi tanpa mengeksposnya di log

**4.** Mengapa CD pipeline hanya berjalan saat push ke `main`, bukan saat PR?
- [ ] a. GitHub Actions tidak mendukung deploy di PR
- [ ] b. PR branch belum direview dan ditest — deploy hanya aman dari kode yang sudah divalidasi di main
- [ ] c. Railway hanya mendukung branch main
- [ ] d. Tidak ada alasan teknis, hanya preferensi

**5.** Prinsip 12-Factor App yang paling relevan untuk perbedaan dev vs production config adalah:
- [ ] a. Factor I: Codebase
- [ ] b. Factor V: Build, Release, Run
- [ ] c. Factor X: Dev/Prod Parity
- [ ] d. Factor III: Config

---

## D4. Persiapan Pertemuan Berikutnya (50 menit)

Pertemuan 12 memulai **Fase Microservices** — kita akan memecah monolith menjadi service-service independen. Persiapkan:

- Apa itu **microservices** dan apa bedanya dengan monolith?
- Apa itu **bounded context** dalam Domain-Driven Design?
- Bagaimana microservices saling berkomunikasi? (REST, message queue)
- Apa itu **API Gateway** dan mengapa diperlukan?
- Baca: https://martinfowler.com/articles/microservices.html
- Baca: https://microservices.io/patterns/index.html

> 💡 **Preview:** Kita akan memecah aplikasi yang sudah ada menjadi 2 service: **Auth Service** dan **Item Service**. Setiap service punya database sendiri dan berkomunikasi via REST API. Ini adalah arsitektur yang digunakan oleh Netflix, Spotify, Grab, dan Gojek.
>

---

---

*Modul ini disusun oleh Aidil Saputra Kirsan, Institut Teknologi Kalimantan.*
