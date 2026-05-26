"""
Public operational info — no auth (Modul 13 graceful degradation).
Always available even when processing circuit is OPEN.
"""
from config import settings
from fastapi import APIRouter

from app.reliability.circuit_breaker import processing_circuit

router = APIRouter(tags=["public"])


@router.get("/public")
async def get_public_info():
    cb = processing_circuit.get_status()
    processing_available = cb["state"] == "CLOSED"
    overall = "operational" if processing_available else "degraded"

    return {
        "service": "document-service",
        "status": overall,
        "version": settings.APP_VERSION,
        "features": {
            "document_review": processing_available,
            "information_extraction": processing_available,
            "stats": True,
            "health": True,
        },
        "circuit_breaker": cb,
        "message": (
            "All features available"
            if processing_available
            else "Document processing temporarily limited; read-only endpoints remain available"
        ),
    }
