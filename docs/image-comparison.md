# Perbandingan Ukuran Image (Modul 6)

Dokumen ini dipakai untuk tugas terstruktur Modul 6 (Lead CI/CD): mencatat ukuran image sebelum vs sesudah optimasi multi-stage.

## 1) Cara Uji

```bash
# Backend - sebelum optimasi (single-stage)
docker build -t pria-solo-backend:before ./backend

# Backend - sesudah optimasi (multi-stage)
docker build -t pria-solo-backend:v2 ./backend

# Frontend Laravel container image
docker build -t pria-solo-frontend:v1 ./frontend

docker images --format "table {{.Repository}}:{{.Tag}}\t{{.Size}}" | grep -E "pria-solo-backend|pria-solo-frontend"
```

## 2) Hasil Ukuran (Evidence Lokal)

| Image | Sebelum Optimasi | Sesudah Optimasi | Target Modul |
|---|---:|---:|---:|
| `pria-solo-backend` | `N/A (tag before belum tersedia di mesin ini)` | `1.12 GB (tag: pria-solo-backend:v2)` | `< 150 MB` |
| `pria-solo-frontend` | `15.4 GB (build pertama)` | `7.59 GB (tag: pria-solo-frontend:v1)` | `adaptasi Laravel (tanpa target < 50 MB)` |

Evidence command output (2026-04-01):

```bash
# Build pertama (sebelum optimasi .dockerignore)
$ docker images pria-solo-frontend:v1
pria-solo-frontend:v1	15.4GB

# Setelah optimasi Dockerfile + .dockerignore
$ docker images --format "{{.Repository}}:{{.Tag}} {{.Size}}" | grep pria-solo
pria-solo-frontend:v1	7.59GB
pria-solo-backend:v2	1.12GB
```

**Pengurangan ukuran frontend: 15.4 GB → 7.59 GB (50.7% reduction)**

## 3) Analisis

### Backend (1.12 GB)
- Memakai multi-stage: dependency di stage builder (venv), runtime stage hanya venv + source.
- Pada codebase ini, hasil build backend masih `1.12 GB` karena dependency AI/ML cukup berat (`numpy`, `scipy`, `pandas`, OCR/vision stack, dll).

### Frontend (7.59 GB - setelah optimasi)
- Multi-stage build: Composer 2.7 builder + PHP 8.2-cli-alpine runtime
- **Optimasi yang diterapkan:**
  - Enhanced `.dockerignore` untuk exclude vendor, node_modules, tests, docs
  - Cleanup Composer cache di builder stage (`rm -rf /root/.composer/cache`)
  - Hapus file tidak perlu di runtime (tests, *.md, .git*, IDE configs)
  - Clear storage files yang tidak diperlukan
- **Hasil:** Pengurangan dari 15.4 GB → 7.59 GB (50.7% reduction)
- **Ukuran masih besar karena:**
  - Laravel/OpenAdmin vendor dependencies yang ekstensif (~6-7 GB)
  - PHP extensions (GD, PDO, MySQL)
  - Application code dan assets
- **Potensi optimasi lanjutan:**
  - Gunakan PHP FPM alpine yang lebih kecil
  - Pisahkan vendor ke layer terpisah untuk caching
  - Compress vendor dengan opcache
  - Gunakan production-only dependencies

## 4) Publish ke Docker Hub

```bash
docker tag pria-solo-backend:v2 USERNAME/pria-solo-backend:v2
docker push USERNAME/pria-solo-backend:v2

docker tag pria-solo-frontend:v1 USERNAME/pria-solo-frontend:v1
docker push USERNAME/pria-solo-frontend:v1
```

Catat URL repository Docker Hub pada laporan akhir tugas.
