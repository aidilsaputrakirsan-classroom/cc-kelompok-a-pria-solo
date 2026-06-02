<?php

namespace App\Services;

/**
 * In-memory request metrics for Laravel frontend (Modul 14).
 */
class RequestMetrics
{
    private const ERROR_ALERT_THRESHOLD = 10.0;

    private const ERROR_ALERT_WINDOW_SECONDS = 60;

    private float $startTime;

    private int $requestCount = 0;

    private int $errorCount = 0;

    /** @var array<int, int> */
    private array $statusCounts = [];

    /** @var list<float> */
    private array $latencies = [];

    /** @var array<string, array{count: int, errors: int, total_latency_ms: float}> */
    private array $endpointStats = [];

    /** @var list<array{0: float, 1: bool}> */
    private array $recent = [];

    public function __construct()
    {
        $this->startTime = microtime(true);
    }

    public function recordRequest(string $method, string $path, int $statusCode, float $durationMs): void
    {
        $isError = $statusCode >= 400;
        $this->requestCount++;
        $this->statusCounts[$statusCode] = ($this->statusCounts[$statusCode] ?? 0) + 1;

        if ($isError) {
            $this->errorCount++;
        }

        $this->latencies[] = $durationMs;
        if (count($this->latencies) > 1000) {
            array_shift($this->latencies);
        }

        $key = $method.' '.$path;
        if (! isset($this->endpointStats[$key])) {
            $this->endpointStats[$key] = ['count' => 0, 'errors' => 0, 'total_latency_ms' => 0.0];
        }
        $this->endpointStats[$key]['count']++;
        $this->endpointStats[$key]['total_latency_ms'] += $durationMs;
        if ($isError) {
            $this->endpointStats[$key]['errors']++;
        }

        $this->recent[] = [microtime(true), $isError];
        $this->pruneOld();
    }

    private function pruneOld(): void
    {
        $cutoff = microtime(true) - self::ERROR_ALERT_WINDOW_SECONDS;
        $this->recent = array_values(array_filter(
            $this->recent,
            static fn (array $entry): bool => $entry[0] >= $cutoff
        ));
    }

    public function errorRateLastMinute(): float
    {
        $this->pruneOld();
        if ($this->recent === []) {
            return 0.0;
        }
        $errors = count(array_filter($this->recent, static fn (array $e): bool => $e[1]));

        return round($errors / count($this->recent) * 100, 2);
    }

    public function checkAndAlert(?string $correlationId = null): bool
    {
        $rate = $this->errorRateLastMinute();
        if ($rate <= self::ERROR_ALERT_THRESHOLD) {
            return false;
        }

        \Illuminate\Support\Facades\Log::critical(
            "Error rate {$rate}% exceeded ".self::ERROR_ALERT_THRESHOLD.'% threshold',
            array_filter([
                'alert' => true,
                'error_rate_percent' => $rate,
                'window_seconds' => self::ERROR_ALERT_WINDOW_SECONDS,
                'correlation_id' => $correlationId,
                'service' => 'frontend',
            ])
        );

        return true;
    }

    /** @return array<string, mixed> */
    public function getMetrics(): array
    {
        $uptime = round(microtime(true) - $this->startTime, 1);
        $errorRate = $this->requestCount > 0
            ? round($this->errorCount / $this->requestCount * 100, 2)
            : 0.0;

        $latencyStats = [];
        if ($this->latencies !== []) {
            $sorted = $this->latencies;
            sort($sorted);
            $n = count($sorted);
            $latencyStats = [
                'p50_ms' => round($sorted[(int) ($n * 0.5)], 2),
                'p95_ms' => round($sorted[(int) ($n * 0.95)], 2),
                'p99_ms' => round($sorted[min((int) ($n * 0.99), $n - 1)], 2),
                'avg_ms' => round(array_sum($sorted) / $n, 2),
            ];
        }

        $endpoints = [];
        foreach ($this->endpointStats as $key => $stats) {
            $count = $stats['count'];
            $endpoints[$key] = [
                'count' => $count,
                'errors' => $stats['errors'],
                'avg_latency_ms' => $count > 0 ? round($stats['total_latency_ms'] / $count, 2) : 0.0,
            ];
        }

        return [
            'service' => 'frontend',
            'uptime_seconds' => $uptime,
            'total_requests' => $this->requestCount,
            'total_errors' => $this->errorCount,
            'error_rate_percent' => $errorRate,
            'error_rate_last_minute_percent' => $this->errorRateLastMinute(),
            'status_codes' => $this->statusCounts,
            'latency' => $latencyStats,
            'endpoints' => $endpoints,
        ];
    }
}
