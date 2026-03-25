"""Pydantic validators for multipart/form API inputs (Modul 4 — setara validasi email/password di contoh modul)."""

from __future__ import annotations

import json
from typing import Any

from pydantic import BaseModel, Field, field_validator


class TicketField(BaseModel):
    """ID tiket: panjang terbatas, karakter aman untuk path & log."""

    ticket: str = Field(..., min_length=1, max_length=128)

    @field_validator("ticket")
    @classmethod
    def normalize_ticket(cls, v: str) -> str:
        t = (v or "").strip()
        if not t:
            raise ValueError("Ticket wajib diisi dan tidak boleh hanya spasi.")
        allowed = set(
            "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-. "
        )
        if not all(c in allowed for c in t):
            raise ValueError(
                "Format ticket tidak valid. Gunakan huruf, angka, spasi, titik, "
                "strip (-), atau underscore (_)."
            )
        return t


class GroundTruthJsonField(BaseModel):
    """String JSON ground_truth harus parse sebagai object (bukan array/primitive)."""

    ground_truth: str = Field(..., min_length=2, max_length=50_000_000)

    @field_validator("ground_truth")
    @classmethod
    def must_be_json_object(cls, v: str) -> str:
        try:
            data: Any = json.loads(v)
        except json.JSONDecodeError as e:
            raise ValueError(
                f"ground_truth bukan JSON valid: {e.msg} (posisi sekitar karakter {e.pos})."
            ) from e
        if not isinstance(data, dict):
            raise ValueError(
                "ground_truth harus berupa JSON object {{...}}, bukan array atau nilai primitif."
            )
        return v


def validation_error_message(exc) -> str:
    """Satu pesan ringkas untuk HTTP 400 dari Pydantic ValidationError."""
    errors = exc.errors()
    if not errors:
        return "Validasi input gagal."
    msg = errors[0].get("msg", "Input tidak valid")
    if isinstance(msg, str) and msg.startswith("Value error, "):
        return msg[len("Value error, ") :]
    return str(msg)
