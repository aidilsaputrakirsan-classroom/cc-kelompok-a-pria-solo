import logging
from datetime import datetime

from app.api import public_info, routes, stats
from app.reliability.circuit_breaker import processing_circuit
from app.reliability.logging_config import setup_logging
from app.reliability.logging_middleware import RequestLoggingMiddleware
from app.reliability.metrics import metrics
from config import settings
from dotenv import load_dotenv
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

load_dotenv()

setup_logging()
logger = logging.getLogger(__name__)

app = FastAPI(
    title="Document Validator API",
    description="API untuk validasi dokumen",
    version=settings.APP_VERSION,
    docs_url="/docs",
    redoc_url="/redoc",
)

# CORS — whitelist (Modul 11: CORS_ORIGINS; proyek: ALLOWED_ORIGINS)
app.add_middleware(
    CORSMiddleware,
    allow_origins=list(settings.CORS_ORIGINS),
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.add_middleware(RequestLoggingMiddleware)


# Root & healthcheck
@app.get("/")
async def root():
    return {"message": "Document Validator API", "status": "running"}


@app.get("/health")
async def health_check():
    cb_status = processing_circuit.get_status()
    overall = "healthy" if cb_status["state"] == "CLOSED" else "degraded"

    return {
        "status": overall,
        "service": "document-service",
        "version": settings.APP_VERSION,
        "database": "not_applicable",
        "timestamp": datetime.utcnow().isoformat(),
        "dependencies": {
            "document-processing": {
                "status": "available" if cb_status["state"] == "CLOSED" else "unavailable",
                "circuit_breaker": cb_status,
            },
        },
    }


@app.get("/team")
async def team_info():
    return {
        "team": "pria-solo",
        "members": [
            {"name": "Dyno Fadillah Ramadhani", "nim": "10231033", "role": "Lead Backend"},
            {"name": "Dyno Fadillah Ramadhani", "nim": "10231033", "role": "Lead Frontend"},
            {"name": "Dyno Fadillah Ramadhani", "nim": "10231033", "role": "Lead DevOps"},
            {"name": "Dyno Fadillah Ramadhani", "nim": "10231033", "role": "Lead QA & Docs"},
        ],
    }


@app.get("/metrics")
def get_metrics():
    """Application metrics for monitoring (Modul 14)."""
    return {
        "service": "document-service",
        **metrics.get_metrics(),
    }


# Register routers
app.include_router(routes.router)
app.include_router(stats.router)
app.include_router(public_info.router)
