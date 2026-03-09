# MODUL 5: DOCKER FUNDAMENTALS — DOCKERFILE, IMAGE & CONTAINER

---

**Mata Kuliah:** Komputasi Awan  
**Program Studi:** Sistem Informasi - Institut Teknologi Kalimantan  
**SKS:** 3 (1 Kuliah + 2 Project)  
**Pertemuan:** 5 dari 16  
**Fase:** 🔵 Containerization (Minggu 5-7)  

---

## Prasyarat

Sebelum memulai pertemuan ini, pastikan:
- [x] Full-stack app dari Fase Foundation berjalan (backend + frontend + auth)
- [x] **Docker Desktop terinstall dan running** (ikon Docker di system tray/menu bar)
- [x] Sudah membaca/menonton materi Docker dasar (Modul 4 Bagian D4)

> ⚠️ **Docker Desktop WAJIB sudah terinstall!**  
> Verifikasi sekarang:
> ```bash
> docker --version       # Harus muncul versi (misal: Docker version 27.x)
> docker compose version # Harus muncul versi
> ```
> Jika belum terinstall, **segera minta bantuan asdos** di awal workshop.

---

## Capaian Pembelajaran

### Sub-CPMK
Setelah menyelesaikan pertemuan ini, mahasiswa mampu:
1. Memahami konsep containerization: image, container, registry, dan perbedaannya dengan virtual machine
2. Menulis Dockerfile untuk aplikasi Python (FastAPI backend)
3. Membangun Docker image dan menjalankan container
4. Memahami konsep layer caching untuk optimasi build
5. Melakukan push image ke Docker Hub

### Indikator Pencapaian
- Mahasiswa bisa menjelaskan perbedaan container vs VM
- Backend FastAPI berjalan di dalam Docker container
- Dockerfile menggunakan best practices (layer ordering, .dockerignore)
- Image berhasil di-push ke Docker Hub

---

## Pembagian Fokus Tim Pertemuan Ini

| Peran | Fokus Utama | Juga Membantu |
|-------|-------------|---------------|
| **Lead DevOps** | Menulis Dockerfile, build image, push ke Docker Hub | — |
| **Lead Backend** | Pastikan app berjalan di container, debug runtime errors | Review Dockerfile |
| **Lead Frontend** | Observasi & pelajari Docker (minggu depan giliran frontend) | Bantu testing |
| **Lead QA & Docs** | Testing container, dokumentasi Docker commands | Update README |
| **Lead CI/CD** *(5 orang)* | Buat `.dockerignore`, dokumentasi image size & build time | Bantu troubleshoot |

---

# BAGIAN A: PEMBEKALAN TEORI (50 Menit)

## 1. Mengapa Docker? (10 menit)

### 1.1 Masalah Klasik: "Works on My Machine"

```mermaid
flowchart LR
    subgraph TANPA ["❌ Tanpa Docker"]
        DEV["👨‍💻 Developer<br/>Python 3.12<br/>Windows 11<br/>PostgreSQL 16"]
        PROD["🖥️ Server<br/>Python 3.10<br/>Ubuntu 22<br/>PostgreSQL 15"]
        DEV -->|Deploy| PROD
        PROD -->|"❌ Error: versi beda,<br/>dependency missing,<br/>config berbeda"| FAIL["💥 CRASH"]
    end

    subgraph DENGAN ["✅ Dengan Docker"]
        DEV2["👨‍💻 Developer<br/>Docker Container"]
        PROD2["🖥️ Server<br/>Docker Container"]
        DEV2 -->|"Exact same image"| PROD2
        PROD2 --> SUCCESS["✅ WORKS"]
    end

    style TANPA fill:#FBE5D6,stroke:#C00000
    style DENGAN fill:#E2EFDA,stroke:#548235
    style FAIL fill:#FF6B6B,color:#fff
    style SUCCESS fill:#70AD47,color:#fff
```

Docker menyelesaikan masalah ini dengan cara membungkus aplikasi **beserta seluruh dependensinya** (OS, runtime, library, config) ke dalam satu paket yang disebut **container**. Container ini berjalan identik di mana saja — laptop developer, server staging, cloud production.

### 1.2 Relevansi Docker untuk Cloud Computing

Docker adalah **fondasi arsitektur cloud-native** karena:
1. **Portability** — Run anywhere: laptop, server, cloud
2. **Consistency** — Dev, staging, production identik
3. **Isolation** — Setiap service punya environment sendiri
4. **Scalability** — Mudah di-scale (jalankan banyak container)
5. **CI/CD Ready** — Build once, deploy everywhere

---

## 2. Konsep Dasar Docker (20 menit)

### 2.1 Container vs Virtual Machine

```mermaid
flowchart TD
    subgraph VM ["🖥️ Virtual Machine"]
        direction TB
        VM_HW["Hardware"]
        VM_HV["Hypervisor"]
        VM_OS1["Guest OS 1<br/>(Ubuntu)"]
        VM_OS2["Guest OS 2<br/>(CentOS)"]
        VM_APP1["App A + Libs"]
        VM_APP2["App B + Libs"]
        VM_HW --> VM_HV
        VM_HV --> VM_OS1 --> VM_APP1
        VM_HV --> VM_OS2 --> VM_APP2
    end

    subgraph DOCKER ["🐳 Docker Container"]
        direction TB
        D_HW["Hardware"]
        D_OS["Host OS (Linux/Mac/Win)"]
        D_ENGINE["Docker Engine"]
        D_C1["Container A<br/>App + Libs"]
        D_C2["Container B<br/>App + Libs"]
        D_C3["Container C<br/>App + Libs"]
        D_HW --> D_OS --> D_ENGINE
        D_ENGINE --> D_C1
        D_ENGINE --> D_C2
        D_ENGINE --> D_C3
    end

    style VM fill:#FCE4D6,stroke:#BF8F00
    style DOCKER fill:#DEEBF7,stroke:#2E75B6
```

| Aspek | Virtual Machine | Docker Container |
|-------|----------------|-----------------|
| **Boot time** | Menit | Detik |
| **Ukuran** | GB (termasuk OS) | MB (hanya app + libs) |
| **OS** | Setiap VM punya OS sendiri | Berbagi kernel host OS |
| **Isolasi** | Penuh (hardware level) | Process level |
| **Performa** | Overhead besar | Near-native |
| **Penggunaan** | Menjalankan OS berbeda | Menjalankan aplikasi |

> 💡 **Analogi:**  
> VM seperti **membangun rumah terpisah** untuk setiap penghuni (termasuk fondasi, dinding, atap) — berat dan mahal. Container seperti **apartemen** — berbagi satu gedung (kernel OS) tapi setiap unit terisolasi dan punya ruangan sendiri.

### 2.2 Terminologi Docker

```mermaid
flowchart TD
    DOCKERFILE["📄 Dockerfile<br/>(Resep / Blueprint)"]
    IMAGE["📦 Image<br/>(Template read-only)"]
    CONTAINER["🏃 Container<br/>(Instance yang berjalan)"]
    REGISTRY["☁️ Registry<br/>(Docker Hub)"]

    DOCKERFILE -->|"docker build"| IMAGE
    IMAGE -->|"docker run"| CONTAINER
    IMAGE -->|"docker push"| REGISTRY
    REGISTRY -->|"docker pull"| IMAGE

    style DOCKERFILE fill:#FFF2CC,stroke:#BF8F00
    style IMAGE fill:#DEEBF7,stroke:#2E75B6
    style CONTAINER fill:#E2EFDA,stroke:#548235
    style REGISTRY fill:#E2D9F3,stroke:#7B68A5
```

| Istilah | Penjelasan | Analogi |
|---------|------------|---------|
| **Dockerfile** | File teks berisi instruksi untuk membangun image | Resep masakan |
| **Image** | Template read-only berisi OS + app + dependencies | Cetakan kue |
| **Container** | Instance running dari image | Kue yang sudah jadi |
| **Registry** | Tempat menyimpan & berbagi images | Toko kue online |
| **Docker Hub** | Registry publik terbesar (seperti GitHub untuk images) | GitHub untuk Docker |

### 2.3 Docker Image Layers

Setiap instruksi di Dockerfile membuat **layer** baru. Layer di-cache agar build berikutnya lebih cepat.

```mermaid
flowchart BT
    L1["Layer 1: FROM python:3.12-slim<br/>(Base OS + Python)"]
    L2["Layer 2: WORKDIR /app"]
    L3["Layer 3: COPY requirements.txt<br/>+ RUN pip install"]
    L4["Layer 4: COPY . .<br/>(Application code)"]
    L5["Layer 5: CMD uvicorn..."]

    L1 --> L2 --> L3 --> L4 --> L5

    L1 -.- C1["~50 MB — Jarang berubah ✅ Cached"]
    L3 -.- C2["~30 MB — Berubah jika deps berubah"]
    L4 -.- C3["~5 MB — Sering berubah (kode)"]

    style L1 fill:#DEEBF7
    style L2 fill:#DEEBF7
    style L3 fill:#E2EFDA
    style L4 fill:#FFF2CC
    style L5 fill:#FFF2CC
    style C1 fill:#E2EFDA,stroke:#548235
    style C2 fill:#FFF2CC,stroke:#BF8F00
    style C3 fill:#FCE4D6,stroke:#BF8F00
```

> 📝 **Key Insight:** Letakkan hal yang **jarang berubah** (base image, dependencies) di atas, dan hal yang **sering berubah** (kode aplikasi) di bawah. Ini memaksimalkan cache dan mempercepat build.

---

## 3. Dockerfile Deep Dive (15 menit)

### 3.1 Instruksi Dockerfile

| Instruksi | Fungsi | Contoh |
|-----------|--------|--------|
| `FROM` | Base image | `FROM python:3.12-slim` |
| `WORKDIR` | Set working directory | `WORKDIR /app` |
| `COPY` | Copy file dari host ke image | `COPY requirements.txt .` |
| `RUN` | Jalankan command saat build | `RUN pip install -r requirements.txt` |
| `ENV` | Set environment variable | `ENV PORT=8000` |
| `EXPOSE` | Dokumentasikan port (tidak membuka port) | `EXPOSE 8000` |
| `CMD` | Command default saat container jalan | `CMD ["uvicorn", "main:app"]` |
| `ENTRYPOINT` | Command tetap yang tidak bisa di-override | `ENTRYPOINT ["python"]` |

### 3.2 Best Practices Dockerfile

```mermaid
flowchart TD
    subgraph GOOD ["✅ Best Practices"]
        G1["Gunakan image slim/alpine<br/>(ukuran kecil)"]
        G2["COPY requirements.txt DULU<br/>baru COPY kode<br/>(cache optimization)"]
        G3["Gunakan .dockerignore<br/>(exclude file tidak perlu)"]
        G4["Non-root user<br/>(keamanan)"]
        G5["Pin versi dependencies<br/>(reproducible build)"]
    end

    subgraph BAD ["❌ Hindari"]
        B1["Gunakan image :latest<br/>(tidak reproducible)"]
        B2["COPY . . di awal<br/>(cache invalidation)"]
        B3["Include node_modules, .env<br/>(image membengkak)"]
        B4["Run as root<br/>(security risk)"]
        B5["RUN apt update && apt install<br/>di banyak layer terpisah"]
    end

    style GOOD fill:#E2EFDA,stroke:#548235
    style BAD fill:#FBE5D6,stroke:#C00000
```

---

## 4. Docker Commands Cheat Sheet (5 menit)

### Build & Run

| Command | Fungsi |
|---------|--------|
| `docker build -t nama:tag .` | Bangun image dari Dockerfile |
| `docker run -p 8000:8000 nama:tag` | Jalankan container, map port |
| `docker run -d -p 8000:8000 nama:tag` | Jalankan di background (detached) |
| `docker run --env-file .env nama:tag` | Jalankan dengan env file |

### Inspect & Manage

| Command | Fungsi |
|---------|--------|
| `docker ps` | Lihat container yang berjalan |
| `docker ps -a` | Lihat semua container (termasuk stopped) |
| `docker logs <id>` | Lihat log container |
| `docker exec -it <id> bash` | Masuk ke dalam container |
| `docker stop <id>` | Hentikan container |
| `docker rm <id>` | Hapus container |

### Image & Registry

| Command | Fungsi |
|---------|--------|
| `docker images` | Lihat daftar images |
| `docker rmi <image>` | Hapus image |
| `docker tag image:tag user/repo:tag` | Beri tag untuk push |
| `docker push user/repo:tag` | Push ke Docker Hub |
| `docker pull user/repo:tag` | Pull dari Docker Hub |

---

## 5. Rangkuman Teori

```mermaid
mindmap
  root((🐳 DOCKER<br/>FUNDAMENTALS))
    Mengapa Docker?
      Works on my machine → solved
      Portable, consistent, isolated
      Fondasi cloud-native
    Konsep
      Dockerfile = Resep
      Image = Template
      Container = Instance
      Registry = Toko image
    Layers
      Setiap instruksi = 1 layer
      Cache optimization penting
      Deps dulu, kode terakhir
    Best Practices
      Slim/alpine base image
      .dockerignore
      Non-root user
      Pin versions
    Commands
      build, run, ps, logs
      exec, stop, rm
      push, pull, tag
```

---

# BAGIAN B: WORKSHOP DI LAB (170 Menit)


> ⚠️ **Pastikan Docker Desktop running!** Cek ikon di system tray. Jika belum running, buka Docker Desktop dan tunggu sampai status "Running".

---

## Workshop 5.1: Verifikasi Docker & Docker Hub Setup (20 menit)

### Langkah 1: Verifikasi Docker

```bash
# Cek Docker terinstall
docker --version
docker compose version

# Test: jalankan container hello-world
docker run hello-world
```

Jika muncul pesan "Hello from Docker!", Docker berfungsi.

### Langkah 2: Buat Akun Docker Hub

1. Buka https://hub.docker.com/ dan buat akun (gratis)
2. Login dari terminal:

```bash
docker login
# Masukkan username dan password Docker Hub
```

> ✅ **Checkpoint:** `docker run hello-world` berhasil dan `docker login` berhasil.

### Troubleshooting Umum

| Problem | Solusi |
|---------|--------|
| Docker daemon not running | Buka Docker Desktop, tunggu sampai "Running" |
| Permission denied (Linux) | Tambahkan user ke docker group: `sudo usermod -aG docker $USER` lalu restart terminal |
| WSL error (Windows) | Pastikan WSL 2 terinstall: `wsl --install` di PowerShell sebagai admin |
| Disk space | Docker butuh minimal 5 GB free space |

---

## Workshop 5.2: Dockerfile untuk Backend (30 menit)

### Flowchart Build Process

```mermaid
flowchart TD
    START([🟢 docker build]) --> FROM["FROM python:3.12-slim<br/>Download base image"]
    FROM --> WORKDIR["WORKDIR /app<br/>Set working directory"]
    WORKDIR --> COPY_REQ["COPY requirements.txt .<br/>Copy file dependencies"]
    COPY_REQ --> INSTALL["RUN pip install<br/>Install Python packages"]
    INSTALL --> COPY_CODE["COPY . .<br/>Copy application code"]
    COPY_CODE --> EXPOSE["EXPOSE 8000<br/>Document port"]
    EXPOSE --> CMD["CMD uvicorn...<br/>Set startup command"]
    CMD --> IMAGE([📦 Docker Image Ready])

    COPY_REQ -.- NOTE1["⚡ Layer 3: Di-cache jika<br/>requirements.txt tidak berubah"]
    COPY_CODE -.- NOTE2["🔄 Layer 5: Rebuild setiap<br/>kali kode berubah"]

    style START fill:#70AD47,color:#fff
    style IMAGE fill:#2E75B6,color:#fff
    style NOTE1 fill:#E2EFDA,stroke:#548235
    style NOTE2 fill:#FFF2CC,stroke:#BF8F00
```

### Langkah 1: Buat .dockerignore

File: `backend/.dockerignore`
```
# Virtual environment
venv/
.venv/

# Environment files (secrets!)
.env

# Python cache
__pycache__/
*.pyc
*.pyo

# IDE
.vscode/
.idea/

# Git
.git/
.gitignore

# Docker
Dockerfile
.dockerignore

# Testing
tests/
*.test.py

# Documentation
*.md
docs/
```

> 💡 `.dockerignore` bekerja seperti `.gitignore` — mencegah file yang tidak perlu masuk ke image. Ini penting untuk:
> - **Keamanan**: `.env` tidak boleh ada di image
> - **Ukuran**: `__pycache__`, `.git` tidak diperlukan
> - **Build speed**: File lebih sedikit = build lebih cepat

### Langkah 2: Buat Dockerfile

File: `backend/Dockerfile`
```dockerfile
# ============================================================
# Stage: Production Image
# ============================================================

# 1. Base image — Python 3.12 slim (debian minimal, ~50MB)
FROM python:3.12-slim

# 2. Set working directory di dalam container
WORKDIR /app

# 3. Copy HANYA requirements.txt dulu (cache optimization)
#    Layer ini hanya rebuild jika requirements.txt berubah
COPY requirements.txt .

# 4. Install Python dependencies
#    --no-cache-dir: jangan simpan pip cache (hemat space)
RUN pip install --no-cache-dir -r requirements.txt

# 5. Copy seluruh kode aplikasi
#    Layer ini rebuild setiap kali kode berubah
COPY . .

# 6. Dokumentasikan port yang digunakan
EXPOSE 8000

# 7. Command untuk menjalankan aplikasi
#    --host 0.0.0.0: agar bisa diakses dari luar container
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]
```

### Langkah 3: Build Image

```bash
cd backend

# Build image dengan tag
docker build -t cloudapp-backend:v1 .

# Lihat hasil build
docker images | grep cloudapp
```

> 📝 **Penjelasan flag:**
> - `-t cloudapp-backend:v1`: Beri nama (tag) image
> - `.`: Build context = direktori saat ini

**Perhatikan output build** — setiap langkah di Dockerfile ditampilkan:
```
Step 1/7 : FROM python:3.12-slim
Step 2/7 : WORKDIR /app
Step 3/7 : COPY requirements.txt .
Step 4/7 : RUN pip install ...
Step 5/7 : COPY . .
Step 6/7 : EXPOSE 8000
Step 7/7 : CMD ["uvicorn", ...]
```

> ✅ **Checkpoint:** `docker images` menampilkan `cloudapp-backend:v1`.

---

## Workshop 5.3: Jalankan Container (30 menit)

### Flowchart Run Process

```mermaid
flowchart TD
    IMAGE["📦 cloudapp-backend:v1"] -->|"docker run"| CONTAINER["🏃 Container"]
    
    CONTAINER --> PORT["Port mapping<br/>-p 8000:8000<br/>Host:Container"]
    CONTAINER --> ENV["Environment<br/>--env-file .env<br/>Database URL, secrets"]
    CONTAINER --> DETACH["Mode<br/>-d = background<br/>tanpa -d = foreground"]

    PORT --> ACCESS["🌐 http://localhost:8000"]
    ENV --> DB["🗄️ PostgreSQL<br/>(di host machine)"]

    style IMAGE fill:#DEEBF7,stroke:#2E75B6
    style CONTAINER fill:#E2EFDA,stroke:#548235
    style ACCESS fill:#FFF2CC,stroke:#BF8F00
```

### Langkah 1: Jalankan Container

```bash
# Jalankan container di foreground (melihat log langsung)
docker run -p 8000:8000 --env-file .env cloudapp-backend:v1
```

> ⚠️ **Kemungkinan error: Database connection refused!**
> 
> Ini normal. Container tidak bisa mengakses `localhost` host machine secara langsung. `localhost` di dalam container merujuk ke container itu sendiri, bukan host.

### Langkah 2: Fix Database URL untuk Docker

Container perlu mengakses PostgreSQL di host machine. Update `backend/.env`:

```bash
# Untuk Mac/Windows (Docker Desktop)
DATABASE_URL=postgresql://postgres:PASSWORD_ANDA@host.docker.internal:5432/cloudapp

# Untuk Linux
DATABASE_URL=postgresql://postgres:PASSWORD_ANDA@172.17.0.1:5432/cloudapp
```

> 💡 **`host.docker.internal`** adalah DNS spesial Docker yang merujuk ke host machine. Ini cara container mengakses service yang berjalan di host.

### Langkah 3: Jalankan Ulang

```bash
# Stop container sebelumnya (Ctrl+C jika foreground)

# Jalankan ulang dengan env yang sudah difix
docker run -p 8000:8000 --env-file .env cloudapp-backend:v1
```

### Langkah 4: Test API

Buka browser: http://localhost:8000/docs

Test beberapa endpoint:
- `GET /health` → harus return `{"status": "healthy"}`
- `POST /auth/register` → buat user baru
- `POST /auth/login` → login

### Langkah 5: Jalankan di Background

```bash
# Stop foreground container (Ctrl+C)

# Jalankan di background (detached mode)
docker run -d -p 8000:8000 --env-file .env --name backend cloudapp-backend:v1

# Lihat container yang berjalan
docker ps

# Lihat logs
docker logs backend

# Ikuti logs secara real-time
docker logs -f backend
```

### Langkah 6: Explore Container

```bash
# Masuk ke dalam container (seperti SSH)
docker exec -it backend bash

# Di dalam container:
ls -la            # Lihat file
python --version  # Cek Python
pip list          # Lihat installed packages
cat requirements.txt
env               # Lihat environment variables

# Keluar dari container
exit
```

> ✅ **Checkpoint:** API berjalan di dalam Docker container, bisa diakses di http://localhost:8000/docs, dan bisa login/register.

---

## Workshop 5.4: Layer Caching Demo (15 menit)

### Demonstrasi Cache

```bash
# Build pertama (cold build) — perhatikan waktu
time docker build -t cloudapp-backend:v1 .

# Ubah sesuatu di main.py (misalnya ubah version ke "0.5.0")
# Lalu build ulang
time docker build -t cloudapp-backend:v1 .
```

**Perhatikan output build kedua:**
```
Step 1/7 : FROM python:3.12-slim
 ---> Using cache                    ✅ Cached!
Step 2/7 : WORKDIR /app
 ---> Using cache                    ✅ Cached!
Step 3/7 : COPY requirements.txt .
 ---> Using cache                    ✅ Cached!
Step 4/7 : RUN pip install ...
 ---> Using cache                    ✅ Cached! (deps tidak berubah)
Step 5/7 : COPY . .
 ---> NOT using cache                🔄 Rebuild (kode berubah)
Step 6/7 : EXPOSE 8000
Step 7/7 : CMD ["uvicorn", ...]
```

> 📝 **Perhatikan:** Step 1-4 menggunakan cache karena `requirements.txt` tidak berubah. Hanya step 5+ yang rebuild karena kode berubah. Inilah kenapa kita COPY `requirements.txt` terpisah sebelum COPY seluruh kode.

### Anti-Pattern: Apa yang Terjadi Jika COPY . . di Awal?

```dockerfile
# ❌ BAD - Jangan lakukan ini!
COPY . .                        # Setiap perubahan kode = invalidate SEMUA layer
RUN pip install -r requirements.txt  # Harus reinstall deps setiap kali!
```

Setiap perubahan kode sekecil apapun akan membuat pip install ulang — build jadi sangat lambat.

---

## Workshop 5.5: Push ke Docker Hub (25 menit)

### Flowchart Push to Registry

```mermaid
flowchart LR
    LOCAL["📦 Local Image<br/>cloudapp-backend:v1"]
    TAG["🏷️ Tag Image<br/>username/cloudapp-backend:v1"]
    PUSH["☁️ Docker Hub<br/>username/cloudapp-backend:v1"]

    LOCAL -->|"docker tag"| TAG
    TAG -->|"docker push"| PUSH

    style LOCAL fill:#DEEBF7,stroke:#2E75B6
    style TAG fill:#FFF2CC,stroke:#BF8F00
    style PUSH fill:#E2D9F3,stroke:#7B68A5
```

### Langkah 1: Tag Image

```bash
# Format: docker tag LOCAL_IMAGE DOCKERHUB_USERNAME/REPO:TAG
docker tag cloudapp-backend:v1 USERNAME/cloudapp-backend:v1

# Contoh:
docker tag cloudapp-backend:v1 aidilkirsan/cloudapp-backend:v1
```

### Langkah 2: Push ke Docker Hub

```bash
# Pastikan sudah login
docker login

# Push image
docker push USERNAME/cloudapp-backend:v1
```

### Langkah 3: Verifikasi

1. Buka https://hub.docker.com/ → login → lihat repository
2. Image `cloudapp-backend:v1` harus muncul

```bash
# Test: pull image dari Docker Hub (seolah-olah di komputer lain)
docker rmi USERNAME/cloudapp-backend:v1  # Hapus local dulu
docker pull USERNAME/cloudapp-backend:v1 # Pull dari Docker Hub
docker run -p 8000:8000 --env-file .env USERNAME/cloudapp-backend:v1
```

> ✅ **Checkpoint:** Image ada di Docker Hub dan bisa di-pull & run oleh siapa saja.

---

## Workshop 5.6: Inspect & Clean Up (10 menit)

### Useful Commands

```bash
# Lihat detail image
docker inspect cloudapp-backend:v1

# Lihat ukuran image
docker images cloudapp-backend

# Lihat layer history
docker history cloudapp-backend:v1

# Stop & remove semua container
docker stop $(docker ps -q)
docker rm $(docker ps -aq)

# Hapus dangling images (images tanpa tag)
docker image prune

# Nuclear option: hapus SEMUA yang tidak dipakai
docker system prune -a
# ⚠️ Hati-hati! Ini menghapus semua images, containers, networks yang tidak dipakai
```

---

## Workshop 5.7: Commit & Push (20 menit)

### Struktur File Baru

```
cloud-team-XX/
├── backend/
│   ├── Dockerfile           ← BARU
│   ├── .dockerignore        ← BARU
│   ├── main.py
│   ├── auth.py
│   ├── database.py
│   ├── models.py
│   ├── schemas.py
│   ├── crud.py
│   ├── requirements.txt
│   ├── .env                 ← Updated (host.docker.internal)
│   └── .env.example         ← Updated
├── frontend/
│   └── ...
└── ...
```

### Commit

```bash
cd cloud-team-XX

# Lead DevOps commit
git add backend/Dockerfile backend/.dockerignore
git commit -m "feat: add Docker support for backend

- Add Dockerfile: Python 3.12-slim, layer-optimized build
- Add .dockerignore: exclude .env, __pycache__, .git
- Backend runs in container on port 8000
- Image pushed to Docker Hub: USERNAME/cloudapp-backend:v1"

git push origin main

# Lead QA update README
git add README.md
git commit -m "docs: add Docker section to README

- Add Docker build & run instructions
- Add Docker Hub image reference
- Update Getting Started with container option"

git push origin main
```

---

# BAGIAN C: TUGAS TERSTRUKTUR (60 Menit)

> 📝 **Kumpulkan sebelum pertemuan 6** via push ke repository tim.

---

## Tugas 5: Optimasi Image & Dokumentasi Docker

### Pembagian Tugas

| Anggota | Tugas | Detail |
|---------|-------|--------|
| **Lead DevOps** | Optimasi Dockerfile: tambahkan **non-root user** | Tambahkan `RUN useradd -m appuser` dan `USER appuser` di Dockerfile. Verifikasi app masih berjalan. |
| **Lead Backend** | Buat **healthcheck** di Dockerfile | Tambahkan `HEALTHCHECK CMD curl -f http://localhost:8000/health \|\| exit 1`. Test dengan `docker inspect --format='{{.State.Health.Status}}' backend` |
| **Lead Frontend** | Riset & tulis `docs/docker-cheatsheet.md` | Buat cheatsheet Docker commands yang sering dipakai (build, run, ps, logs, exec, stop, rm, push, pull). Sertakan contoh spesifik untuk proyek ini. |
| **Lead QA & Docs** | Bandingkan ukuran image | Catat ukuran `python:3.12` vs `python:3.12-slim` vs `python:3.12-alpine`. Tulis hasilnya di `docs/image-comparison.md`. |
| **Lead CI/CD** *(5 orang)* | Buat `Makefile` atau `scripts/docker.sh` | Script untuk mempermudah: `make build`, `make run`, `make push`, `make clean`. |

### Contoh: Non-root User di Dockerfile

```dockerfile
FROM python:3.12-slim

WORKDIR /app

COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

COPY . .

# Buat non-root user
RUN useradd -m appuser
USER appuser

EXPOSE 8000
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8000"]
```

### Contoh: Healthcheck

```dockerfile
# Tambahkan sebelum CMD
RUN apt-get update && apt-get install -y curl && rm -rf /var/lib/apt/lists/*
HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
    CMD curl -f http://localhost:8000/health || exit 1
```

### Informasi Pengumpulan

| Item | Keterangan |
|------|------------|
| **Deadline** | Sebelum pertemuan 6 dimulai |
| **Format** | Push ke repository tim |
| **Penilaian** | Dockerfile optimized, healthcheck works, docs lengkap, semua anggota ≥1 commit |

---

# BAGIAN D: BELAJAR MANDIRI (230 Menit)

---

## D1. Membaca Referensi (60 menit)

### Bacaan Wajib
1. **Docker Get Started — Part 2: Containerize an Application**  
   https://docs.docker.com/get-started/workshop/02_our_app/

2. **Dockerfile Best Practices**  
   https://docs.docker.com/build/building/best-practices/

3. **Docker Multi-stage Builds** (untuk minggu depan)  
   https://docs.docker.com/build/building/multi-stage/

### Bacaan Tambahan
- Docker Cheat Sheet — https://docs.docker.com/get-started/docker_cheatsheet.pdf
- Docker Security Best Practices — https://cheatsheetseries.owasp.org/cheatsheets/Docker_Security_Cheat_Sheet.html

---

## D2. Video Tutorial (60 menit)

1. **"Docker Tutorial for Beginners"** — TechWorld with Nana (YouTube, ~30 min pertama)
2. **"Docker in 100 Seconds"** — Fireship (YouTube, ~2 min)
3. **"Dockerize a Python App"** — cari di YouTube (~15 min)
4. **"Docker Multi-stage Builds"** — cari di YouTube (~10 min, persiapan minggu depan)

---

## D3. Latihan Mandiri (60 menit)

### Soal Pilihan Ganda

**1.** Perbedaan utama Docker container dengan Virtual Machine adalah:
- [ ] a. Container lebih mahal
- [ ] b. Container berbagi kernel host OS, VM punya OS sendiri
- [ ] c. Container tidak bisa menjalankan aplikasi
- [ ] d. VM lebih ringan dari container

**2.** Instruksi Dockerfile yang menentukan command saat container dijalankan adalah:
- [ ] a. `RUN`
- [ ] b. `COPY`
- [ ] c. `CMD`
- [ ] d. `FROM`

**3.** Mengapa kita COPY `requirements.txt` sebelum COPY seluruh kode?
- [ ] a. Karena Python membutuhkannya
- [ ] b. Untuk mengoptimalkan Docker layer caching — dependencies jarang berubah
- [ ] c. Karena Dockerfile mengharuskannya
- [ ] d. Tidak ada alasan khusus

**4.** Apa fungsi `.dockerignore`?
- [ ] a. Menghentikan Docker daemon
- [ ] b. Mencegah file tertentu masuk ke Docker image
- [ ] c. Mengabaikan error saat build
- [ ] d. Menghapus file dari container

**5.** `docker run -p 8000:8000` artinya:
- [ ] a. Jalankan 8000 container
- [ ] b. Map port 8000 di host ke port 8000 di container
- [ ] c. Buka 8000 koneksi
- [ ] d. Tunggu 8000 detik sebelum start

---

## D4. Persiapan Pertemuan Berikutnya (50 menit)

Pertemuan 6 akan membahas **Docker lanjutan: multi-stage build, volumes, dan networks**. Persiapkan:

- Apa itu **multi-stage build** dan kenapa bisa mengurangi ukuran image?
- Apa itu **Docker volume** dan mengapa data di container bisa hilang?
- Apa itu **Docker network** dan bagaimana container berkomunikasi?
- Baca: https://docs.docker.com/build/building/multi-stage/
- Baca: https://docs.docker.com/engine/storage/volumes/
- **Siapkan Dockerfile untuk frontend** (React) — kita akan build frontend image minggu depan

> 💡 **Tip:** Coba buat `frontend/Dockerfile` sendiri sebelum pertemuan 6. Hint: React perlu **build step** (`npm run build`) yang menghasilkan file statis, lalu file statis itu di-serve oleh **nginx**. Ini adalah use case sempurna untuk multi-stage build.

---

---

*Modul ini disusun oleh Aidil Saputra Kirsan, Institut Teknologi Kalimantan.*
