<?php

namespace App\Services;

/**
 * Simple circuit breaker for Laravel → document-service calls (Modul 13).
 */
class CircuitBreaker
{
    private const CACHE_PREFIX = 'document_service_cb_';

    public function __construct(
        private readonly string $name = 'document-service',
        private readonly int $failureThreshold = 5,
        private readonly int $cooldownSeconds = 30,
    ) {
    }

    public function canExecute(): bool
    {
        $state = cache()->get(self::CACHE_PREFIX.'state', 'CLOSED');
        if ($state === 'CLOSED') {
            return true;
        }
        if ($state === 'OPEN') {
            $lastFailure = (int) cache()->get(self::CACHE_PREFIX.'last_failure', 0);
            if ($lastFailure > 0 && (time() - $lastFailure) >= $this->cooldownSeconds) {
                cache()->put(self::CACHE_PREFIX.'state', 'HALF_OPEN', $this->cooldownSeconds + 60);

                return true;
            }
            cache()->increment(self::CACHE_PREFIX.'rejected');

            return false;
        }

        return true;
    }

    public function recordSuccess(): void
    {
        cache()->put(self::CACHE_PREFIX.'state', 'CLOSED', 3600);
        cache()->put(self::CACHE_PREFIX.'failures', 0, 3600);
    }

    public function recordFailure(): void
    {
        $failures = (int) cache()->increment(self::CACHE_PREFIX.'failures');
        cache()->put(self::CACHE_PREFIX.'last_failure', time(), 3600);
        $state = cache()->get(self::CACHE_PREFIX.'state', 'CLOSED');

        if ($state === 'HALF_OPEN' || $failures >= $this->failureThreshold) {
            cache()->put(self::CACHE_PREFIX.'state', 'OPEN', $this->cooldownSeconds + 60);
        }
    }

    /** @return array<string, mixed> */
    public function getStatus(): array
    {
        return [
            'name' => $this->name,
            'state' => cache()->get(self::CACHE_PREFIX.'state', 'CLOSED'),
            'failure_count' => (int) cache()->get(self::CACHE_PREFIX.'failures', 0),
            'failure_threshold' => $this->failureThreshold,
            'total_rejected' => (int) cache()->get(self::CACHE_PREFIX.'rejected', 0),
            'cooldown_seconds' => $this->cooldownSeconds,
        ];
    }
}
