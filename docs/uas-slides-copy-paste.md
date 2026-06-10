# UAS Google Slides — Copy-Paste Text

**Proyek:** AI Document Validator — PRIA Solo  
**Presenter:** Dyno Fadillah Ramadhani (10231033)  
**Durasi:** ~12 menit presentasi + 8 menit Q&A

> Cara pakai: buat slide baru di Google Slides → salin **Judul slide** ke title box → salin **Isi slide** ke body/text box. Tambahkan screenshot/diagram sesuai catatan di setiap slide.

---

## Slide 1 — Judul

**Durasi bicara:** ~30 detik

### Judul slide

```
AI Document Validator — PRIA Solo
```

### Subjudul (opsional)

```
Komputasi Awan · Sistem Informasi · Institut Teknologi Kalimantan
```

### Isi slide

```
Dyno Fadillah Ramadhani
NIM: 10231033

Kelompok A — PRIA Solo
Mata Kuliah Komputasi Awan (3 SKS)

Proyek magang — Telkom Regional IV Kalimantan Timur
UAS · Milestone 3 · v3.0.0
```

### Catatan visual

- Tambah logo ITK atau screenshot dashboard (blur data sensitif jika perlu).

### Speaker notes (tidak perlu di slide)

> Perkenalkan nama, NIM, nama proyek, dan konteks magang. Sebutkan ini aplikasi cloud-native untuk validasi dokumen berbasis AI.

---

## Slide 2 — Problem & Solution

**Durasi bicara:** ~1 menit

### Judul slide

```
Problem & Solution
```

### Isi slide

```
MASALAH
• Review dokumen kontrak/NPK manual memakan waktu lama
• Human error pada validasi terbilang, tanggal, dan typo
• Konsistensi antar dokumen sulit diskalakan ke banyak tiket

TARGET PENGGUNA
• Tim pre-sales dan project management (lingkungan Telkom)

SOLUSI — PRIA Solo
• Upload PDF → OCR (Azure Document Intelligence) → AI Review (LangChain + OpenAI)
• Admin panel Laravel Open Admin untuk workflow upload, review, dan catatan
• Arsitektur cloud-native: container, CI/CD, gateway, monitoring
```

### Catatan visual

- Kolom kiri: icon ❌ manual / lambat. Kolom kanan: screenshot halaman upload atau hasil review.

---

## Slide 3 — Architecture Journey

**Durasi bicara:** ~2 menit

### Judul slide

```
Architecture Journey — Monolith ke Cloud-Native
```

### Isi slide

```
PERJALANAN ARSITEKTUR

Minggu 1–4   → Full-stack: Laravel + FastAPI + MySQL
Minggu 5–7   → Docker Compose (containerization)
Minggu 8     → UTS Demo (Milestone 1)
Minggu 9–11  → GitHub Actions + deploy Railway (PaaS)
Minggu 12–14 → Nginx Gateway + document-service + observability
Minggu 15–16 → Security hardening + dokumentasi UAS

ARSITEKTUR FINAL

Admin (Browser)
    ↓
Nginx API Gateway :8080
    ├── Laravel + Open Admin :8000  (auth session, UI, jobs)
    └── FastAPI Document Service :8001  (OCR, ekstraksi, AI review)
Laravel → MySQL 8
Laravel → FastAPI (HTTP + retry + circuit breaker)
```

### Catatan visual

- Gambar diagram Mermaid dari README atau draw.io: Browser → Gateway → Laravel / FastAPI → MySQL.
- Tabel timeline bisa dibuat sebagai tabel Google Slides (copy baris di atas).

---

## Slide 4 — Tech Stack & Infrastructure

**Durasi bicara:** ~2 menit

### Judul slide

```
Tech Stack & Infrastructure
```

### Isi slide

```
APLIKASI
• Frontend: Laravel 8, Open Admin, Blade, Guzzle HTTP
• Backend: FastAPI, Pydantic, Azure Document Intelligence
• AI: LangChain + OpenAI (advance review & validasi)
• Database: MySQL 8 (data aplikasi & admin)

INFRASTRUKTUR & DEVOPS
• Docker Compose — 4 container: gateway, frontend, document-service, db
• API Gateway: Nginx (routing, rate limiting, timeout upload besar)
• CI/CD: GitHub Actions → lint, pytest, phpunit, build, deploy Railway
• Cloud: Railway (PaaS) — frontend + backend production

OBSERVABILITY (Modul 14)
• Structured JSON logging + correlation ID
• Endpoint /metrics per service
• Dashboard /status (health + metrics real-time)

SECURITY (Modul 15)
• Rate limiting: 5 req/s login, 20 req/s API, 30 req/s umum
• Secret via environment variable (tidak di Git)
• CORS whitelist + validasi input Pydantic
```

### Catatan visual

- Screenshot GitHub Actions pipeline hijau atau badge CI dari README.
- Logo Docker, GitHub, Railway (opsional).

---

## Slide 5 — Live Demo

**Durasi bicara:** ~3 menit (demo live, slide = cheat sheet)

### Judul slide

```
Live Demo
```

### Isi slide

```
ALUR DEMO (±3 menit)

1. Buka aplikasi production
   https://cc-kelompok-a-pria-solo-production.up.railway.app

2. Login Open Admin
   /projess/auth/login

3. Upload dokumen PDF (Advance Review)

4. Jalankan Information Extraction (OCR)

5. Tampilkan hasil Review / Validasi AI

6. Buka halaman Status
   /status — health + metrics semua service

7. Tunjukkan CI/CD hijau di GitHub Actions

BACKUP
• Video demo ~3 menit (Google Drive) jika WiFi bermasalah
```

### URL cepat (untuk tab browser, tidak wajib di slide)

```
Frontend:    https://cc-kelompok-a-pria-solo-production.up.railway.app
Login:       .../projess/auth/login
Status:      .../status
Backend:     https://backend-production-bdd8.up.railway.app/health
Swagger:     https://backend-production-bdd8.up.railway.app/docs
GitHub CI:   github.com/aidilsaputrakirsan-classroom/cc-kelompok-a-pria-solo/actions
```

### Catatan visual

- Slide ini sengaja ringkas — fokus ke layar laptop saat demo.
- Siapkan PDF kecil (1–2 halaman) agar OCR tidak terlalu lama.

---

## Slide 6 — Challenges & Lessons Learned

**Durasi bicara:** ~2 menit

### Judul slide

```
Challenges & Lessons Learned
```

### Isi slide

```
TANTANGAN & SOLUSI

Integrasi Laravel ↔ FastAPI (file besar, timeout)
→ Gateway timeout 600s, Laravel job queue, URL_VM_PYTHON via gateway

Latency & biaya OCR/AI (Azure + OpenAI)
→ Circuit breaker + exponential retry di DocumentServiceClient

Ukuran image Docker frontend besar (~7.5 GB)
→ Dokumentasi ukuran image; roadmap optimasi multi-stage build

Model auth berbeda dari template kuliah (JWT)
→ Session Open Admin + pemanggilan FastAPI dari server internal (trusted)

PELAJARAN UTAMA
Cloud-native bukan sekadar "hosting di cloud" —
observability, rate limiting, dan secret management
sama pentingnya dengan fitur aplikasi.
```

### Catatan visual

- Tabel 2 kolom (Challenge | Solution) jika lebih rapi di Slides.

---

## Slide 7 — Team Contributions

**Durasi bicara:** ~1 menit

### Judul slide

```
Team Contributions
```

### Isi slide

```
TIM — Kelompok A PRIA Solo

Dyno Fadillah Ramadhani · NIM 10231033

Peran (solo — seluruh peran tim diampu satu anggota):

• Lead Backend
  FastAPI, OCR, AI review, Pydantic validation, circuit breaker

• Lead Frontend
  Laravel Open Admin, UI workflow, Blade, integrasi API

• Lead DevOps
  Docker Compose, Nginx gateway, Railway deploy, rate limiting

• Lead QA & Docs
  pytest, phpunit, CI/CD, README, api-contract, release notes

Repository: github.com/aidilsaputrakirsan-classroom/cc-kelompok-a-pria-solo
~45 commits · Release v3.0.0

Terima kasih — siap Q&A
```

### Catatan visual

- Foto profil atau avatar opsional.
- QR code ke repo GitHub (opsional).

---

## Bonus — Slide penutup (opsional, slide 8)

Jika ingin slide "Thank you" terpisah:

### Judul slide

```
Terima Kasih
```

### Isi slide

```
AI Document Validator — PRIA Solo
Dyno Fadillah Ramadhani (10231033)

Pertanyaan?
```

---

## Ringkasan timing

| Slide | Topik              | Waktu  |
|-------|--------------------|--------|
| 1     | Judul              | 0:30   |
| 2     | Problem & Solution | 1:00   |
| 3     | Architecture       | 2:00   |
| 4     | Tech Stack         | 2:00   |
| 5     | Live Demo          | 3:00   |
| 6     | Challenges         | 2:00   |
| 7     | Kontribusi Tim     | 1:00   |
|       | Buffer             | ~0:30  |

---

## Referensi

- Outline lengkap: [uas-presentation-outline.md](uas-presentation-outline.md)
- Demo script teknis: [uts-demo-script.md](uts-demo-script.md)
- Checklist UAS: [final-checklist.md](final-checklist.md)
