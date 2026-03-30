# Laporan Penilaian Kelompok pria-solo – Kelas A
## Fase 1: Minggu Kuliah 1–4 (24 Feb – 22 Mar 2026)

---

### Informasi Kelompok

| Field | Detail |
|---|---|
| Nama Kelompok | pria-solo |
| Repo | cc-kelompok-a-pria-solo |
| Kelas | A |

**Anggota (dari `/team` endpoint):**

> **PERHATIAN:** `/team` endpoint berisi data placeholder palsu ("Hyundo", NIM "78903422" diulang 4 kali). Data ini **tidak valid** dan bukan data mahasiswa terdaftar.

| Git Name | Email | Commits W1-4 |
|---|---|---|
| jstdyn / justdyn | dynofadillah1@gmail.com | 6 |

> Hanya **1 kontributor** teridentifikasi dari git log. Identitas mahasiswa tidak dapat dikonfirmasi dari `/team`.

---

### A. Penilaian Kelompok (Kualitas Kode & Deliverable)

| Kriteria | Bobot | Nilai | Catatan |
|---|---|---|---|
| M1 – README + /team endpoint | 25% | 20 | `/team` berisi data palsu (semua "Hyundo", NIM "78903422"), tidak mencerminkan anggota kelompok |
| M2 – Backend CRUD | 25% | 30 | Hanya 2 POST routes (`/information-extraction`, `/review`) – tidak ada CRUD standar (GET/PUT/DELETE untuk entitas data) |
| M3 – React Frontend | 25% | 20 | Frontend adalah **Laravel PHP** (bukan React/Vite) – stack yang diminta tidak diimplementasikan |
| M4 – JWT + CORS + .env | 25% | 35 | Tidak ada JWT di backend. CORSMiddleware ✓. .env.example ✓ |

**Nilai Kelompok: (20 + 30 + 20 + 35) / 4 = 26.25**
**Grade Kelompok: E**

---

### B. Distribusi Kontribusi Individual (git log W9–W12)

> W9 = Minggu 1 (24 Feb–1 Mar), W10 = Minggu 2 (2–8 Mar), W11 = Minggu 3 (9–15 Mar), W12 = Minggu 4 (16–22 Mar)

> **Catatan alias:** `jstdyn` + `justdyn` = orang yang sama (email `dynofadillah1@gmail.com` identik). **Tidak ada commit di Minggu 2 (W10).**

| Git Name | Mg 1 | Mg 2 | Mg 3 | Mg 4 | Total (W9-W12) |
|---|---|---|---|---|---|
| jstdyn / justdyn | 2 | 0 | 3 | 1 | **6** |

---

### C. Penilaian Individual

#### jstdyn (identitas belum terkonfirmasi)

> Karena `/team` berisi data palsu, nilai individu **ditangguhkan** sampai dosen mengklarifikasi siapa `jstdyn` dan apakah ada anggota kelompok lain.

Jika diasumsikan `jstdyn` adalah satu-satunya anggota aktif:
- **Konsistensi (30%):** 3/4 minggu aktif (tidak ada W10) → 65
- **Substansi (40%):** 6 commits; tidak ada CRUD, tidak ada JWT, frontend Laravel → 30
- **Kesesuaian Peran (20%):** Tidak dapat dinilai (peran tidak diketahui) → 30
- **Kualitas Pesan (10%):** Deskriptif ("update: working on week X assignments") → 72

**Nilai estimasi: (0.30×65 + 0.40×30 + 0.20×30 + 0.10×72) = 19.5 + 12.0 + 6.0 + 7.2 = 44.7 → 45 (PENDING)**
**Grade estimasi: D (PENDING REVIEW)**

---

### D. Rekap Nilai

| Git Name | NIM | Role | Nilai Individu | Nilai Kelompok | Rata-rata | Grade |
|---|---|---|---|---|---|---|
| jstdyn / justdyn | **TIDAK DIKETAHUI** | — | **PENDING** | 26.25 | — | **PENDING** |

### E. Catatan & Flag

- **FLAG KRITIS:** `/team` berisi data palsu "Hyundo" – kewajiban Modul 1 sama sekali tidak dipenuhi.
- **FLAG KRITIS:** Frontend adalah Laravel PHP, bukan React/Vite – stack yang diminta tidak digunakan.
- **FLAG KRITIS:** Tidak ada implementasi JWT di backend.
- **FLAG KRITIS:** Hanya 1 kontributor teridentifikasi dari git log.
- **FLAG:** Tidak ada commit di Minggu 2 (W10).
- Mohon dosen mengklarifikasi: siapa `jstdyn`? Apakah ada anggota lain yang tidak commit?
- Kelompok ini memerlukan perhatian serius dan kemungkinan perlu konsultasi langsung.