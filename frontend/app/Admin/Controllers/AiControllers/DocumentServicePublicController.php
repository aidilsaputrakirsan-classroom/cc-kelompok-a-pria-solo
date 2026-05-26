<?php

namespace App\Admin\Controllers\AiControllers;

use App\Http\Controllers\Controller;
use App\Services\DocumentServiceClient;
use Illuminate\Http\JsonResponse;

/**
 * Proxy GET /public — no auth required on FastAPI; admin route may still require login.
 */
class DocumentServicePublicController extends Controller
{
    public function __construct(
        private readonly DocumentServiceClient $client = new DocumentServiceClient(),
    ) {
    }

    public function publicInfo(): JsonResponse
    {
        $result = $this->client->getPublic();

        if ($result['ok'] && $result['body'] !== null) {
            return response()->json($result['body'], $result['status']);
        }

        return response()->json([
            'service' => 'document-service',
            'status' => 'degraded',
            'features' => [
                'document_review' => false,
                'information_extraction' => false,
                'stats' => true,
                'health' => false,
            ],
            'degraded' => true,
            'error' => $result['error'] ?? 'Service temporarily unavailable',
            'code' => 'SERVICE_UNAVAILABLE',
            'retryable' => true,
        ], 503);
    }
}
