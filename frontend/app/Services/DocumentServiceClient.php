<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP client to document-service with retry + circuit breaker (Modul 13).
 */
class DocumentServiceClient
{
    private const MAX_RETRIES = 3;

    private const BASE_DELAY_MS = 500;

    private const RETRYABLE_STATUS = [500, 502, 503, 504];

    public function __construct(
        private readonly CircuitBreaker $circuit = new CircuitBreaker(),
    ) {
    }

    public function baseUrl(): string
    {
        return rtrim((string) env('URL_VM_PYTHON', 'http://127.0.0.1:8001'), '/');
    }

    public function timeoutSeconds(): int
    {
        return (int) env('BACKEND_TIMEOUT', 30);
    }

    /** @return array{ok: bool, status: int, body: array<string, mixed>|null, degraded: bool, error?: string} */
    public function getStats(): array
    {
        return $this->getJson('/stats', allowWhenCircuitOpen: true);
    }

    /** @return array{ok: bool, status: int, body: array<string, mixed>|null, degraded: bool, error?: string} */
    public function getPublic(): array
    {
        return $this->getJson('/public', allowWhenCircuitOpen: true);
    }

    /** @return array{ok: bool, status: int, body: array<string, mixed>|null, degraded: bool, error?: string} */
    public function getHealth(): array
    {
        return $this->getJson('/health', allowWhenCircuitOpen: true);
    }

    /**
     * @return array{ok: bool, status: int, body: array<string, mixed>|null, degraded: bool, error?: string}
     */
    private function getJson(string $path, bool $allowWhenCircuitOpen = false): array
    {
        if (! $allowWhenCircuitOpen && ! $this->circuit->canExecute()) {
            return $this->unavailableResponse();
        }

        $url = $this->baseUrl().$path;
        $lastError = null;
        $headers = $this->requestHeaders();

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $response = Http::timeout($this->timeoutSeconds())
                    ->withHeaders($headers)
                    ->get($url);

                if ($response->successful()) {
                    $this->circuit->recordSuccess();

                    return [
                        'ok' => true,
                        'status' => $response->status(),
                        'body' => $response->json() ?? [],
                        'degraded' => false,
                    ];
                }

                if (in_array($response->status(), self::RETRYABLE_STATUS, true) && $attempt < self::MAX_RETRIES) {
                    Log::warning('Document service retryable status', [
                        'path' => $path,
                        'status' => $response->status(),
                        'attempt' => $attempt,
                        'correlation_id' => $headers['X-Correlation-ID'] ?? null,
                        'service' => 'frontend',
                    ]);
                    usleep(self::BASE_DELAY_MS * (2 ** ($attempt - 1)) * 1000);

                    continue;
                }

                if (in_array($response->status(), self::RETRYABLE_STATUS, true)) {
                    $this->circuit->recordFailure();
                }

                return [
                    'ok' => false,
                    'status' => $response->status(),
                    'body' => null,
                    'degraded' => true,
                    'error' => 'Service temporarily unavailable',
                ];
            } catch (ConnectionException|RequestException $e) {
                $lastError = $e->getMessage();
                Log::warning('Document service connection error', [
                    'path' => $path,
                    'attempt' => $attempt,
                    'message' => $lastError,
                    'correlation_id' => $headers['X-Correlation-ID'] ?? null,
                    'service' => 'frontend',
                ]);
                if ($attempt < self::MAX_RETRIES) {
                    usleep(self::BASE_DELAY_MS * (2 ** ($attempt - 1)) * 1000);
                }
            }
        }

        $this->circuit->recordFailure();

        return [
            'ok' => false,
            'status' => 503,
            'body' => null,
            'degraded' => true,
            'error' => $lastError ?? 'Service temporarily unavailable',
        ];
    }

    /** @return array{ok: bool, status: int, body: array<string, mixed>|null, degraded: bool, error?: string} */
    private function unavailableResponse(): array
    {
        return [
            'ok' => false,
            'status' => 503,
            'body' => null,
            'degraded' => true,
            'error' => 'Document service circuit breaker OPEN',
        ];
    }

    /** Degraded stats payload when document-service is down. */
    public function degradedStatsPayload(): array
    {
        return [
            'total_tickets' => 0,
            'total_files' => 0,
            'total_size_bytes' => 0,
            'largest_file_bytes' => null,
            'smallest_file_bytes' => null,
            'degraded' => true,
            'message' => 'Document service unavailable — showing cached empty metrics',
        ];
    }

    public function circuitBreaker(): CircuitBreaker
    {
        return $this->circuit;
    }

    /** @return array<string, string> */
    private function requestHeaders(): array
    {
        $headers = [];
        if (app()->runningInConsole()) {
            return $headers;
        }

        $request = request();
        if ($request === null) {
            return $headers;
        }

        $correlationId = $request->attributes->get('correlation_id');
        if ($correlationId) {
            $headers['X-Correlation-ID'] = (string) $correlationId;
        }

        return $headers;
    }
}
