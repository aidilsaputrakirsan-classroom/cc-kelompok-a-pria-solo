<?php

namespace App\Http\Middleware;

use App\Services\RequestMetrics;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Generate or propagate X-Correlation-ID and log each request as JSON (Modul 14).
 */
class CorrelationIdMiddleware
{
    /** @var RequestMetrics */
    protected $metrics;

    public function __construct(RequestMetrics $metrics)
    {
        $this->metrics = $metrics;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = $request->header('X-Correlation-ID') ?: Str::substr((string) Str::uuid(), 0, 12);
        $request->attributes->set('correlation_id', $correlationId);

        $start = microtime(true);

        try {
            $response = $next($request);
        } catch (\Throwable $e) {
            $durationMs = round((microtime(true) - $start) * 1000, 2);
            $this->metrics->recordRequest($request->method(), $request->path(), 500, $durationMs);
            $this->metrics->checkAndAlert($correlationId);

            Log::error('Request failed: '.$request->method().' '.$request->path(), [
                'correlation_id' => $correlationId,
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => 500,
                'duration_ms' => $durationMs,
                'service' => 'frontend',
            ]);

            throw $e;
        }

        $durationMs = round((microtime(true) - $start) * 1000, 2);
        $statusCode = $response->getStatusCode();
        $this->metrics->recordRequest($request->method(), $request->path(), $statusCode, $durationMs);
        $this->metrics->checkAndAlert($correlationId);

        if (! in_array($request->path(), ['health', 'metrics'], true)) {
            $level = $statusCode >= 400 ? 'warning' : 'info';
            Log::{$level}($request->method().' '.$request->path().' → '.$statusCode.' ('.$durationMs.'ms)', [
                'correlation_id' => $correlationId,
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => $statusCode,
                'duration_ms' => $durationMs,
                'service' => 'frontend',
            ]);
        }

        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}
