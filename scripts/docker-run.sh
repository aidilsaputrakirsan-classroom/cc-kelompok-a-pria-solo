#!/bin/bash
set -e

# ============================================================
# Modul 6 helper: run multi-container setup manually
# Adapted for pria-solo internship project.
# ============================================================

ACTION=${1:-start}
BACKEND_IMAGE=${BACKEND_IMAGE:-pria-solo-backend:v2}
FRONTEND_IMAGE=${FRONTEND_IMAGE:-pria-solo-frontend:v1}
NETWORK_NAME=${NETWORK_NAME:-cloudnet}
VOLUME_NAME=${VOLUME_NAME:-mysqldata}

case "$ACTION" in
  start)
    echo "[docker-run] Starting containers..."
    docker network create "$NETWORK_NAME" 2>/dev/null || true

    docker run -d \
      --name db \
      --network "$NETWORK_NAME" \
      -e MYSQL_ROOT_PASSWORD=root123 \
      -e MYSQL_DATABASE=cloudapp \
      -e MYSQL_USER=clouduser \
      -e MYSQL_PASSWORD=cloudpass \
      -p 3307:3306 \
      -v "$VOLUME_NAME":/var/lib/mysql \
      mysql:8.0 >/dev/null || true

    echo "[docker-run] Waiting briefly for database..."
    sleep 5

    docker run -d \
      --name backend \
      --network "$NETWORK_NAME" \
      --env-file backend/.env.docker \
      -p 8001:8001 \
      "$BACKEND_IMAGE" >/dev/null || true

    # Frontend container is optional in this repo (Laravel app is usually served separately).
    # Keep this step aligned with Modul 6 architecture expectations.
    docker run -d \
      --name frontend \
      --network "$NETWORK_NAME" \
      -p 3000:8000 \
      "$FRONTEND_IMAGE" >/dev/null || true

    echo "[docker-run] Done."
    echo "  Frontend: http://localhost:3000"
    echo "  Backend:  http://localhost:8001"
    echo "  DB host:  localhost:3307"
    ;;

  stop)
    echo "[docker-run] Stopping containers..."
    docker stop frontend backend db 2>/dev/null || true
    docker rm frontend backend db 2>/dev/null || true
    echo "[docker-run] Containers removed."
    ;;

  status)
    docker ps --format "table {{.Names}}\t{{.Image}}\t{{.Status}}\t{{.Ports}}"
    ;;

  logs)
    CONTAINER=${2:-backend}
    docker logs -f "$CONTAINER"
    ;;

  *)
    echo "Usage: ./scripts/docker-run.sh [start|stop|status|logs [container]]"
    exit 1
    ;;
esac
