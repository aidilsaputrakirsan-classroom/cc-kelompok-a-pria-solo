# Hasil Testing API — Backend Document Validator

Dokumen ini memuat **dokumentasi hasil testing** semua endpoint backend (FastAPI) untuk memenuhi **Tugas 2 Modul 2** — Lead Frontend: *"Buat file docs/api-test-results.md berisi screenshot/dokumentasi hasil testing semua endpoint via Swagger/Thunder Client"*.

**Penyesuaian:** Testing mengacu pada API **AI Document Validator** di codebase pria-solo (bukan resource items dari modul).

---

## Informasi Testing

| Item | Keterangan |
|------|------------|
| **Base URL** | `http://localhost:8000` |
| **Swagger UI** | http://localhost:8000/docs |
| **Tool** | Swagger UI / Thunder Client (sesuai yang dipakai saat test) |
| **Tanggal test** | *(isi tanggal saat testing dilakukan)* |
| **Backend** | FastAPI — Document Validator API |

---

## Daftar Endpoint yang Di-test

| No | Method | Endpoint | Deskripsi |
|----|--------|----------|-----------|
| 1 | GET | `/` | Info dasar API |
| 2 | GET | `/health` | Health check |
| 3 | GET | `/team` | Informasi tim |
| 4 | POST | `/information-extraction` | Upload PDF & ekstraksi (OCR + ground truth) |
| 5 | POST | `/review` | Validasi & advance review (typo, tanggal, harga, AI) |

Detail parameter dan contoh request/response lengkap: **[docs/api-dokumen-validasi-ai.md](api-dokumen-validasi-ai.md)**.

---

## 1. GET /

**Request:** Tidak ada body. Method GET.

**Response (200 OK):**

```json
{
  "message": "Document Validator API",
  "status": "running"
}
```

**Status code:** `200`

**Screenshot:** *(tambahkan screenshot dari Swagger UI atau Thunder Client)*

---

## 2. GET /health

**Request:** Tidak ada body. Method GET.

**Response (200 OK):**

```json
{
  "status": "healthy",
  "timestamp": "2026-03-09T12:00:00.000000"
}
```

*Nilai `timestamp` mengikuti waktu server saat request.*

**Status code:** `200`

**Screenshot:** *(tambahkan screenshot dari Swagger UI atau Thunder Client)*

---

## 3. GET /team

**Request:** Tidak ada body. Method GET.

**Response (200 OK):**

```json
{
  "team": "pria-solo",
  "members": [
    {"name": "Hyundo", "nim": "78903422", "role": "Lead Backend"},
    {"name": "Hyundo", "nim": "78903422", "role": "Lead Frontend"},
    {"name": "Hyundo", "nim": "78903422", "role": "Lead DevOps"},
    {"name": "Hyundo", "nim": "78903422", "role": "Lead QA & Docs"}
  ]
}
```

**Status code:** `200`

**Screenshot:** *(tambahkan screenshot dari Swagger UI atau Thunder Client)*

---

## 4. POST /information-extraction

**Request:**
- **Content-Type:** `multipart/form-data`
- **Body:**
  - `ticket` (string, wajib): ID tiket, mis. `TEST-001`
  - `files` (file[], wajib): Satu atau lebih file PDF

**Contoh response sukses (200 OK):**

```json
{
  "status": "completed",
  "ticket": "TEST-001",
  "total_files": 1,
  "ocr_extraction_success": 1,
  "ground_truth_results": {
    "P7": {
      "extracted_data": { ... }
    }
  }
}
```

**Status code:** `200` (sukses)

**Kode error yang mungkin:**
- `400` — File bukan PDF atau ticket kosong
- `500` — Gagal proses (OCR/ekstraksi)

**Screenshot:** *(tambahkan screenshot request (form + file) dan response dari Swagger/Thunder Client)*

---

## 5. POST /review

**Request:**
- **Content-Type:** `multipart/form-data` atau `application/x-www-form-urlencoded`
- **Body:**
  - `ticket` (string, wajib): ID tiket yang sudah pernah diekstraksi lewat `/information-extraction`
  - `ground_truth` (string, wajib): JSON string berisi data referensi untuk dibandingkan dengan hasil OCR

**Contoh response sukses (200 OK):**

```json
{
  "ticket": "TEST-001",
  "status": "completed",
  "validation_results": {
    "typo_checker": [ ... ],
    "date_validator": [ ... ],
    "price_validator": [ ... ]
  },
  "advance_review": { ... }
}
```

**Status code:** `200` (sukses)

**Kode error yang mungkin:**
- `400` — Ticket kosong atau ground_truth bukan JSON valid
- `404` — Hasil ekstraksi untuk tiket tidak ditemukan (jalankan `/information-extraction` dulu)
- `500` — Gagal proses review

**Screenshot:** *(tambahkan screenshot request (ticket + ground_truth) dan response dari Swagger/Thunder Client)*

---

## Ringkasan Hasil Testing

| No | Method | Endpoint | Status Diharapkan | Hasil |
|----|--------|----------|------------------|-------|
| 1 | GET | `/` | 200 | *(centang ✅ setelah test)* |
| 2 | GET | `/health` | 200 | *(centang ✅ setelah test)* |
| 3 | GET | `/team` | 200 | *(centang ✅ setelah test)* |
| 4 | POST | `/information-extraction` | 200 | *(centang ✅ setelah test)* |
| 5 | POST | `/review` | 200 | *(centang ✅ setelah test)* |

**Catatan:** Untuk endpoint 4 dan 5, pastikan backend berjalan (`uvicorn app.main:app --reload --port 8000`) dan variabel environment (Azure DI, OpenAI, TEMP_STORAGE) sudah diisi jika dipakai. Endpoint 5 memerlukan tiket yang sudah diekstraksi lewat endpoint 4.

---

*Dokumen ini memenuhi tugas Lead Frontend (Tugas 2 Modul 2). Screenshot dapat ditambahkan ke masing-masing section di atas setelah testing dilakukan.*
