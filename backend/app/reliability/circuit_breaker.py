"""
Circuit breaker — prevents cascading failure when document processing is unhealthy.
Modul 13 — PRIA SOLO document-service.
"""
import logging
import os
import time

logger = logging.getLogger(__name__)


class CircuitBreaker:
    """
    States:
    - CLOSED:    Normal — requests allowed.
    - OPEN:      Tripped — fail fast without heavy work.
    - HALF_OPEN: One probe allowed to test recovery.
    """

    def __init__(
        self,
        name: str = "default",
        failure_threshold: int | None = None,
        cooldown_seconds: int | None = None,
    ):
        self.name = name
        self.failure_threshold = failure_threshold or int(
            os.getenv("CIRCUIT_FAILURE_THRESHOLD", "5")
        )
        self.cooldown_seconds = cooldown_seconds or int(
            os.getenv("CIRCUIT_COOLDOWN_SECONDS", "30")
        )
        self.failure_count = 0
        self.state = "CLOSED"
        self.last_failure_time: float | None = None
        self.total_rejected = 0

    def _forced_open(self) -> bool:
        return os.getenv("FORCE_CIRCUIT_OPEN", "").lower() in ("1", "true", "yes")

    def can_execute(self) -> bool:
        if self._forced_open():
            return False

        if self.state == "CLOSED":
            return True

        if self.state == "OPEN":
            if self.last_failure_time is None:
                return False
            elapsed = time.time() - self.last_failure_time
            if elapsed >= self.cooldown_seconds:
                logger.info(
                    "[CircuitBreaker:%s] OPEN → HALF_OPEN (cooldown %ss elapsed)",
                    self.name,
                    self.cooldown_seconds,
                )
                self.state = "HALF_OPEN"
                return True
            self.total_rejected += 1
            return False

        return True

    def record_success(self) -> None:
        if self.state == "HALF_OPEN":
            logger.info("[CircuitBreaker:%s] HALF_OPEN → CLOSED", self.name)
        self.failure_count = 0
        self.state = "CLOSED"

    def record_failure(self) -> None:
        self.failure_count += 1
        self.last_failure_time = time.time()

        if self.state == "HALF_OPEN":
            logger.warning("[CircuitBreaker:%s] HALF_OPEN → OPEN (probe failed)", self.name)
            self.state = "OPEN"
        elif self.failure_count >= self.failure_threshold:
            logger.error(
                "[CircuitBreaker:%s] CLOSED → OPEN (%s/%s failures)",
                self.name,
                self.failure_count,
                self.failure_threshold,
            )
            self.state = "OPEN"

    def get_status(self) -> dict:
        state = "OPEN" if self._forced_open() else self.state
        return {
            "name": self.name,
            "state": state,
            "failure_count": self.failure_count,
            "failure_threshold": self.failure_threshold,
            "total_rejected": self.total_rejected,
            "cooldown_seconds": self.cooldown_seconds,
        }


processing_circuit = CircuitBreaker(name="document-processing")
