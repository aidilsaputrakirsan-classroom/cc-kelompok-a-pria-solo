"""
In-memory metrics collector (Modul 14).
Tracks request count, error rate, latency percentiles, and per-endpoint stats.
"""
import logging
import threading
import time
from collections import defaultdict

logger = logging.getLogger(__name__)

ERROR_ALERT_THRESHOLD_PERCENT = 10.0
ERROR_ALERT_WINDOW_SECONDS = 60


class MetricsCollector:
    """Thread-safe in-memory metrics collector."""

    def __init__(self) -> None:
        self._lock = threading.Lock()
        self.start_time = time.time()
        self.request_count = 0
        self.error_count = 0
        self.status_counts: dict[int, int] = defaultdict(int)
        self.latencies: list[float] = []
        self.max_latency_samples = 1000
        self.endpoint_stats: dict[str, dict] = defaultdict(
            lambda: {"count": 0, "errors": 0, "total_latency_ms": 0.0}
        )
        self._recent: list[tuple[float, bool]] = []

    def _prune_old(self) -> None:
        cutoff = time.time() - ERROR_ALERT_WINDOW_SECONDS
        self._recent = [(ts, err) for ts, err in self._recent if ts >= cutoff]

    def record_request(
        self, method: str, path: str, status_code: int, duration_ms: float
    ) -> None:
        is_error = status_code >= 400
        with self._lock:
            self.request_count += 1
            self.status_counts[status_code] += 1
            if is_error:
                self.error_count += 1

            self.latencies.append(duration_ms)
            if len(self.latencies) > self.max_latency_samples:
                self.latencies.pop(0)

            key = f"{method} {path}"
            self.endpoint_stats[key]["count"] += 1
            self.endpoint_stats[key]["total_latency_ms"] += duration_ms
            if is_error:
                self.endpoint_stats[key]["errors"] += 1

            self._recent.append((time.time(), is_error))
            self._prune_old()

    def _error_rate_last_minute_unlocked(self) -> float:
        self._prune_old()
        if not self._recent:
            return 0.0
        errors = sum(1 for _, is_error in self._recent if is_error)
        return round(errors / len(self._recent) * 100, 2)

    def error_rate_last_minute(self) -> float:
        with self._lock:
            return self._error_rate_last_minute_unlocked()

    def check_and_alert(self, correlation_id: str | None = None) -> bool:
        """Log CRITICAL with alert=true if error rate exceeds threshold."""
        rate = self.error_rate_last_minute()
        if rate <= ERROR_ALERT_THRESHOLD_PERCENT:
            return False

        extra: dict = {
            "alert": True,
            "error_rate_percent": rate,
            "window_seconds": ERROR_ALERT_WINDOW_SECONDS,
        }
        if correlation_id:
            extra["correlation_id"] = correlation_id

        logger.critical(
            f"Error rate {rate}% exceeded {ERROR_ALERT_THRESHOLD_PERCENT}% threshold",
            extra=extra,
        )
        return True

    def get_metrics(self) -> dict:
        with self._lock:
            uptime = round(time.time() - self.start_time, 1)
            error_rate = (
                round(self.error_count / self.request_count * 100, 2)
                if self.request_count > 0
                else 0.0
            )

            latency_stats: dict = {}
            if self.latencies:
                sorted_lat = sorted(self.latencies)
                n = len(sorted_lat)
                latency_stats = {
                    "p50_ms": round(sorted_lat[int(n * 0.5)], 2),
                    "p95_ms": round(sorted_lat[int(n * 0.95)], 2),
                    "p99_ms": round(sorted_lat[min(int(n * 0.99), n - 1)], 2),
                    "avg_ms": round(sum(sorted_lat) / n, 2),
                }

            endpoints = {}
            for key, stats in self.endpoint_stats.items():
                count = stats["count"]
                avg_lat = (
                    round(stats["total_latency_ms"] / count, 2) if count > 0 else 0.0
                )
                endpoints[key] = {
                    "count": count,
                    "errors": stats["errors"],
                    "avg_latency_ms": avg_lat,
                }

            return {
                "uptime_seconds": uptime,
                "total_requests": self.request_count,
                "total_errors": self.error_count,
                "error_rate_percent": error_rate,
                "error_rate_last_minute_percent": self._error_rate_last_minute_unlocked(),
                "status_codes": dict(self.status_counts),
                "latency": latency_stats,
                "endpoints": endpoints,
            }

    def reset(self) -> None:
        with self._lock:
            self.request_count = 0
            self.error_count = 0
            self.status_counts.clear()
            self.latencies.clear()
            self.endpoint_stats.clear()
            self._recent.clear()


metrics = MetricsCollector()
