# ============================================================
# Stage 1: Builder - create venv and install dependencies
# ============================================================
FROM python:3.12-slim AS builder

WORKDIR /app

COPY requirements.txt .

RUN python -m venv /opt/venv \
    && /opt/venv/bin/pip install --no-cache-dir --upgrade pip \
    && /opt/venv/bin/pip install --no-cache-dir -r requirements.txt

# ============================================================
# Stage 2: Runtime - copy only venv and source code
# ============================================================
FROM python:3.12-slim

WORKDIR /app

# curl for HEALTHCHECK, default-mysql-client for mysqladmin in wait-for-db script
RUN apt-get update \
    && apt-get install -y --no-install-recommends curl default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

COPY --from=builder /opt/venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

# Copy application source code and startup script
COPY . .
COPY scripts/wait-for-db.sh /usr/local/bin/wait-for-db.sh

# Create non-root runtime user and grant app directory ownership.
RUN sed -i 's/\r$//' /usr/local/bin/wait-for-db.sh \
    && chmod +x /usr/local/bin/wait-for-db.sh \
    && useradd -m appuser \
    && chown -R appuser:appuser /app
USER appuser

EXPOSE 8001

HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
  CMD curl -f http://127.0.0.1:8001/health || exit 1

ENTRYPOINT ["sh", "/usr/local/bin/wait-for-db.sh"]
CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8001"]
