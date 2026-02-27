<?php

namespace App\Admin\Controllers\AiControllers;

use OpenAdmin\Admin\Layout\Content;
use OpenAdmin\Admin\Admin;
use App\Http\Controllers\Controller;
use \App\Models\Ticket;
use \App\Models\GroundTruth;
use \App\Models\AdvanceReviewResult;
use \App\Models\TypoError;
use \App\Models\DateValidation;
use \App\Models\PriceValidation;
use \App\Models\BoundingBox;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ReviewSubmissionController extends Controller
{
    /**
     * Submit ground truth data untuk review (Async)
     * Route: POST /api/review/submit
     */
    public function submit(Request $request)
    {
        // CRITICAL: Set unlimited execution time di awal
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        $startTime = microtime(true);
        $requestId = uniqid('req_', true);

        Log::info('[SUBMIT] ===== REQUEST START =====', [
            'request_id' => $requestId,
            'ticket' => $request->input('ticket'),
            'timestamp' => now()->toDateTimeString(),
            'php_time_limit' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit')
        ]);

        // Validasi request
        $validated = $request->validate([
            'ticket' => 'required|string|regex:/^\d{6}-[A-Z]{3}-\d{3}$/',
            'ground_truth' => 'required|string'
        ]);

        $ticketNumber = $validated['ticket'];
        $groundTruthJson = trim($validated['ground_truth']);

        Log::info('[SUBMIT] JSON received', [
            'request_id' => $requestId,
            'ticket' => $ticketNumber,
            'json_length' => strlen($groundTruthJson)
        ]);

        // Validasi JSON
        $groundTruthData = json_decode($groundTruthJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('[SUBMIT] Invalid JSON', [
                'request_id' => $requestId,
                'error' => json_last_error_msg()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON format: ' . json_last_error_msg()
            ], 400);
        }

        try {
            // Cari ticket
            $ticket = Ticket::where('ticket_number', $ticketNumber)->firstOrFail();

            Log::info('[SUBMIT] Ticket found', [
                'request_id' => $requestId,
                'ticket_id' => $ticket->id
            ]);

            // Validasi ground truth exists (sekarang hanya 1 GT dengan doc_type = "Ground Truth")
            $groundTruth = GroundTruth::where('ticket_id', $ticket->id)
                ->where('doc_type', 'Ground Truth')
                ->first();

            if (!$groundTruth) {
                throw new \Exception('Ground truth not found for this ticket');
            }

            // Validasi bahwa semua doc_types yang di-submit ada di ground truth
            $requiredDocTypes = array_keys($groundTruthData);
            $availableDocTypes = array_keys($groundTruth->extracted_data);
            
            // Remove _metadata from available doc types
            $availableDocTypes = array_filter($availableDocTypes, fn($key) => $key !== '_metadata');
            
            $missingDocTypes = array_diff($requiredDocTypes, $availableDocTypes);
            
            if (!empty($missingDocTypes)) {
                throw new \Exception('Missing ground truth data for: ' . implode(', ', $missingDocTypes));
            }

            // Set initial status
            Cache::put("review_status_{$ticketNumber}", 'processing', 3600);
            Cache::put("review_request_id_{$ticketNumber}", $requestId, 3600);

            Log::info('[SUBMIT] Dispatching async process', [
                'request_id' => $requestId,
                'ticket' => $ticketNumber,
                'ground_truth_id' => $groundTruth->id
            ]);

            // Dispatch async job
            dispatch(function () use ($ticketNumber, $groundTruthJson, $ticket, $requestId) {
                $this->processReviewAsync($ticketNumber, $groundTruthJson, $ticket, $requestId);
            })->afterResponse();

            $processingTime = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('[SUBMIT] Request accepted', [
                'request_id' => $requestId,
                'processing_time_ms' => $processingTime
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Review sedang diproses',
                'data' => [
                    'ticket_number' => $ticketNumber,
                    'status' => 'processing',
                    'request_id' => $requestId
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('[SUBMIT] Ticket not found', [
                'request_id' => $requestId,
                'ticket' => $ticketNumber
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ticket tidak ditemukan'
            ], 404);

        } catch (\Exception $e) {
            Log::error('[SUBMIT] Submission failed', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process review secara async
     */
    private function processReviewAsync(string $ticketNumber, string $groundTruthJson, Ticket $ticket, string $requestId): void
    {
        // CRITICAL: Set unlimited execution time untuk async process
        set_time_limit(0);
        ini_set('max_execution_time', '0');

        $startTime = microtime(true);

        Log::info('[ASYNC] ===== ASYNC START =====', [
            'request_id' => $requestId,
            'ticket' => $ticketNumber,
            'timestamp' => now()->toDateTimeString(),
            'php_time_limit' => ini_get('max_execution_time')
        ]);

        try {
            // Kirim ke API
            $response = $this->sendToReviewAPI($ticketNumber, $groundTruthJson, $requestId);

            if (!$response['success']) {
                throw new \Exception('Review API error: ' . ($response['message'] ?? 'Unknown error'));
            }

            // Simpan hasil
            $this->saveReviewResults($ticket, $response['data'], $requestId);

            // Update status
            Cache::put("review_status_{$ticketNumber}", 'completed', 3600);
            Cache::put("review_redirect_{$ticketNumber}", route('projess.tickets.advance-reviews', $ticketNumber), 3600);

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::info('[ASYNC] ===== COMPLETED =====', [
                'request_id' => $requestId,
                'ticket' => $ticketNumber,
                'duration_ms' => $duration,
                'duration_minutes' => round($duration / 60000, 2)
            ]);

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            Log::error('[ASYNC] ===== FAILED =====', [
                'request_id' => $requestId,
                'ticket' => $ticketNumber,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'trace' => $e->getTraceAsString()
            ]);

            Cache::put("review_status_{$ticketNumber}", 'failed', 3600);
            Cache::put("review_error_{$ticketNumber}", $e->getMessage(), 3600);
        }
    }

    /**
     * Kirim ke API eksternal
     */
    private function sendToReviewAPI(string $ticketNumber, string $groundTruthJson, string $requestId): array
    {
        // CRITICAL: Set unlimited execution time
        set_time_limit(0);

        $apiStartTime = microtime(true);
        $endpoint = env('URL_VM_PYTHON') . '/review';

        Log::info('[API] ===== SENDING TO API =====', [
            'request_id' => $requestId,
            'ticket' => $ticketNumber,
            'endpoint' => $endpoint,
            'timeout_seconds' => 1800,
            'json_length' => strlen($groundTruthJson)
        ]);

        try {
            // 30 menit timeout
            $response = Http::timeout(1800)
                ->withOptions([
                    'connect_timeout' => 10,
                    'read_timeout' => 1800,
                    'http_errors' => false
                ])
                ->asForm()
                ->post($endpoint, [
                    'ticket' => $ticketNumber,
                    'ground_truth' => $groundTruthJson
                ]);

            $duration = round((microtime(true) - $apiStartTime) * 1000, 2);

            Log::info('[API] Response received', [
                'request_id' => $requestId,
                'status_code' => $response->status(),
                'duration_ms' => $duration,
                'duration_minutes' => round($duration / 60000, 2),
                'body_length' => strlen($response->body())
            ]);

            if (!$response->successful()) {
                Log::error('[API] Request failed', [
                    'request_id' => $requestId,
                    'status' => $response->status(),
                    'body_preview' => substr($response->body(), 0, 500)
                ]);

                return [
                    'success' => false,
                    'message' => 'API returned status ' . $response->status()
                ];
            }

            $data = $response->json();

            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                Log::error('[API] Invalid JSON response', [
                    'request_id' => $requestId,
                    'json_error' => json_last_error_msg(),
                    'body_preview' => substr($response->body(), 0, 500)
                ]);

                return [
                    'success' => false,
                    'message' => 'Invalid JSON response: ' . json_last_error_msg()
                ];
            }

            // Check for errors in response
            if (isset($data['error']) || (isset($data['status']) && $data['status'] === 'error')) {
                $errorMsg = $data['error'] ?? $data['message'] ?? 'Unknown error';

                Log::error('[API] API returned error', [
                    'request_id' => $requestId,
                    'error' => $errorMsg
                ]);

                return [
                    'success' => false,
                    'message' => $errorMsg
                ];
            }

            Log::info('[API] ===== SUCCESS =====', [
                'request_id' => $requestId,
                'has_basic_review' => isset($data['basic_review']),
                'has_advance_review' => isset($data['advance_review'])
            ]);

            return [
                'success' => true,
                'data' => $data
            ];

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $apiStartTime) * 1000, 2);

            Log::error('[API] Exception', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'duration_ms' => $duration,
                'duration_minutes' => round($duration / 60000, 2)
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Simpan hasil review
     */
    private function saveReviewResults(Ticket $ticket, array $reviewData, string $requestId): void
    {
        DB::beginTransaction();

        try {
            Log::info('[SAVE] Starting to save', [
                'request_id' => $requestId,
                'ticket_id' => $ticket->id
            ]);

            // Check for errors
            if (isset($reviewData['error'])) {
                throw new \Exception('API returned error: ' . $reviewData['error']);
            }

            // Save basic review
            if (!empty($reviewData['basic_review'])) {
                $this->saveBasicReview($ticket, $reviewData['basic_review'], $requestId);
            }

            // Save advance review (NEW STRUCTURE)
            if (!empty($reviewData['advance_review'])) {
                $this->saveAdvanceReview($ticket, $reviewData['advance_review'], $requestId);
            }

            DB::commit();

            Log::info('[SAVE] All results saved', [
                'request_id' => $requestId,
                'ticket_id' => $ticket->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('[SAVE] Failed - rolled back', [
                'request_id' => $requestId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Save Basic Review
     */
    private function saveBasicReview(Ticket $ticket, array $basicReview, string $requestId): void
    {
        foreach ($basicReview as $docType => $reviewData) {
            Log::info('[SAVE:BASIC] Processing doc', [
                'request_id' => $requestId,
                'doc_type' => $docType
            ]);

            if (!empty($reviewData['typo_checker'])) {
                $this->saveTypoErrors($ticket, $docType, $reviewData['typo_checker'], $requestId);
            }

            if (!empty($reviewData['price_validator'])) {
                $this->savePriceValidations($ticket, $docType, $reviewData['price_validator'], $requestId);
            }

            if (!empty($reviewData['date_validator'])) {
                $this->saveDateValidations($ticket, $docType, $reviewData['date_validator'], $requestId);
            }
        }
    }

    /**
     * Save Typo Errors
     */
    private function saveTypoErrors(Ticket $ticket, string $docType, array $typoData, string $requestId): void
    {
        foreach ($typoData as $typo) {
            $typoError = TypoError::create([
                'ticket_id' => $ticket->id,
                'doc_type' => $docType,
                'typo_word' => $typo['typo_word'] ?? null,
                'correction_word' => $typo['correction_word'] ?? null
            ]);

            if (!empty($typo['bbox'])) {
                $this->createBoundingBox(
                    $typoError,
                    'TypoError',
                    $typo['page'] ?? null,
                    $typo['typo_word'] ?? null,
                    $typo['bbox']
                );
            }
        }
    }

    /**
     * Save Price Validations
     */
    private function savePriceValidations(Ticket $ticket, string $docType, array $priceData, string $requestId): void
    {
        foreach ($priceData as $price) {
            // Skip if valid
            if (isset($price['is_valid']) && $price['is_valid'] === true) {
                continue;
            }

            $priceValidation = PriceValidation::create([
                'ticket_id' => $ticket->id,
                'doc_type' => $docType,
                'extracted_text' => $price['extracted_text'] ?? null,
                'correction' => $price['correct_terbilang'] ?? null
            ]);

            if (!empty($price['bounding_box']) && is_array($price['bounding_box'])) {
                foreach ($price['bounding_box'] as $bbox) {
                    $this->createBoundingBox(
                        $priceValidation,
                        'PriceValidation',
                        $bbox['page'] ?? null,
                        $bbox['word'] ?? null,
                        $bbox['bbox'] ?? []
                    );
                }
            }
        }
    }

    /**
     * Save Date Validations
     */
    private function saveDateValidations(Ticket $ticket, string $docType, array $dateData, string $requestId): void
    {
        foreach ($dateData as $date) {
            // Skip if valid
            if (isset($date['is_valid']) && $date['is_valid'] === true) {
                continue;
            }

            $dateValidation = DateValidation::create([
                'ticket_id' => $ticket->id,
                'doc_type' => $docType,
                'full_text' => $date['full_text'] ?? null,
                'tanggal_bracket' => $date['tanggal_bracket'] ?? null,
                'is_valid' => $date['is_valid'] ?? false,
                'correction' => $date['correction'] ?? null
            ]);

            if (!empty($date['bounding_box']) && is_array($date['bounding_box'])) {
                foreach ($date['bounding_box'] as $bbox) {
                    $this->createBoundingBox(
                        $dateValidation,
                        'DateValidation',
                        $bbox['page'] ?? null,
                        $bbox['word'] ?? null,
                        $bbox['bbox'] ?? []
                    );
                }
            }
        }
    }

    /**
     * Create Bounding Box
     */
    private function createBoundingBox($parent, string $boundableType, ?int $page, ?string $word, array $bboxData): void
    {
        BoundingBox::create([
            'boundable_id' => $parent->id,
            'boundable_type' => $boundableType,
            'page' => $page,
            'word' => $word,
            'x' => $bboxData['x'] ?? null,
            'y' => $bboxData['y'] ?? null,
            'width' => $bboxData['width'] ?? null,
            'height' => $bboxData['height'] ?? null
        ]);
    }

    /**
     * Save Advance Review (NEW STRUCTURE - Single GT for all doc_types)
     */
    private function saveAdvanceReview(Ticket $ticket, array $advanceReview, string $requestId): void
    {
        // Get THE SINGLE ground truth for this ticket (doc_type = "Ground Truth")
        $groundTruth = GroundTruth::where('ticket_id', $ticket->id)
            ->where('doc_type', 'Ground Truth')
            ->first();

        if (!$groundTruth) {
            Log::error('[SAVE:ADVANCE] Ground truth not found', [
                'request_id' => $requestId,
                'ticket_id' => $ticket->id,
                'expected_doc_type' => 'Ground Truth'
            ]);
            throw new \Exception('Ground truth not found for ticket');
        }

        Log::info('[SAVE:ADVANCE] Using single Ground Truth', [
            'request_id' => $requestId,
            'ground_truth_id' => $groundTruth->id,
            'doc_type' => $groundTruth->doc_type,
            'review_doc_types' => array_keys($advanceReview)
        ]);

        // Create MULTIPLE advance_review_results records
        // Each doc_type gets its own record, but ALL point to the SAME ground_truth_id
        foreach ($advanceReview as $docType => $reviewResult) {
            try {
                AdvanceReviewResult::create([
                    'ground_truth_id' => $groundTruth->id, // SAME ID for all doc_types
                    'doc_type' => $docType, // Actual doc_type (KL, BAST, SP, etc.)
                    'status' => $reviewResult['status'] ?? 'pending',
                    'error_message' => $reviewResult['error'] ?? null,
                    'review_data' => $reviewResult['review_result'] ?? null
                ]);

                Log::info('[SAVE:ADVANCE] Created review result', [
                    'request_id' => $requestId,
                    'ground_truth_id' => $groundTruth->id,
                    'doc_type' => $docType,
                    'status' => $reviewResult['status'] ?? 'pending'
                ]);

            } catch (\Exception $e) {
                Log::error('[SAVE:ADVANCE] Failed to save review for doc_type', [
                    'request_id' => $requestId,
                    'ground_truth_id' => $groundTruth->id,
                    'doc_type' => $docType,
                    'error' => $e->getMessage()
                ]);
                // Continue with other doc_types even if one fails
            }
        }

        Log::info('[SAVE:ADVANCE] All advance reviews saved', [
            'request_id' => $requestId,
            'ground_truth_id' => $groundTruth->id,
            'total_reviews_saved' => count($advanceReview)
        ]);
    }

    /**
     * Get review status
     */
    public function getStatus($ticketNumber)
    {
        try {
            $status = Cache::get("review_status_{$ticketNumber}", 'not_found');
            $requestId = Cache::get("review_request_id_{$ticketNumber}", 'unknown');

            $response = [
                'ticket_number' => $ticketNumber,
                'status' => $status,
                'request_id' => $requestId
            ];

            if ($status === 'completed') {
                $response['redirect_url'] = Cache::get("review_redirect_{$ticketNumber}");
            } elseif ($status === 'failed') {
                $response['error'] = Cache::get("review_error_{$ticketNumber}", 'Unknown error');
            } elseif ($status === 'processing') {
                $response['message'] = 'Review sedang diproses, mohon tunggu...';
            }

            return response()->json($response, 200, [
                'Content-Type' => 'application/json',
                'X-Content-Type-Options' => 'nosniff'
            ]);

        } catch (\Exception $e) {
            Log::error('[STATUS] Error', [
                'ticket' => $ticketNumber,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'ticket_number' => $ticketNumber,
                'status' => 'error',
                'error' => 'Failed to get status'
            ], 500, [
                'Content-Type' => 'application/json',
                'X-Content-Type-Options' => 'nosniff'
            ]);
        }
    }
}