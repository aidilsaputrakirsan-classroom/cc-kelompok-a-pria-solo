# Operations Guide — PRIA Solo (Modul 14)

Monitoring, logging, and troubleshooting for the PRIA Solo microservices stack.

## Architecture

```
Browser → Nginx Gateway (:8080)
            ├── /                    → Laravel frontend (auth + OpenAdmin)
            ├── /frontend/health     → Laravel health
            ├── /frontend/metrics    → Laravel request metrics
            ├── /api/python/*        → FastAPI document-service
            ├── /health              → Gateway health (static JSON)
            └── /status              → System status dashboard
```

| Service | Role | Health | Metrics |
|---------|------|--------|---------|
| **Gateway** | Reverse proxy, rate limiting | `GET /health` | — |
| **Frontend** | Laravel + OpenAdmin | `GET /frontend/health` | `GET /frontend/metrics` |
| **Document Service** | FastAPI document validation | `GET /api/python/health` | `GET /api/python/metrics` |

---

## Quick Health Checks

```bash
# All services at once
make status

# Individual checks
curl -s http://localhost:8080/health | python -m json.tool
curl -s http://localhost:8080/api/python/health | python -m json.tool
curl -s http://localhost:8080/frontend/health | python -m json.tool
```

Open the dashboard: **http://localhost:8080/status**

---

## Reading Logs

All services emit **structured JSON logs** to stdout (captured by Docker).

```bash
# Follow all service logs
make logs
./scripts/logs.sh all

# ERROR and CRITICAL only
./scripts/logs.sh errors

# Export logs to file
docker compose logs --no-color > logs/all-services-$(date +%Y%m%d).log
```

**Log fields:** `timestamp`, `level`, `service`, `message`, `correlation_id`, `method`, `path`, `status_code`, `duration_ms`

**Production log level:** `INFO` (set via `LOG_LEVEL` env var)

---

## Tracing Requests (Correlation ID)

Every HTTP request receives an `X-Correlation-ID` header. The same ID flows:

```
Browser → Gateway → Laravel → Document Service
```

### Find the correlation ID

```bash
curl -v http://localhost:8080/api/python/health 2>&1 | grep -i correlation
```

### Trace across services

```bash
./scripts/logs.sh trace abc-test-12
# or
docker compose logs document-service frontend 2>&1 | grep "abc-test-12"
```

---

## Metrics

```bash
./scripts/logs.sh metrics

# Document service
curl -s http://localhost:8080/api/python/metrics | python -m json.tool

# Laravel frontend
curl -s http://localhost:8080/frontend/metrics | python -m json.tool
```

**Key metrics:**

| Metric | Description | Alert threshold |
|--------|-------------|-----------------|
| `total_requests` | Request count since startup | — |
| `error_rate_percent` | Overall 4xx/5xx percentage | > 10% triggers CRITICAL log |
| `error_rate_last_minute_percent` | Rolling 1-minute error rate | > 10% triggers alert |
| `latency.p95_ms` | 95th percentile response time | > 1000ms (investigate) |

Logs with `"alert": true` indicate error rate exceeded 10% in the last minute.

---

## Docker Commands

```bash
# Development (hot reload)
make up-dev

# Production-like (no exposed DB/backend ports)
make prod

# Container status
docker compose ps

# Restart a service
docker compose restart document-service
```

---

## Common Troubleshooting

### Document service returns 503

1. Check circuit breaker state: `curl http://localhost:8080/api/python/health`
2. Inspect logs: `docker compose logs document-service --tail=50`
3. Verify Azure/OpenAI env vars in `backend/.env.docker`

### Frontend cannot reach document-service

1. Confirm gateway is running: `curl http://localhost:8080/health`
2. Check `URL_VM_PYTHON=http://gateway/api/python` in frontend env
3. Trace request: look for correlation ID in Laravel logs

### Database connection errors

1. `docker compose ps` — ensure `db` is healthy
2. Run migrations: `make migrate`
3. Check credentials match `docker-compose.yml` and `frontend/.env.docker`

### High error rate alert in logs

1. Run `./scripts/logs.sh errors`
2. Identify failing endpoint from `path` field in JSON logs
3. Trace specific request: `./scripts/logs.sh trace <correlation-id>`

### Integration tests fail in CI

1. Open GitHub Actions → failed run → **Artifacts** → `docker-logs-*`
2. Review `document-service.log` and `frontend.log`

---

## Escalation Path

1. **Check status page** — http://localhost:8080/status
2. **Run `make status`** — confirm which service is down
3. **Read structured logs** — `./scripts/logs.sh errors`
4. **Trace request chain** — `./scripts/logs.sh trace <id>`
5. **Check metrics** — `./scripts/logs.sh metrics`
6. **Restart affected service** — `docker compose restart <service>`
7. **Full stack restart** — `docker compose down && docker compose up -d --build`

---

## CI/CD Log Artifacts

When integration tests fail in GitHub Actions, Docker logs are automatically exported as artifacts (`docker-logs-<run_id>`) for 7 days.

---

*PRIA Solo — Modul 14 Monitoring, Logging & Observability*
