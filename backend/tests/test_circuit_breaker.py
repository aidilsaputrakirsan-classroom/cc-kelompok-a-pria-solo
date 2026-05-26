"""Tests for circuit breaker on processing routes (Modul 13)."""

from app.reliability.circuit_breaker import CircuitBreaker, processing_circuit


def test_processing_returns_503_when_circuit_forced_open(client, monkeypatch):
    monkeypatch.setenv("FORCE_CIRCUIT_OPEN", "1")
    response = client.post(
        "/review",
        data={"ticket": "T-1", "ground_truth": "{}"},
    )
    assert response.status_code == 503


def test_stats_still_works_when_circuit_forced_open(client, monkeypatch):
    monkeypatch.setenv("FORCE_CIRCUIT_OPEN", "1")
    response = client.get("/stats")
    assert response.status_code == 200


def test_circuit_breaker_state_transitions():
    cb = CircuitBreaker(name="test", failure_threshold=2, cooldown_seconds=1)
    assert cb.can_execute()
    cb.record_failure()
    assert cb.can_execute()
    cb.record_failure()
    assert cb.state == "OPEN"
    assert not cb.can_execute()
    cb.record_success()
    assert cb.state == "CLOSED"


def test_health_shows_degraded_when_circuit_open(client, monkeypatch):
    monkeypatch.setenv("FORCE_CIRCUIT_OPEN", "1")
    response = client.get("/health")
    assert response.status_code == 200
    assert response.json()["status"] == "degraded"
