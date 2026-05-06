"""Pytest fixtures — set env before importing the FastAPI app (CHUNK_SIZE required at import time)."""

import os

os.environ.setdefault("CHUNK_SIZE", "10")
# Typo service instantiates ChatOpenAI at import time; real calls are not made in smoke tests.
os.environ.setdefault("OPENAI_API_KEY", "test-dummy-key-for-ci")

import pytest
from fastapi.testclient import TestClient

from app.main import app


@pytest.fixture
def client():
    with TestClient(app) as c:
        yield c
