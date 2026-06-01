#!/bin/sh
set -e

# Ensure writable storage (build-time dirs + Railway volumes mounted as root)
mkdir -p \
    /app/storage/logs \
    /app/storage/framework/cache \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/storage/app/public \
    /app/bootstrap/cache

chown -R appuser:appgroup /app/storage /app/bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx /app/storage /app/bootstrap/cache 2>/dev/null || true

exec su-exec appuser "$@"
