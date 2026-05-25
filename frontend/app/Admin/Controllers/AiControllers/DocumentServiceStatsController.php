<?php

namespace App\Admin\Controllers\AiControllers;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;

/**
 * Proxy ke FastAPI GET /stats — hanya untuk admin yang sudah login (OpenAdmin).
 */
class DocumentServiceStatsController extends Controller
{
    public function stats(): JsonResponse
    {
        $base = rtrim((string) env('URL_VM_PYTHON', 'http://127.0.0.1:8001'), '/');
        $url = $base.'/stats';

        try {
            $client = new Client(['timeout' => (int) env('BACKEND_TIMEOUT', 30)]);
            $response = $client->get($url);
            $body = json_decode((string) $response->getBody(), true);

            return response()->json($body ?? [], $response->getStatusCode());
        } catch (GuzzleException $e) {
            $status = method_exists($e, 'getCode') && $e->getCode() === 504 ? 504 : 503;

            return response()->json([
                'error' => 'Service temporarily unavailable',
                'detail' => $e->getMessage(),
            ], $status >= 400 ? $status : 503);
        }
    }
}
