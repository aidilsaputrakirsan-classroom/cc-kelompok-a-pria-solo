# API documentation (ringkas + contoh cURL) — Backend FastAPI

Dokumen ini memenuhi struktur tugas **Modul 4 — Lead CI/CD: `api-documentation.md`** untuk proyek **pria-solo**. Spesifikasi lengkap parameter, error, dan rute Laravel ada di **[api-dokumen-validasi-ai.md](api-dokumen-validasi-ai.md)**.

**Base URL default lokal (FastAPI):** `http://127.0.0.1:8001` — Laravel UI biasanya di `http://127.0.0.1:8000`.  
**Auth:** Endpoint di bawah **tidak** memakai JWT. Akses admin ada di Laravel (session). Pemanggilan ke FastAPI dari production biasanya dari **server Laravel** (Guzzle/HTTP client), bukan dari browser publik.

---

## Ringkasan endpoint

| Method | Path | Auth | Deskripsi singkat |
|--------|------|------|-------------------|
| GET | `/` | Tidak | Info API |
| GET | `/health` | Tidak | Health check |
| GET | `/team` | Tidak | Metadata tim (keperluan modul) |
| POST | `/information-extraction` | Tidak* | Upload PDF + ekstraksi OCR / ground truth |
| POST | `/review` | Tidak* | Review/validasi AI terhadap hasil ekstraksi |

\*Tidak ada header `Authorization`. Lindungi dengan firewall/reverse proxy di lingkungan production jika perlu.

---

## cURL — GET `/`

```bash
curl -sS http://127.0.0.1:8001/
```

---

## cURL — GET `/health`

```bash
curl -sS http://127.0.0.1:8001/health
```

---

## cURL — GET `/team`

```bash
curl -sS http://127.0.0.1:8001/team
```

---

## cURL — POST `/information-extraction`

**Content-Type:** `multipart/form-data`  
**Field:** `ticket` (string), `files` (satu atau lebih file `.pdf`).

```bash
curl -sS -X POST http://127.0.0.1:8001/information-extraction \
  -F "ticket=TICKET-DEMO-001" \
  -F "files=@/path/ke/dokumen/P7.pdf"
```

Beberapa file:

```bash
curl -sS -X POST http://127.0.0.1:8001/information-extraction \
  -F "ticket=TICKET-DEMO-002" \
  -F "files=@/path/P7.pdf" \
  -F "files=@/path/BAST.pdf"
```

**Validasi tambahan (server):** ticket — panjang & karakter aman; maksimal 100 PDF per request; maksimal ~50 MiB per file. Detail pesan error dalam bahasa Indonesia di response JSON (`detail`).

---

## cURL — POST `/review`

**Content-Type:** `multipart/form-data` atau `application/x-www-form-urlencoded`  
**Field:** `ticket` (sama seperti saat ekstraksi), `ground_truth` (string berisi **JSON object**).

```bash
curl -sS -X POST http://127.0.0.1:8001/review \
  -F "ticket=TICKET-DEMO-001" \
  -F 'ground_truth={"P7":{"field":"value"}}'
```

**Catatan:** Tiket harus sudah pernah sukses melalui `/information-extraction` sehingga file hasil ekstraksi ada di `TEMP_STORAGE` untuk tiket tersebut.

---

## Konfigurasi lingkungan (backend)

| Variabel | Fungsi |
|----------|--------|
| `ALLOWED_ORIGINS` | Origin halaman Laravel di browser (koma), contoh: `http://127.0.0.1:8000,http://localhost:8000` |
| `TEMP_STORAGE` | Direktori kerja tiket / hasil ekstraksi |
| Kunci Azure / OpenAI | Lihat `backend/.env.example` |

---

## Lanjutan

- **OpenAPI interaktif:** http://127.0.0.1:8001/docs  
- **Alur lengkap dengan rute Laravel:** [api-dokumen-validasi-ai.md](api-dokumen-validasi-ai.md)
