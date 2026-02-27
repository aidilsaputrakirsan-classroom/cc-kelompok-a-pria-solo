<?php

namespace App\Admin\Controllers\AiControllers\API;

use OpenAdmin\Admin\Layout\Content;
use OpenAdmin\Admin\Admin;
use App\Http\Controllers\Controller;
use \App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ApiGatewayController extends Controller
{
    /**
     * Endpoint: GET /api/perusahaan
     * Return: array [{ id: int, nama_perusahaan: string }]
     */
    public function getAllCompanyNames(): JsonResponse
    {
        try {
            $companies = Company::select('id', 'name')
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'nama_perusahaan' => $c->name,
                ]);

            return response()->json($companies, 200);

        } catch (\Throwable $e) {
            Log::error('Error fetching companies: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Gagal mengambil data perusahaan',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}