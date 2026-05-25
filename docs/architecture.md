# Arsitektur Microservices — PRIA Solo

Modul 12 — stack tim: **Laravel + OpenAdmin** (web & **autentikasi**) + **FastAPI :8001** (document API) + **Nginx gateway**.

## Diagram

```mermaid
flowchart TD
    Browser["Browser (login OpenAdmin)"] --> GW["Gateway :8080"]
    GW -->|"/"| WEB["Laravel :8000"]
    GW -->|"/api/python/*"| DOC["Document Service :8001"]
    WEB --> MYSQL[("cloudapp MySQL")]
    WEB -.->|"URL_VM_PYTHON server-side"| DOC
```

## Autentikasi

| Lapisan | Mekanisme |
|---------|-----------|
| **Pengguna / admin** | **OpenAdmin** — session, `admin` middleware, tabel `admin_users` |
| **Upload & review** | Laravel job/controller → FastAPI (trusted internal network) |
| **GET /stats (UI)** | Route admin `api/document-stats` → proxy ke FastAPI `/stats` |
| **GET /stats (FastAPI)** | Tanpa JWT; hanya untuk pemanggilan internal / admin proxy |

Tidak ada service auth terpisah dan tidak ada duplikasi model user di Python.

## Services & ports

| Service | Port (host) | Peran |
|---------|-------------|--------|
| `gateway` | 8080 | Reverse proxy |
| `frontend` | via gateway | Laravel + OpenAdmin |
| `document-service` | **8001** | FastAPI |
| `db` | 3307 | MySQL |

## API

### Document service (FastAPI)

| Method | Path | Keterangan |
|--------|------|------------|
| GET | `/health` | Healthcheck |
| GET | `/stats` | Metrik `TEMP_STORAGE` |
| POST | `/information-extraction` | Dipanggil Laravel |
| POST | `/review` | Dipanggil Laravel |

Gateway: `http://localhost:8080/api/python/...`

### Laravel (OpenAdmin, perlu login)

| Method | Path (prefix admin) | Keterangan |
|--------|---------------------|------------|
| GET | `{admin}/api/document-stats` | Proxy ke FastAPI `/stats` |

Contoh: jika prefix admin adalah `admin`, URL  
`http://localhost:8080/admin/api/document-stats` (setelah login OpenAdmin).

## Menjalankan

```bash
docker compose up --build -d
docker compose exec frontend php artisan migrate --force
```

- OpenAdmin: http://localhost:8080/admin (sesuai konfigurasi `config/admin.php`)
- FastAPI docs: http://localhost:8001/docs

## Inter-service

Laravel memanggil document-service dengan `URL_VM_PYTHON` (Compose: `http://gateway/api/python` atau langsung `http://document-service:8001` dari container frontend).

## Debug

```bash
docker compose logs frontend
docker compose logs document-service
docker compose logs gateway
```
