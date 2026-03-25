# Daftar API — AI Document Validator

Dokumen ini memuat daftar seluruh endpoint API yang terkait dengan fitur **AI Document Validator**, baik dari **Backend (FastAPI)** maupun **Frontend (Laravel Open Admin)**.

**Base URL Backend (FastAPI):** `http://127.0.0.1:8001`  
**Base URL Frontend (Laravel):** `http://127.0.0.1:8000` (route admin memakai prefix, mis. `/admin`)

---

## Daftar Isi

1. [Backend API (FastAPI)](#1-backend-api-fastapi)
2. [Frontend API — Rute Publik](#2-frontend-api--rute-publik)
3. [Frontend API — Rute Admin (AI Document Validator)](#3-frontend-api--rute-admin-ai-document-validator)

---

## 1. Backend API (FastAPI)

Backend menyediakan layanan ekstraksi dokumen (OCR) dan validasi/review berbasis AI. Dijalankan terpisah dari Laravel (standar proyek ini: **FastAPI port 8001**, Laravel **8000**).

### 1.1 Root & Health Check

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/` | Info dasar API (nama, status) |
| `GET` | `/health` | Health check untuk monitoring |
| `GET` | `/team` | Informasi tim pengembang (untuk keperluan modul) |

**Contoh response `GET /`:**
```json
{
  "message": "Document Validator API",
  "status": "running"
}
```

**Contoh response `GET /health`:**
```json
{
  "status": "healthy",
  "timestamp": "2026-02-27T10:00:00"
}
```

---

### 1.2 Ekstraksi Informasi Dokumen

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `POST` | `/information-extraction` | Upload file PDF dan jalankan ekstraksi (OCR + perhitungan ground truth) |

**Content-Type:** `multipart/form-data`

**Parameter:**

| Nama   | Tipe     | Wajib | Keterangan |
|--------|----------|-------|------------|
| `ticket` | string   | Ya    | ID tiket untuk tracking (unik per batch) |
| `files`  | file[]  | Ya    | Satu atau lebih file PDF |

**Contoh response sukses (200):**
```json
{
  "status": "completed",
  "ticket": "TICKET-001",
  "total_files": 3,
  "ocr_extraction_success": 3,
  "ground_truth_results": {
    "P7": { "extracted_data": { ... } },
    "BAST": { "extracted_data": { ... } }
  }
}
```

**Kode error:** `400` (file bukan PDF / ticket kosong), `500` (gagal proses)

---

### 1.3 Review & Validasi Dokumen

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `POST` | `/review` | Jalankan validasi (typo, tanggal, harga) dan advanced review AI terhadap hasil ekstraksi suatu tiket |

**Content-Type:** `multipart/form-data` atau `application/x-www-form-urlencoded`

**Parameter:**

| Nama         | Tipe   | Wajib | Keterangan |
|--------------|--------|-------|------------|
| `ticket`     | string | Ya    | ID tiket yang sudah pernah diekstraksi lewat `/information-extraction` |
| `ground_truth` | string | Ya  | JSON string berisi data referensi untuk dibandingkan dengan hasil OCR |

**Contoh response sukses (200):**
```json
{
  "ticket": "TICKET-001",
  "status": "completed",
  "validation_results": {
    "typo_checker": [ ... ],
    "date_validator": [ ... ],
    "price_validator": [ ... ]
  },
  "advance_review": { ... }
}
```

**Kode error:** `400` (ticket kosong / ground_truth invalid), `404` (hasil ekstraksi tiket tidak ada), `500` (gagal proses)

---

## 2. Frontend API — Rute Publik

Rute berikut dapat dipanggil tanpa login admin (mis. untuk polling status dari halaman validasi).

**Base URL:** `http://127.0.0.1:8000` (atau domain frontend; sesuaikan dengan `APP_URL` / `php artisan serve`)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/projess/api/ticket-status/{ticketNumber}` | Cek status proses ekstraksi untuk tiket (polling setelah upload) |
| `GET` | `/projess/api/review/status/{ticketNumber}` | Cek status proses review/validasi untuk tiket (polling setelah submit review) |
| `GET` | `/api/review/status/{ticketNumber}` | Sama seperti di atas, lewat rute API (prefix `/api`) |

**Parameter path:**
- `ticketNumber` — nomor/id tiket yang sama dengan yang dipakai di backend.

---

## 3. Frontend API — Rute Admin (AI Document Validator)

Semua rute di bawah ini berada di bawah **prefix admin** (mis. `/admin`). Contoh: jika prefix = `admin`, maka endpoint menjadi `/admin/validasi-dokumen`, `/admin/api/advance-upload`, dan seterusnya.

### 3.1 Halaman & Navigasi

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/{prefix}/validasi-dokumen` | Halaman utama fitur validasi dokumen AI |
| `GET` | `/{prefix}/riwayat-review` | Daftar riwayat review |
| `DELETE` | `/{prefix}/riwayat-review/{id}` | Hapus satu riwayat (by id) |
| `DELETE` | `/{prefix}/riwayat-review/review/{id}` | Hapus satu review (by id) |

---

### 3.2 Upload & Status Tiket

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `POST` | `/{prefix}/api/advance-upload` | Upload file PDF untuk validasi; memicu pemanggilan backend `POST /information-extraction` |

---

### 3.3 Ground Truth & Validasi

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/{prefix}/validate-ground-truth/{ticket_number}` | Halaman input/validasi ground truth untuk tiket |
| `POST` | `/{prefix}/validate-ground-truth/{ticket_number}/save` | Simpan draft ground truth |
| `POST` | `/{prefix}/validate-ground-truth/{ticket_number}/complete` | Selesaikan & kirim ground truth; memicu backend `POST /review` |
| `GET` | `/{prefix}/pdf/ground-truth/{ticket_number}/{doc_type}/{filename}` | Unduh/tampilkan PDF ground truth |

---

### 3.4 Submit Review (Backend)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `POST` | `/{prefix}/api/review/submit` | Submit review; frontend memanggil backend `POST /review` dan menyimpan hasil |

---

### 3.5 Hasil Advance Review

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/{prefix}/tickets/{ticketNumber}/advance-reviews` | Halaman daftar advance review untuk satu tiket |
| `GET` | `/{prefix}/api/tickets/{ticketNumber}/advance-reviews/data` | Data overview advance review (JSON) |
| `GET` | `/{prefix}/advance-result/{ticketNumber}/{docType}` | Halaman detail hasil advance review per tiket & tipe dokumen |
| `GET` | `/{prefix}/api/advance-result/{ticketNumber}/{docType}/data` | Data hasil advance review (JSON) |
| `GET` | `/{prefix}/pdf/advance/{ticketNumber}/{docType}/{filename}` | Unduh/tampilkan PDF terkait advance result |

---

### 3.6 Hasil Basic Review

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/{prefix}/basic-result/{ticket}` | Halaman detail hasil basic review untuk tiket |
| `GET` | `/{prefix}/api/basic-result/{ticket}/issues` | Data daftar issue basic review (JSON) |
| `GET` | `/{prefix}/pdf/basic/{ticketNumber}/{docType}/{filename}` | Unduh/tampilkan PDF basic result |

---

### 3.7 Catatan Tiket

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/{prefix}/api/tickets/{ticketNumber}/notes` | Ambil catatan untuk tiket |
| `POST` | `/{prefix}/api/tickets/{ticketNumber}/notes` | Simpan catatan untuk tiket |

---

### 3.8 Data Pendukung

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/{prefix}/api/companies` | Daftar nama perusahaan (untuk dropdown dll.) |

---

### 3.9 Pairing / Perbandingan Dokumen

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| `GET` | `/{prefix}/api/tickets/{ticketNumber}/pairing-documents/available` | Daftar dokumen yang tersedia untuk fitur pairing |
| `GET` | `/{prefix}/tickets/{ticketNumber}/pairing-documents/compare` | Halaman perbandingan dokumen |
| `GET` | `/{prefix}/pdf/pairing/{ticketNumber}/{documentId}` | Unduh/tampilkan PDF untuk pairing |

---

## Ringkasan Alur AI Document Validator

1. **Upload:** Pengguna upload PDF lewat `POST /{prefix}/api/advance-upload` → frontend memanggil backend `POST /information-extraction`.
2. **Status ekstraksi:** Frontend/pengguna melakukan polling `GET /projess/api/ticket-status/{ticketNumber}`.
3. **Ground truth:** Pengguna isi ground truth di halaman `GET /{prefix}/validate-ground-truth/{ticket_number}` dan submit lewat `POST .../complete` → frontend memanggil backend `POST /review`.
4. **Status review:** Polling `GET /projess/api/review/status/{ticketNumber}` atau `GET /api/review/status/{ticketNumber}`.
5. **Lihat hasil:** Melalui halaman advance-result, basic-result, dan riwayat-review; data JSON dari endpoint `api/advance-result/.../data`, `api/basic-result/.../issues`, dan `api/tickets/.../advance-reviews/data`.

---

*Dokumen ini menggabungkan spesifikasi Backend (FastAPI) dan Frontend (Laravel Open Admin) untuk fitur AI Document Validator. Terakhir diperbarui sesuai struktur proyek pria-solo.*
