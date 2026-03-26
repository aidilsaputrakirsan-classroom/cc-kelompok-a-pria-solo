# Perbandingan Image Python untuk Backend Docker

Dokumen ini untuk Tugas 5 (Lead QA & Docs), membandingkan base image:
- `python:3.12`
- `python:3.12-slim`
- `python:3.12-alpine`

## Cara Uji

```bash
docker pull python:3.12
docker pull python:3.12-slim
docker pull python:3.12-alpine

docker images python --format "table {{.Repository}}:{{.Tag}}\t{{.Size}}"
```

## Hasil (Isi saat praktikum)

| Image | Ukuran |
|------|--------|
| `python:3.12` | `(isi hasil docker images)` |
| `python:3.12-slim` | `(isi hasil docker images)` |
| `python:3.12-alpine` | `(isi hasil docker images)` |

## Analisis untuk Proyek `pria-solo`

- `python:3.12` paling besar, tetapi kompatibilitas paket biasanya paling mulus.
- `python:3.12-slim` umumnya kompromi terbaik: ukuran lebih kecil dengan kompatibilitas dependency Python yang tetap baik.
- `python:3.12-alpine` biasanya paling kecil, namun beberapa paket scientific/AI dapat memerlukan penyesuaian tambahan.

## Keputusan

Untuk backend FastAPI `pria-solo` (dependency cukup berat seperti `pandas`, `scipy`, `PyMuPDF`), base image yang dipakai adalah:

**`python:3.12-slim`**

Alasan:
- ukuran lebih efisien dibanding full image;
- stabil untuk build dependency Python proyek ini;
- sesuai best practice Modul 5.
