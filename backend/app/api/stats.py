"""
Statistik operasi Document Service (Modul 12 Bagian C).
Autentikasi pengguna: OpenAdmin / Laravel — bukan di layer FastAPI.
Endpoint ini dipanggil server-to-server dari Laravel (jaringan internal Compose).
"""
import os
from pathlib import Path

from fastapi import APIRouter
from pydantic import BaseModel

router = APIRouter(tags=["stats"])

TEMP_STORAGE = Path(os.getenv("TEMP_STORAGE", "./temp"))


class DocumentStatsResponse(BaseModel):
    total_tickets: int
    total_files: int
    total_size_bytes: int
    largest_file_bytes: int | None
    smallest_file_bytes: int | None


def _collect_temp_stats() -> tuple[int, int, int, int | None, int | None]:
    if not TEMP_STORAGE.is_dir():
        return 0, 0, 0, None, None

    ticket_dirs = [p for p in TEMP_STORAGE.iterdir() if p.is_dir()]
    total_files = 0
    total_size = 0
    sizes: list[int] = []

    for ticket_dir in ticket_dirs:
        for path in ticket_dir.rglob("*"):
            if path.is_file():
                total_files += 1
                size = path.stat().st_size
                total_size += size
                sizes.append(size)

    largest = max(sizes) if sizes else None
    smallest = min(sizes) if sizes else None
    return len(ticket_dirs), total_files, total_size, largest, smallest


@router.get("/stats", response_model=DocumentStatsResponse)
async def get_document_stats():
    total_tickets, total_files, total_size, largest, smallest = _collect_temp_stats()
    return DocumentStatsResponse(
        total_tickets=total_tickets,
        total_files=total_files,
        total_size_bytes=total_size,
        largest_file_bytes=largest,
        smallest_file_bytes=smallest,
    )
