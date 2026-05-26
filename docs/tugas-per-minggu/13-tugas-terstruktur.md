# Tugas Terstruktur — Modul 13 (PRIA SOLO)

**Sumber:** `docs/2026-modul-praktikum-cloudcomputing/13-modul.md` — Bagian C  
**Deadline:** Sebelum pertemuan 14 · **Format:** Pull Request (CI hijau)

## Pemetaan ke stack PRIA SOLO

| Peran | Branch (disarankan) | Implementasi di repo |
|-------|---------------------|----------------------|
| Lead Backend | `feature/graceful-degradation` | `backend/app/reliability/`, `GET /public`, circuit pada POST processing |
| Lead Frontend | `feature/error-handling-ui` | Banner, `service-health-banner.js`, 503 upload handling |
| Lead DevOps | `feature/compose-resilience` | `deploy.resources.limits`, `docker-compose.dev.yml`, nginx rate limit |
| Lead QA & Docs | `docs/reliability-testing` | `docs/reliability-testing.md`, `docs/architecture.md` |
| Lead CI/CD | `feature/ci-integration-test` | `tests/integration/`, job `integration-test` di CI |

## Verifikasi lokal

```bash
docker compose up -d --build
docker compose exec frontend php artisan migrate --force
pip install -r requirements-integration.txt
pytest tests/integration/ -v
cd backend && pytest tests/test_public.py tests/test_circuit_breaker.py tests/test_health.py -q
```

## Checklist pengumpulan

- [ ] Graceful degradation (`/stats`, `/public` saat processing unavailable)
- [ ] UI: banner + pesan 503 + retry
- [ ] Compose: restart + resource limits + dev override
- [ ] Dokumentasi reliability + diagram arsitektur
- [ ] CI: integration-test job lulus
