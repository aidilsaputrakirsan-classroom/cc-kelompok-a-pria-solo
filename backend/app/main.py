import logging
from datetime import datetime

from app.api import routes
from config import settings
from dotenv import load_dotenv
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

load_dotenv()

_log_level_name = str(settings.LOG_LEVEL).upper()
_log_level = getattr(logging, _log_level_name, logging.INFO)
logging.basicConfig(level=_log_level, force=True)

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


# Root & healthcheck
@app.get("/")
async def root():
    return {"message": "Document Validator API", "status": "running"}


@app.get("/health")
async def health_check():
    return {
        "status": "healthy",
        "service": "backend",
        "version": settings.APP_VERSION,
        "database": "not_applicable",
        "timestamp": datetime.utcnow().isoformat(),
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


# Register routers
app.include_router(routes.router)
