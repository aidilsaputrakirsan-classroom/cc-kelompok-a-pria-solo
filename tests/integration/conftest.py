"""
Integration test configuration (Modul 13).
Requires: docker compose up -d (gateway on :8080).
"""
import os

import pytest

GATEWAY_URL = os.getenv("GATEWAY_URL", "http://localhost:8080").rstrip("/")


@pytest.fixture(scope="session")
def gateway_url():
    return GATEWAY_URL
