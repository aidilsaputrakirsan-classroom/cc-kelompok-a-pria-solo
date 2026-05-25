"""Smoke tests for public HTTP endpoints."""


def test_health(client):
    response = client.get("/health")
    assert response.status_code == 200
    data = response.json()
    assert data["status"] == "healthy"
    assert data["service"] == "document-service"
    assert data["database"] == "not_applicable"
    assert "version" in data


def test_root(client):
    response = client.get("/")
    assert response.status_code == 200
    assert response.json()["status"] == "running"


def test_team(client):
    response = client.get("/team")
    assert response.status_code == 200
    body = response.json()
    assert body["team"] == "pria-solo"
    assert isinstance(body["members"], list)
