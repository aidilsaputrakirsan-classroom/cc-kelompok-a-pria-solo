"""Tests for Modul 14 metrics and logging middleware."""
from fastapi import FastAPI
from fastapi.testclient import TestClient

from app.reliability.logging_middleware import RequestLoggingMiddleware
from app.reliability.metrics import metrics


def test_metrics_endpoint_returns_service_stats():
    app = FastAPI()
    app.add_middleware(RequestLoggingMiddleware)

    @app.get("/health")
    def health():
        return {"status": "healthy"}

    @app.get("/metrics")
    def get_metrics():
        return {"service": "document-service", **metrics.get_metrics()}

    client = TestClient(app)
    client.get("/health")
    client.get("/health")

    response = client.get("/metrics")
    assert response.status_code == 200
    data = response.json()
    assert data["service"] == "document-service"
    assert data["total_requests"] >= 2
    assert "error_rate_percent" in data
    assert "latency" in data


def test_correlation_id_header_returned():
    app = FastAPI()
    app.add_middleware(RequestLoggingMiddleware)

    @app.get("/ping")
    def ping():
        return {"ok": True}

    client = TestClient(app)
    response = client.get("/ping", headers={"X-Correlation-ID": "abc-test-12"})
    assert response.status_code == 200
    assert response.headers.get("x-correlation-id") == "abc-test-12"


def test_error_alert_threshold():
    metrics.reset()
    for _ in range(8):
        metrics.record_request("GET", "/fail", 500, 10.0)
    for _ in range(2):
        metrics.record_request("GET", "/ok", 200, 5.0)

    assert metrics.error_rate_last_minute() == 80.0
    assert metrics.check_and_alert("alert-test-id") is True

    metrics.reset()
