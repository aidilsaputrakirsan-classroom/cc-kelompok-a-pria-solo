import logging
from datetime import datetime

from app.api import routes
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

app = FastAPI(
    title="Document Validator API",
    description="API untuk validasi dokumen",
    version="1.0.0",
    docs_url="/docs",
    redoc_url="/redoc"
)

# Middleware
origins = [
    "http://127.0.0.1:8000",       # Laravel dev
    "http://localhost:8000",
]

app.add_middleware(
    CORSMiddleware,
    allow_origins=origins,
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
    return {"status": "healthy", "timestamp": datetime.utcnow().isoformat()}


@app.get("/team")
async def team_info():
    return {
        "team": "pria-solo",
        "members": [
            {"name": "Hyundo", "nim": "78903422", "role": "Lead Backend"},
            {"name": "Hyundo", "nim": "78903422", "role": "Lead Frontend"},
            {"name": "Hyundo", "nim": "78903422", "role": "Lead DevOps"},
            {"name": "Hyundo", "nim": "78903422", "role": "Lead QA & Docs"},
        ]
    }


# Register routers
app.include_router(routes.router)
