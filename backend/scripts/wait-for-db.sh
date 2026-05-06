#!/bin/sh
set -eu

# Wait for MySQL only when DB_HOST is configured.
# This keeps local/non-DB runs working while supporting Docker network startup ordering.
if [ -n "${DB_HOST:-}" ]; then
  DB_PORT="${DB_PORT:-3306}"
  DB_WAIT_RETRIES="${DB_WAIT_RETRIES:-60}"
  DB_WAIT_SLEEP="${DB_WAIT_SLEEP:-2}"

  echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT} ..."
  i=1
  while [ "$i" -le "$DB_WAIT_RETRIES" ]; do
    if mysqladmin ping -h"${DB_HOST}" -P"${DB_PORT}" --silent >/dev/null 2>&1; then
      echo "MySQL is ready."
      break
    fi
    echo "[$i/$DB_WAIT_RETRIES] MySQL is unavailable, retrying in ${DB_WAIT_SLEEP}s..."
    sleep "${DB_WAIT_SLEEP}"
    i=$((i + 1))
  done

  if [ "$i" -gt "$DB_WAIT_RETRIES" ]; then
    echo "Timed out waiting for MySQL at ${DB_HOST}:${DB_PORT}."
    exit 1
  fi
else
  echo "DB_HOST not set; skipping DB wait."
fi

exec "$@"
#!/bin/sh

# Optional DB readiness gate for Modul 6 workflow.
# If DATABASE_URL is not provided, continue immediately.
if [ -z "${DATABASE_URL}" ]; then
  echo "[wait-for-db] DATABASE_URL is not set. Skipping DB readiness check."
  exec "$@"
fi

# Strip Windows CRLF from env (Docker --env-file on Windows can leave \r).
trim_cr() {
  printf '%s' "$1" | tr -d '\r'
}

DB_HOST=$(trim_cr "${DB_HOST:-db}")
DB_PORT=$(trim_cr "${DB_PORT:-3306}")
# Prefer root for ping — always exists; app user may lag during first init.
PING_USER=$(trim_cr "${MYSQL_PING_USER:-root}")
PING_PASS=$(trim_cr "${MYSQL_ROOT_PASSWORD:-}")
if [ -z "${PING_PASS}" ]; then
  PING_USER=$(trim_cr "${MYSQL_USER:-root}")
  PING_PASS=$(trim_cr "${MYSQL_PASSWORD:-}")
fi

MAX_RETRIES="${DB_WAIT_RETRIES:-60}"
SLEEP_SECONDS="${DB_WAIT_SLEEP:-2}"

echo "[wait-for-db] Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
i=0
while [ "${i}" -lt "${MAX_RETRIES}" ]; do
  # --skip-ssl avoids self-signed cert errors; timeout prevents hangs.
  if timeout 5 mysqladmin ping -h "${DB_HOST}" -P "${DB_PORT}" -u"${PING_USER}" -p"${PING_PASS}" --skip-ssl --silent >/dev/null 2>&1; then
    echo "[wait-for-db] MySQL is ready."
    exec "$@"
  fi
  i=$((i + 1))
  sleep "${SLEEP_SECONDS}"
done

echo "[wait-for-db] MySQL is not ready after ${MAX_RETRIES} attempts."
exit 1
