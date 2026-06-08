#!/usr/bin/env bash
# Final verification checklist — Modul 15 (PRIA Solo)
# Usage: ./scripts/verify-final.sh

set -euo pipefail

GATEWAY_URL="${GATEWAY_URL:-http://localhost:8080}"

echo "============================================"
echo "  FINAL VERIFICATION CHECKLIST — PRIA Solo"
echo "============================================"

echo ""
echo "1. Docker Compose..."
docker compose up -d
sleep 12
RUNNING=$(docker compose ps --format json 2>/dev/null | grep -c '"running"' || echo "0")
echo "   Containers running: ${RUNNING}/4 (db, document-service, frontend, gateway)"

echo ""
echo "2. Health checks..."
GW=$(curl -s -o /dev/null -w "%{http_code}" "${GATEWAY_URL}/health" || echo "000")
DOC=$(curl -s -o /dev/null -w "%{http_code}" "${GATEWAY_URL}/api/python/health" || echo "000")
FE=$(curl -s -o /dev/null -w "%{http_code}" "${GATEWAY_URL}/frontend/health" || echo "000")
echo "   Gateway: ${GW}"
echo "   Document Service: ${DOC}"
echo "   Laravel Frontend: ${FE}"

echo ""
echo "3. Metrics..."
METRICS=$(curl -s -o /dev/null -w "%{http_code}" "${GATEWAY_URL}/api/python/metrics" || echo "000")
echo "   Document Service Metrics: ${METRICS}"

echo ""
echo "4. Status page..."
STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${GATEWAY_URL}/status" || echo "000")
echo "   /status: ${STATUS}"

echo ""
echo "5. Frontend root..."
ROOT=$(curl -s -o /dev/null -w "%{http_code}" "${GATEWAY_URL}/" || echo "000")
echo "   Frontend (via gateway): ${ROOT}"

echo ""
echo "6. Rate limiting (burst login)..."
RATE_429=0
for i in $(seq 1 15); do
  CODE=$(curl -s -o /dev/null -w "%{http_code}" \
    -X POST "${GATEWAY_URL}/projess/auth/login" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -d "username=test@example.com&password=wrong" || echo "000")
  if [ "$CODE" = "429" ]; then
    RATE_429=1
    echo "   Request ${i}: HTTP 429 (rate limit active)"
    break
  fi
done
if [ "$RATE_429" = "0" ]; then
  echo "   Rate limit: not triggered in 15 requests (may need more burst or check nginx)"
fi

echo ""
echo "7. CI/CD..."
echo "   Check: https://github.com/aidilsaputrakirsan-classroom/cc-kelompok-a-pria-solo/actions"

echo ""
echo "============================================"
echo "  VERIFICATION COMPLETE"
echo "============================================"
