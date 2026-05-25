"""Tests for GET /stats (document-service, no JWT — auth via OpenAdmin)."""

import pytest


@pytest.fixture
def stats_client(client, tmp_path, monkeypatch):
    monkeypatch.setenv("TEMP_STORAGE", str(tmp_path))
    ticket = tmp_path / "TICKET-001"
    ticket.mkdir()
    (ticket / "doc.pdf").write_bytes(b"%PDF-1.4 test")
    return client


def test_stats_returns_storage_metrics(stats_client):
    response = stats_client.get("/stats")
    assert response.status_code == 200
    data = response.json()
    assert data["total_tickets"] == 1
    assert data["total_files"] == 1
    assert data["total_size_bytes"] > 0
