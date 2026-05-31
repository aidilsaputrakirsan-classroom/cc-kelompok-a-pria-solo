<?php

namespace App\Http\Controllers;

use App\Services\RequestMetrics;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class HealthController extends Controller
{
    /** @var RequestMetrics */
    protected $metrics;

    public function __construct(RequestMetrics $metrics)
    {
        $this->metrics = $metrics;
    }

    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'service' => 'frontend',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function metrics(): JsonResponse
    {
        return response()->json($this->metrics->getMetrics());
    }
}
