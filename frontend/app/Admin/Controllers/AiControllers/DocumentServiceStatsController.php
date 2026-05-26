<?php

namespace App\Admin\Controllers\AiControllers;

use App\Http\Controllers\Controller;
use App\Services\DocumentServiceClient;
use Illuminate\Http\JsonResponse;

/**
 * Proxy ke FastAPI GET /stats — graceful degradation saat document-service down (Modul 13).
 */
class DocumentServiceStatsController extends Controller
{
    public function __construct(
        private readonly DocumentServiceClient $client = new DocumentServiceClient(),
    ) {
    }

    public function stats(): JsonResponse
    {
        $result = $this->client->getStats();

        if ($result['ok'] && $result['body'] !== null) {
            return response()->json($result['body'], $result['status']);
        }

        return response()->json(
            $this->client->degradedStatsPayload(),
            200
        );
    }
}
