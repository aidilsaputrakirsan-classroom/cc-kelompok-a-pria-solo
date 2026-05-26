# Reliability Testing — PRIA SOLO (Modul 13)

Dokumentasi skenario uji ketahanan untuk arsitektur **Laravel + document-service (FastAPI) + Nginx gateway**.

## Pola yang diuji

| Pola | Implementasi |
|------|----------------|
| **Retry** | Laravel `DocumentServiceClient` — 3× percobaan, exponential backoff 0.5s / 1s / 2s |
| **Circuit breaker** | FastAPI `processing_circuit` + Laravel `CircuitBreaker` (cache) |
| **Graceful degradation** | `GET /stats`, `GET /public` tetap tersedia; `POST /review` & extraction → 503 saat circuit OPEN |

## Skenario uji

### 1. Document-service dihentikan

**Langkah:**

```bash
docker compose up -d
docker compose stop document-service
curl -s http://localhost:8080/api/python/health || echo "expected fail"
curl -s http://localhost:8080/api/python/public || echo "expected fail"
```

**Perilaku yang diharapkan:**

- Gateway `/api/python/*` tidak dapat menjangkau backend → fetch gagal
- Banner UI: *"Some features are temporarily unavailable"*
- Admin proxy stats (`/admin/api/document-stats`) → JSON degraded (nilai 0 + `degraded: true`), HTTP **200**
- Upload/review → error 503 atau pesan unavailable

**Recovery:**

```bash
docker compose start document-service
sleep 10
curl -s http://localhost:8080/api/python/health | python -m json.tool
```

Banner hilang setelah **Retry** atau refresh; health kembali `healthy` atau `operational`.

---

### 2. Circuit breaker OPEN (fail fast)

**Langkah:**

```bash
# Di backend/.env.docker atau compose environment:
# FORCE_CIRCUIT_OPEN=1
docker compose up -d --build document-service
curl -s -o /dev/null -w "%{http_code}\n" -X POST http://localhost:8080/api/python/review \
  -d "ticket=T1&ground_truth=%7B%7D"
curl -s http://localhost:8080/api/python/stats | python -m json.tool
curl -s http://localhost:8080/api/python/public | python -m json.tool
```

**Perilaku yang diharapkan:**

| Endpoint | HTTP | Catatan |
|----------|------|---------|
| `POST /review` | **503** | Cepat (<100ms), tanpa menunggu timeout panjang |
| `GET /stats` | **200** | Degraded mode — tetap baca metrik storage |
| `GET /public` | **200** | `status: degraded`, `document_review: false` |
| `GET /health` | **200** | `status: degraded` |

---

### 3. Threshold failure (tanpa FORCE_CIRCUIT_OPEN)

Simulasikan 5+ kegagalan beruntun pada `POST /review` (mis. ground truth invalid berulang atau service error) hingga `failure_count >= 5` di log document-service.

**Perilaku:** Circuit **CLOSED → OPEN**; request berikutnya ke processing endpoints gagal cepat dengan 503.

Setelah **30 detik** cooldown: state **HALF_OPEN** → satu request sukses → **CLOSED**.

---

### 4. Integration tests (otomatis)

```bash
docker compose up -d --build
docker compose exec frontend php artisan migrate --force
pip install -r requirements-integration.txt
pytest tests/integration/ -v
```

Minimal **7** tes lintas layanan (gateway + document-service via `/api/python/*`).

CI: job `integration-test` di `.github/workflows/ci.yml`.

---

### 5. Rate limiting (bonus gateway)

Nginx `limit_req` pada `/api/python/` — burst 40, rate 20 req/s per IP.

**Uji manual:** kirim >20 request/detik dengan `ab` atau loop `curl`; sebagian mendapat **503** dari nginx.

---

## Hasil uji (isi setelah menjalankan)

| Skenario | Tanggal | Hasil | Catatan |
|----------|---------|-------|---------|
| Service stop / recovery | | ☐ Pass ☐ Fail | |
| Circuit OPEN + degraded reads | | ☐ Pass ☐ Fail | |
| Integration pytest | | ☐ Pass ☐ Fail | |
| CI integration-test job | | ☐ Pass ☐ Fail | |

## Perintah cepat

```bash
make up
make migrate
make integration-test
docker compose logs document-service --tail=30
```
