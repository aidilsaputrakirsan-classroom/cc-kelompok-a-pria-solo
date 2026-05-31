#!/usr/bin/env bash
# Log helper for PRIA Solo microservices (Modul 14)
# Usage: ./scripts/logs.sh [command] [args]

set -euo pipefail

GATEWAY_URL="${GATEWAY_URL:-http://localhost:8080}"

case "${1:-}" in
  all)
    echo "Showing document-service + frontend logs..."
    docker compose logs -f document-service frontend
    ;;
  errors)
    echo "Showing ERROR logs only..."
    docker compose logs document-service frontend 2>&1 | grep -E '"level":"ERROR"|"level":"CRITICAL"' || true
    ;;
  trace)
    if [ -z "${2:-}" ]; then
      echo "Usage: ./scripts/logs.sh trace <correlation-id>"
      exit 1
    fi
    echo "Tracing correlation ID: $2"
    docker compose logs document-service frontend 2>&1 | grep "$2" || true
    ;;
  metrics)
    echo "--- Gateway ---"
    curl -sf "${GATEWAY_URL}/health" | python -m json.tool || echo "Gateway unreachable"
    echo ""
    echo "--- Document Service ---"
    curl -sf "${GATEWAY_URL}/api/python/metrics" | python -m json.tool || echo "Document service metrics unreachable"
    echo ""
    echo "--- Laravel Frontend ---"
    curl -sf "${GATEWAY_URL}/frontend/metrics" | python -m json.tool || echo "Frontend metrics unreachable"
    ;;
  *)
    echo "Usage: ./scripts/logs.sh {all|errors|trace <id>|metrics}"
    exit 1
    ;;
esac
