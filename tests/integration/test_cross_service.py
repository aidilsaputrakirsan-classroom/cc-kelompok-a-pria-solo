"""
Cross-service integration tests — PRIA SOLO (Modul 13 Bagian C).
Run: pytest tests/integration/ -v
Prerequisite: docker compose up -d && migrate
"""
import os

import httpx
import pytest


def test_gateway_health(gateway_url):
    response = httpx.get(f"{gateway_url}/health", timeout=15.0)
    assert response.status_code == 200
    data = response.json()
    assert data.get("service") == "gateway"
    assert data.get("status") == "healthy"


def test_document_health_via_gateway(gateway_url):
    response = httpx.get(f"{gateway_url}/api/python/health", timeout=30.0)
    assert response.status_code == 200
    data = response.json()
    assert data["service"] == "document-service"
    assert data["status"] in ("healthy", "degraded")


def test_document_public_via_gateway(gateway_url):
    response = httpx.get(f"{gateway_url}/api/python/public", timeout=30.0)
    assert response.status_code == 200
    data = response.json()
    assert data["service"] == "document-service"
    assert "features" in data
    assert data["features"]["stats"] is True


def test_document_stats_via_gateway(gateway_url):
    response = httpx.get(f"{gateway_url}/api/python/stats", timeout=30.0)
    assert response.status_code == 200
    data = response.json()
    assert "total_tickets" in data
    assert "total_files" in data


def test_laravel_frontend_reachable(gateway_url):
    response = httpx.get(f"{gateway_url}/", timeout=30.0, follow_redirects=True)
    assert response.status_code in (200, 302)


def test_openapi_docs_via_gateway(gateway_url):
    response = httpx.get(f"{gateway_url}/api/python/docs", timeout=30.0)
    assert response.status_code == 200


def test_document_root_via_gateway(gateway_url):
    response = httpx.get(f"{gateway_url}/api/python/", timeout=30.0)
    assert response.status_code == 200
    assert response.json().get("status") == "running"


def test_processing_returns_503_when_circuit_forced(gateway_url):
    """Requires FORCE_CIRCUIT_OPEN=1 on document-service container."""
    if os.getenv("FORCE_CIRCUIT_OPEN", "").lower() not in ("1", "true", "yes"):
        pytest.skip("Set FORCE_CIRCUIT_OPEN=1 on document-service to run this test")
    response = httpx.post(
        f"{gateway_url}/api/python/review",
        data={"ticket": "T-INT", "ground_truth": "{}"},
        timeout=15.0,
    )
    assert response.status_code == 503
