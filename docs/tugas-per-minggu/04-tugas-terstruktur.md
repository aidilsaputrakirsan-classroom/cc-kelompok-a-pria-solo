# Tugas 4: Polish Full-Stack App & Dokumentasi — Jawaban Berdasarkan Codebase (Magang)

**Modul:** 04-modul.md (BAGIAN C: TUGAS TERSTRUKTUR — *Tugas 4*)  
**Proyek:** pria-solo — AI Document Validator (magang)  
**Penyesuaian:** Tugas asli modul mengacu pada **React + JWT + CRUD `items`**. Sesuai arahan dosen, implementasi dan bukti mengacu pada **`backend/` (FastAPI)** dan **`frontend/` (Laravel + Open Admin)** yang dipakai di magang.

---

## 0. Konteks Singkat

- **Backend:** `backend/app/` — FastAPI, endpoint antara lain `GET /`, `/health`, `/team`, `POST /information-extraction`, `POST /review`. Validasi input diperketat dengan **Pydantic** (`app/schemas/route_inputs.py`). **CORS** memakai whitelist dari environment variable `ALLOWED_ORIGINS` (`app/main.py`).
- **Frontend:** `frontend/` — Laravel memanggil FastAPI dari server (mis. `URL_VM_PYTHON` di `.env`, dipakai di `ProcessAdvanceUploadJob` dan controller terkait). UI upload memakai modal, overlay loading, dan **notifikasi Bootstrap** (`showNotification` di `public/js/file-upload-handler.js`).
- **Dokumentasi baru (Tugas 4):**
  - [docs/setup-guide.md](../setup-guide.md) — setup end-to-end
  - [docs/api-documentation.md](../api-documentation.md) — ringkasan endpoint + **cURL**
  - Dokumen ini — pemetaan peran modul → magang

---

## 1. Lead Backend — Validasi & Pesan Error (Disetarakan dari Email/Password + `/items/stats`)

### Tugas modul (referensi)

> Tambah validasi: email format, password strength (Pydantic regex). Tambah `GET /items/stats` jika belum. Pesan error informatif.

### Penyesuaian ke AI Document Validator

- Tidak ada registrasi user / JWT / resource `items` di FastAPI magang.
- **Setara validasi Pydantic:** model `TicketField` dan `GroundTruthJsonField` di `backend/app/schemas/route_inputs.py` memvalidasi `ticket` (panjang, charset aman) dan `ground_truth` (JSON object valid).
- **Batas operasional:** jumlah file PDF per request, ukuran maksimum per file — di `backend/app/api/routes.py`.
- **Pesan error:** respons `400` memakai teks **bahasa Indonesia** yang jelas.
- **`GET /items/stats`:** tidak ditambahkan sebagai endpoint fiktif; statistik bisnis lebih relevan di **Laravel + MySQL** (tiket, hasil review). Lihat juga penjelasan Tugas 3 untuk stats/pagination.

**Bukti:** `route_inputs.py`, perubahan di `routes.py`.

---

## 2. Lead Frontend — Notifikasi & Loading (Disetarakan dari Toast React)

### Tugas modul (referensi)

> Notifikasi sukses/gagal + loading state setelah create/update/delete.

### Penyesuaian ke AI Document Validator

- Operasi utama bukan CRUD `items` di React, melainkan **upload dokumen → polling ekstraksi → redirect ground truth**.
- Sudah ada: overlay loading, modal error, alur sukses di overlay.
- **Tambahan Tugas 4:** `showNotification` dipanggil setelah **upload chunk sukses** (alert sukses ~3 detik) dan pada **timeout polling** (alert danger + modal error tetap).

**Bukti:** `frontend/public/js/file-upload-handler.js`.

---

## 3. Lead DevOps — `docs/setup-guide.md`

### Tugas modul (referensi)

> `docs/setup-guide.md`: clone → install deps → DB → `.env` → jalankan backend & frontend.

### Implementasi

File **[docs/setup-guide.md](../setup-guide.md)** berisi urutan: prasyarat, venv Python, `backend/.env`, uvicorn port **8001**, Composer Laravel, migrate, `URL_VM_PYTHON` (mis. `http://127.0.0.1:8001`), `php artisan serve` port **8000**, opsi queue worker, checklist verifikasi.

**Bukti:** file di atas.

---

## 4. Lead QA & Docs — README + Uji End-to-End

### Tugas modul (referensi)

> Uji auth + CRUD; README dengan section Authentication dan daftar endpoint.

### Penyesuaian

- **Auth:** Laravel Open Admin (session); FastAPI tanpa JWT untuk endpoint magang — dijelaskan di **README** bagian *Authentication & security model*.
- **CRUD:** setara dengan alur **upload / validasi / review / riwayat**; hasil uji UI tetap dirujuk di [docs/ui-test-results.md](../ui-test-results.md), API di [docs/api-test-results.md](../api-test-results.md).

**Bukti:** README root proyek (diperbarui bersama Tugas 4).

---

## 5. Lead CI/CD — `docs/api-documentation.md`

### Tugas modul (referensi)

> Dokumentasi semua endpoint: method, URL, body, auth, contoh cURL.

### Implementasi

**[docs/api-documentation.md](../api-documentation.md)** memuat tabel ringkas endpoint FastAPI dan **contoh cURL** untuk `/`, `/health`, `/team`, `/information-extraction`, `/review`, plus referensi ke `api-dokumen-validasi-ai.md` untuk detail Laravel + alur lengkap.

**Bukti:** file di atas.

---

## 6. Konfigurasi lingkungan (setara Modul 4 — env & CORS)

- **Backend:** `ALLOWED_ORIGINS` ditambahkan di `backend/.env.example`; dibaca di `main.py`.
- **Frontend:** `URL_VM_PYTHON` didokumentasikan di `frontend/.env.example` agar base URL FastAPI tidak “tersembunyi” di kode.

---

## 7. Ringkasan

| Diminta modul (konsep) | Di proyek magang |
|------------------------|------------------|
| Validasi kuat + pesan jelas | Pydantic + batas file + pesan ID di FastAPI |
| Toast / feedback UI | Alert Bootstrap 3 dtk + modal/overlay yang ada |
| Setup guide | `docs/setup-guide.md` |
| README + auth story | README diperbarui |
| API doc + cURL | `docs/api-documentation.md` |
| JWT + `/items` | Sengaja tidak dipaksakan; autentikasi di Laravel, domain dokumen AI |

Dengan demikian **Tugas 4** dipenuhi **sesuai proyek intern `frontend/` dan `backend/`**, tetap selaras tujuan pembelajaran Modul 4 (integrasi, konfigurasi, keamanan dasar, dokumentasi).
