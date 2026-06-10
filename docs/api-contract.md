# API Contract — PRIA Solo (AI Document Validator)

Kontrak API formal untuk UAS — Modul 15. Detail alur bisnis & Laravel ada di [api-dokumen-validasi-ai.md](api-dokumen-validasi-ai.md).

## Base URLs

| Environment | Gateway | Document Service (langsung) |
|-------------|---------|----------------------------|
| Local (Compose) | http://localhost:8080 | http://localhost:8001 |
| Production | https://cc-kelompok-a-pria-solo-production.up.railway.app | https://backend-production-bdd8.up.railway.app |

Via gateway, prefix FastAPI: `http://localhost:8080/api/python/...`

## Authentication

| Lapisan | Mekanisme |
|---------|-----------|
| **Admin / pengguna** | Laravel Open Admin — **session cookie** setelah login di `/projess/auth/login` |
| **FastAPI document API** | **Tidak memakai JWT** — dipanggil oleh server Laravel (Guzzle/job) di jaringan internal |
| **Rate limiting** | Nginx: login 5 req/s, API 20 req/s, umum 30 req/s |

## Error response format

### FastAPI (JSON)

```json
{
  "detail": "Pesan error"
}
```

| Status | Arti |
|--------|------|
| 200 | Sukses |
| 400 | Validasi gagal / bad request |
| 401 | Tidak berlaku di FastAPI (auth di Laravel) |
| 422 | Validation error (Pydantic) |
| 429 | Rate limited (gateway) |
| 503 | Service unavailable (circuit breaker OPEN) |

### Laravel

HTML redirect atau JSON sesuai route; form login mengembalikan halaman error validasi.

---

## Document Service (FastAPI)

### GET /health

- **Auth:** Tidak
- **Response 200:**

```json
{
  "status": "healthy",
  "service": "document-service",
  "version": "string",
  "database": "not_applicable",
  "timestamp": "ISO-8601",
  "dependencies": { "document-processing": { "status": "available", "circuit_breaker": {} } }
}
```

### GET /metrics

- **Auth:** Tidak (internal/monitoring)
- **Response 200:** `service`, `uptime_seconds`, `total_requests`, `error_rate_percent`, `latency` (p50/p95/p99)

### GET /stats

- **Auth:** Tidak (dipanggil Laravel admin proxy)
- **Response 200:**

```json
{
  "total_tickets": 0,
  "total_files": 0,
  "total_size_bytes": 0,
  "largest_file_bytes": null,
  "smallest_file_bytes": null
}
```

### GET /public

- **Auth:** Tidak
- **Response 200:** Info operasional publik (degraded-safe)

### POST /information-extraction

- **Rate limit:** 20 req/s (via gateway)
- **Content-Type:** `multipart/form-data`
- **Fields:**
  - `ticket` — string, 1–128 karakter, alphanumeric + `_.- `
  - `files` — satu atau lebih PDF, maks 100 file, maks 50 MiB per file
- **Response 200:** Hasil ekstraksi OCR + ground truth paths
- **Response 400:** Validasi ticket/file gagal
- **Response 503:** Circuit breaker OPEN

### POST /review

- **Rate limit:** 20 req/s
- **Content-Type:** `multipart/form-data`
- **Fields:** `ticket`, `ground_truth` (JSON object string, max ~50MB)
- **Response 200:** Hasil validasi AI
- **Response 400/503:** Sama seperti extraction

### GET /team

- **Auth:** Tidak
- **Response 200:** Metadata tim (modul kuliah)

---

## Gateway (Nginx)

| Method | Path | Proxy ke |
|--------|------|----------|
| GET | `/health` | JSON gateway health |
| GET | `/status` | Laravel status dashboard |
| GET | `/frontend/health` | Laravel `/health` |
| GET | `/frontend/metrics` | Laravel `/metrics` |
| * | `/api/python/*` | FastAPI (strip prefix) |
| POST | `/projess/auth/login` | Laravel (rate limit ketat) |
| * | `/` | Laravel frontend |

---

## Laravel proxy endpoints (perlu login Open Admin)

| Method | Path (contoh) | Proxy ke |
|--------|---------------|----------|
| GET | `{admin}/api/document-stats` | FastAPI `/stats` |
| GET | `{admin}/api/document-public` | FastAPI `/public` |

Prefix admin default: `projess` → contoh: `/projess/api/document-stats`

---

## Validasi input (ringkas)

| Field | Aturan |
|-------|--------|
| `ticket` | Wajib, strip, max 128, karakter aman |
| `files` | Wajib min 1, hanya `.pdf`, max 100/request |
| `ground_truth` | JSON object valid, bukan array/primitif |
| File size | Max 50 MiB per PDF |

Implementasi: `backend/app/schemas/route_inputs.py`, `backend/app/api/routes.py`
