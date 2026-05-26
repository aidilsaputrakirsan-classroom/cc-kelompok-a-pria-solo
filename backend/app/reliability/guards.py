"""FastAPI guards for circuit breaker protected routes."""

from fastapi import HTTPException

from app.reliability.circuit_breaker import processing_circuit


def require_processing_available() -> None:
    """Fail fast when circuit is OPEN (graceful degradation for read endpoints)."""
    if not processing_circuit.can_execute():
        raise HTTPException(
            status_code=503,
            detail={
                "error": "Document processing temporarily unavailable",
                "code": "CIRCUIT_BREAKER_OPEN",
                "retryable": True,
            },
        )
