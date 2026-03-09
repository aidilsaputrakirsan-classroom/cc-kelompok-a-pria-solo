# Modul Praktikum Komputasi Awan

**Mata Kuliah:** Komputasi Awan | **Prodi:** Sistem Informasi - Institut Teknologi Kalimantan
**Metode:** Project-Based Learning (PjBL) | **Tim:** 4-5 mahasiswa

---

## RPS & Silabus

| Sumber | Link |
|--------|------|
| RPS Online (Kurikulum ITK) | [https://kurikulum.itk.ac.id/guest/rps/2827?code=RPS-ICK9R-YOKXN](https://kurikulum.itk.ac.id/guest/rps/2827?code=RPS-ICK9R-YOKXN) |
| RPS PDF (lokal) | [`Komputasi Awan_SI2514027_RPS.pdf`](Komputasi%20Awan_SI2514027_RPS.pdf) |

---

## Struktur Modul

| Minggu | Modul | Topik |
|--------|-------|-------|
| 1 | [01-modul.md](01-modul.md) | Cloud Computing Fundamentals & Environment Setup |
| 2 | [02-modul.md](02-modul.md) | Backend REST API dengan FastAPI & PostgreSQL |
| 3 | [03-modul.md](03-modul.md) | Frontend dengan React & Integrasi API |
| 4 | [04-modul.md](04-modul.md) | Full-Stack Integration, CORS, JWT Authentication |
| 5 | [05-modul.md](05-modul.md) | Docker Fundamentals — Dockerfile, Image, Container |
| 6 | [06-modul.md](06-modul.md) | Docker Compose — Multi-Container Orchestration |
| 7 | [07-modul.md](07-modul.md) | Container Deployment & Registry |
| **8** | — | **UTS / Demo Milestone 1** |
| 9 | [09-modul.md](09-modul.md) | GitHub Actions & CI/CD Fundamentals |
| 10 | [10-modul.md](10-modul.md) | CI/CD Pipeline & Automated Testing |
| 11 | [11-modul.md](11-modul.md) | Cloud Deployment (Railway/Render) |
| 12 | [12-modul.md](12-modul.md) | Microservices Architecture & API Gateway |
| 13 | [13-modul.md](13-modul.md) | Advanced Microservices Patterns |
| 14 | [14-modul.md](14-modul.md) | Monitoring, Logging & Observability |
| 15–16 | [15-modul.md](15-modul.md) | Security Hardening, Dokumentasi & **UAS** |

---

## Alur Belajar

```
[Minggu 1-4]          [Minggu 5-7]        [Minggu 8]    [Minggu 9-11]      [Minggu 12-14]     [Minggu 15-16]
Full-Stack App   -->  Containerization --> UTS/Demo --> CI/CD & Deploy --> Microservices --> UAS & Docs
FastAPI + React       Docker & Compose                 GitHub Actions      Monitoring
```

---

## Tech Stack

- **Backend:** Python 3.10+, FastAPI, SQLAlchemy, PostgreSQL
- **Frontend:** React + Vite
- **Container:** Docker, Docker Compose
- **CI/CD:** GitHub Actions
- **Deploy:** Railway / Render
- **Proxy:** Nginx (API Gateway)

---

## Format Setiap Modul

Setiap modul terdiri dari 4 sesi:

| Sesi | Durasi | Keterangan |
|------|--------|------------|
| **A. Pembekalan Teori** | 50 menit | Konsep, diagram, analogi |
| **B. Workshop di Lab** | 170 menit | Praktik step-by-step dengan checkpoint |
| **C. Tugas Terstruktur** | 60 menit | Tugas tim dengan pembagian peran |
| **D. Belajar Mandiri** | 230 menit | Bacaan, video, latihan soal |

---

## Pembagian Peran Tim

**Tim 4 orang:**
- **Lead Backend** — REST API, database, autentikasi
- **Lead Frontend** — React UI, state management, routing
- **Lead DevOps** — Docker, Docker Compose, CI/CD
- **Lead QA & Docs** — Testing, monitoring, dokumentasi

**Tim 5 orang** (DevOps dibagi dua):
- **Lead Container** — Docker, Dockerfile, Docker Compose
- **Lead CI/CD & Deploy** — GitHub Actions, cloud deployment

---

## Penilaian

| Komponen | Waktu | Keterangan |
|----------|-------|------------|
| UTS / Demo Milestone 1 | Minggu 8 | Full-stack app + Docker berjalan |
| UAS / Demo Final | Minggu 15-16 | Sistem lengkap + dokumentasi + presentasi |
