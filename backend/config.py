"""Application settings — read from environment (Modul 11 — Lead Backend)."""

from __future__ import annotations

import os


def _int_env(name: str, default: int) -> int:
    raw = os.getenv(name)
    if raw is None or not str(raw).strip():
        return default
    return int(str(raw).strip())


def _cors_origins_raw() -> str:
    """Prefer CORS_ORIGINS (Modul 11); fall back ke ALLOWED_ORIGINS (konvensi proyek)."""
    cors = os.getenv("CORS_ORIGINS", "").strip()
    if cors:
        return cors
    allowed = os.getenv("ALLOWED_ORIGINS", "").strip()
    if allowed:
        return allowed
    return "http://127.0.0.1:8000,http://localhost:8000"


class Settings:
    """Application settings — dibaca dari environment variables."""

    ENVIRONMENT: str = os.getenv("ENVIRONMENT", "development")
    DEBUG: bool = ENVIRONMENT == "development"

    DATABASE_URL: str = os.getenv(
        "DATABASE_URL",
        "postgresql://postgres:postgres@localhost:5432/cloudapp",
    )

    SECRET_KEY: str = os.getenv("SECRET_KEY", "dev-secret-key-change-in-production")
    ACCESS_TOKEN_EXPIRE_MINUTES: int = _int_env("TOKEN_EXPIRE_MINUTES", 30)

    CORS_ORIGINS: list[str] = [o.strip() for o in _cors_origins_raw().split(",") if o.strip()]

    LOG_LEVEL: str = os.getenv("LOG_LEVEL", "DEBUG" if DEBUG else "INFO")

    APP_VERSION: str = os.getenv("APP_VERSION", "1.0.0")


settings = Settings()
