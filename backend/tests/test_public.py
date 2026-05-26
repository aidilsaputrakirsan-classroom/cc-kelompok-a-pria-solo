"""Tests for GET /public (Modul 13 — graceful degradation)."""


def test_public_returns_operational_info(client):
    response = client.get("/public")
    assert response.status_code == 200
    data = response.json()
    assert data["service"] == "document-service"
    assert data["status"] in ("operational", "degraded")
    assert data["features"]["stats"] is True
    assert "circuit_breaker" in data


def test_public_available_when_circuit_open(client, monkeypatch):
    monkeypatch.setenv("FORCE_CIRCUIT_OPEN", "1")
    response = client.get("/public")
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "degraded"
    assert data["features"]["document_review"] is False
    assert data["features"]["stats"] is True
