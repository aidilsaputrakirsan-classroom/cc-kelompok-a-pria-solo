<?php

namespace App\Admin\Controllers\AiControllers;

use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use App\Services\PDFService;
use App\Models\Ticket;
use App\Models\GroundTruth;
use App\Models\AdvanceReviewResult;
use Illuminate\Support\Facades\Log;

class AdvanceResultController extends Controller
{
    protected $pdfService;

    public function __construct(PDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Helper: Recursively clean whitespace from array values
     */
    private function cleanArrayValues($data)
    {
        if (is_string($data)) {
            // Trim leading/trailing whitespace from strings
            return trim($data);
        }
        
        if (is_array($data)) {
            $cleaned = [];
            foreach ($data as $key => $value) {
                $cleaned[$key] = $this->cleanArrayValues($value);
            }
            return $cleaned;
        }
        
        // Return non-string, non-array values as-is (numbers, null, etc.)
        return $data;
    }

    /**
     * Extract and filter issues from review_data/review_result structure
     * Only returns issues where is_valid === true
     * 
     * @param array|null $reviewData The review_data or review_result JSON from database
     * @param string $parentKey The parent issue key for nested issues (e.g., "issue_4")
     * @return array Array of issues with descriptions (only valid ones)
     */
    private function extractValidIssues($reviewData, $parentKey = '')
    {
        if (empty($reviewData) || !is_array($reviewData)) {
            return [];
        }

        $issues = [];

        foreach ($reviewData as $issueKey => $issueData) {
            if (!is_array($issueData)) {
                continue;
            }

            // Skip non-issue fields like "errors_count"
            if ($issueKey === 'errors_count' || $issueKey === 'quality_warnings') {
                continue;
            }

            // Build the full key path for nested issues
            $fullKey = $parentKey ? "{$parentKey}.{$issueKey}" : $issueKey;

            // Check if this is a simple issue with description and is_valid
            if (isset($issueData['description']) && isset($issueData['is_valid'])) {
                // Normalize is_valid to boolean (handle string "true"/"false")
                $isValid = $issueData['is_valid'];
                if (is_string($isValid)) {
                    $isValid = strtolower($isValid) === 'true';
                }
                $description = isset($issueData['description']) ? trim((string) $issueData['description']) : '';
                // Only include if is_valid === true AND has non-empty description
                if ($isValid === true && $description !== '') {
                    $issues[] = [
                        'label' => $this->formatIssueKeyToLabel($fullKey),
                        'value' => '',
                        'status' => 'success', // Valid issues are marked as success
                        'description' => $description,
                        'can_add_to_notes' => true,
                    ];
                }
            } else {
                // This might be a nested issue structure (e.g., issue_4.1, issue_4.2)
                // Recursively process nested issues, passing the current key as parent
                $nestedIssues = $this->extractValidIssues($issueData, $fullKey);
                $issues = array_merge($issues, $nestedIssues);
            }
        }

        return $issues;
    }

    /**
     * Format issue key to readable label
     * e.g., "issue_1" -> "Issue 1"
     * e.g., "issue_4.1" -> "Issue 4.1"
     */
    private function formatIssueKeyToLabel($issueKey)
    {
        // Handle nested keys (e.g., "issue_4.1")
        $parts = explode('.', $issueKey);
        $formattedParts = [];
        
        foreach ($parts as $part) {
            // Remove "issue_" prefix if present
            $cleaned = preg_replace('/^issue_/', '', $part);
            // Replace underscores with spaces
            $cleaned = str_replace('_', ' ', $cleaned);
            // Capitalize words
            $cleaned = ucwords($cleaned);
            $formattedParts[] = $cleaned;
        }
        
        $label = implode('.', $formattedParts);
        
        // Add "Issue" prefix if not present
        if (!str_starts_with(strtolower($label), 'issue')) {
            $label = 'Issue ' . $label;
        }
        
        return $label;
    }

    /**
     * Transform review_data/review_result JSON structure into stages format for frontend
     * Handles both review_data and review_result structures
     * Only includes issues where is_valid === true
     * 
     * @param array|null $reviewData The review_data or review_result JSON from database
     * @param string $docType The document type
     * @return array Transformed stages array
     */
    private function transformReviewDataToStages($reviewData, $docType = '')
    {
        if (empty($reviewData) || !is_array($reviewData)) {
            return [];
        }

        // Extract valid issues from the review data
        $validIssues = $this->extractValidIssues($reviewData);

        // If no valid issues found, return empty array
        if (empty($validIssues)) {
            return [];
        }

        // Group issues into a single stage for the document type
        $stageName = $docType ? "Review {$docType}" : "Review Notes";
        
        return [
            [
                'stage_id' => 1,
                'stage_name' => $stageName,
                'status' => 'success', // Since we only show valid issues
                'issues' => $validIssues,
            ]
        ];
    }

    /**
     * Format stage key to readable name
     * e.g., "stage_5_penandatangan" -> "Validasi Penandatangan"
     */
    private function formatStageKeyToName($stageKey)
    {
        // Remove "stage_" prefix and numbers
        $name = preg_replace('/^stage_\d+_/', '', $stageKey);
        // Replace underscores with spaces
        $name = str_replace('_', ' ', $name);
        // Capitalize words
        $name = ucwords($name);
        // Add "Validasi" prefix if not present
        if (!str_starts_with(strtolower($name), 'validasi')) {
            $name = 'Validasi ' . $name;
        }
        return $name;
    }

    /**
     * Determine stage status based on description content
     */
    private function determineStageStatus($description)
    {
        if (empty($description)) {
            return 'success';
        }

        $descriptionLower = strtolower($description);
        
        // Check for error indicators
        if (
            str_contains($descriptionLower, 'tidak ditemukan') ||
            str_contains($descriptionLower, 'missing') ||
            str_contains($descriptionLower, 'error') ||
            str_contains($descriptionLower, 'salah') ||
            str_contains($descriptionLower, 'tidak valid') ||
            str_contains($descriptionLower, 'tidak sesuai')
        ) {
            return 'error';
        }
        
        // Check for warning indicators
        if (
            str_contains($descriptionLower, 'perlu ditinjau') ||
            str_contains($descriptionLower, 'warning') ||
            str_contains($descriptionLower, 'perlu perbaikan')
        ) {
            return 'warning';
        }

        return 'success';
    }

    /**
     * Create error stages from error_message
     * 
     * @param string $errorMessage The error message from database
     * @param string $status The status (error/warning)
     * @return array Stages array with error information
     */
    private function createErrorStages($errorMessage, $status = 'error')
    {
        // Clean up error message (remove stack traces if present)
        $cleanMessage = $errorMessage;
        if (str_contains($cleanMessage, '#0')) {
            $cleanMessage = explode('#0', $cleanMessage)[0];
        }
        $cleanMessage = trim($cleanMessage);

        return [
            [
                'stage_id' => 1,
                'stage_name' => 'Error Review',
                'status' => $status,
                'issues' => [
                    [
                        'label' => 'Error Message',
                        'value' => '',
                        'status' => $status,
                        'description' => $cleanMessage,
                        'can_add_to_notes' => true, // Allow adding error messages to notes
                    ]
                ]
            ]
        ];
    }

    /**
     * Create "No review notes" stage when review_data and error_message are both empty
     * 
     * @return array Stages array with "No review notes" message
     */
    private function createNoReviewNotesStage()
    {
        return [
            [
                'stage_id' => 1,
                'stage_name' => 'Review Notes',
                'status' => 'info',
                'issues' => [
                    [
                        'label' => 'No Review Notes',
                        'value' => '',
                        'status' => 'info',
                        'description' => 'No review notes',
                        'can_add_to_notes' => false, // Flag to prevent adding to notes
                    ]
                ]
            ]
        ];
    }

    /**
     * Create "Tidak ada Review Data" stage when error_message exists
     * 
     * @return array Stages array with "Tidak ada Review Data" message
     */
    private function createNoReviewDataStage()
    {
        return [
            [
                'stage_id' => 1,
                'stage_name' => 'Review Notes',
                'status' => 'info',
                'issues' => [
                    [
                        'label' => 'Tidak ada Review Data',
                        'value' => '',
                        'status' => 'info',
                        'description' => 'Tidak ada Review Data',
                        'can_add_to_notes' => false, // Flag to prevent adding to notes
                    ]
                ]
            ]
        ];
    }

    /**
     * Menampilkan halaman hasil review (View Blade)
     */
    public function show(string $ticketNumber, string $docType, Content $content)
    {
        // Setup Ticket Simplified (Data Header Ringkas untuk Tampilan Awal)
        $ticketSimplified = (object) [
            'ticket_number' => $ticketNumber,
            'project_title' => 'PENGADAAN TENAGA ADMINISTRASI, DEVICE PENDUKUNG, DAN MANAGE SERVICE JARINGAN UNTUK PT CONCH SOUTH KALIMANTAN CEMENT',
            'company' => (object) ['name' => 'KOPERASI METROPOLITAN'],
            'groundTruth' => (object) ['dpp' => 'Rp 142.371.432,-']
        ];

        $documentName = $docType . '.pdf';
        // Generate PDF URL using route helper - Laravel will handle URL encoding automatically
        $prefix = config('admin.route.prefix', 'admin');
        $pdfUrl = route($prefix . '.ai.pdf.advance', [
            'ticketNumber' => $ticketNumber,
            'docType' => $docType,
            'filename' => $documentName
        ]);

        // Load Bootstrap Icons for consistent iconography
        Admin::css('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css');
        
        // Load custom CSS files (not OpenAdmin CSS)
        Admin::css(asset('css/advance-review-result.css'));
        Admin::css(asset('css/notes.css'));
        Admin::css(asset('css/templates/template-kontrak.css'));
        Admin::css(asset('css/templates/template-npk.css'));

        // Load custom JS files
        Admin::js(asset('js/notes.js'));
        Admin::js('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js');
        Admin::js(asset('js/form-templates/form-kontrak-readonly.js'));
        Admin::js(asset('js/form-templates/form-npk-readonly.js'));
        Admin::js(asset('js/advance-review-handler.js'));

        return $content
            ->title('Advance Review Result')
            ->description('Advance review result yang pernah dilakukan')
            ->body(view('advance-reviews.templates.advance-review-result', [
                'ticket' => $ticketSimplified,
                'docType' => $docType,
                'pdfUrl' => $pdfUrl,
                'documentName' => $documentName,
                'ticketNumber' => $ticketNumber,
                'isOpenAdmin' => true,
            ]));
    }

    /**
     * API: Get advance result data
     * Mengembalikan Data Ground Truth Lengkap & Review Stages
     * NOW: Returns ALL ground truths from ALL document types for the ticket
     */
    public function getAdvanceResultData(string $ticketNumber, string $docType)
    {
        try {
            // Try to get real data from database first
            $groundTruthData = null;
            $allGroundTruths = []; // ← NEW: Will store ALL ground truths by doc_type
            $reviewData = null;
            $advanceReviewResult = null;
            
            try {
                $ticket = Ticket::where('ticket_number', $ticketNumber)->first();
                if ($ticket) {
                    // ========================================
                    // FETCH ALL GROUND TRUTHS FOR THIS TICKET
                    // ========================================
                    $allGroundTruthRecords = GroundTruth::where('ticket_id', $ticket->id)
                        ->whereNotNull('extracted_data')
                        ->where('extracted_data', '!=', '[]')
                        ->get();
                    
                    // Organize ALL ground truths by doc_type
                    foreach ($allGroundTruthRecords as $record) {
                        $cleanedData = $this->cleanArrayValues($record->extracted_data);
                        $allGroundTruths[$record->doc_type] = $cleanedData;
                    }
                    
                    // Set ground truth data to ALL ground truths
                    if (!empty($allGroundTruths)) {
                        $groundTruthData = $allGroundTruths;
                        Log::info('Using ALL ground truths from database', [
                            'ticket' => $ticketNumber,
                            'doc_types' => array_keys($allGroundTruths),
                            'total_count' => count($allGroundTruths)
                        ]);
                    }
                    
                    // Get review_data from advance_review_results table
                    $advanceReviewResult = AdvanceReviewResult::whereHas('groundTruth', function ($query) use ($ticket) {
                        $query->where('ticket_id', $ticket->id);
                    })
                    ->where('doc_type', $docType)
                    ->first();
                    
                    if ($advanceReviewResult) {
                        if ($advanceReviewResult->review_data && !empty($advanceReviewResult->review_data)) {
                            $reviewData = $advanceReviewResult->review_data;
                            Log::info('Using real review_data from database', [
                                'ticket' => $ticketNumber,
                                'doc_type' => $docType
                            ]);
                        }
                        // Note: If review_data is empty, we'll check error_message in stages logic
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to retrieve data from database', [
                    'ticket' => $ticketNumber,
                    'doc_type' => $docType,
                    'error' => $e->getMessage()
                ]);
            }

            // Always use data from database - no dummy data fallback
            // If no ground truth found, return empty array or null
            if (!$groundTruthData) {
                $groundTruthData = [];
                Log::warning('No ground truth data found in database', [
                    'ticket' => $ticketNumber,
                    'doc_type' => $docType
                ]);
            }

            // REMOVED: Dummy data fallback - now always using database data
            /*
            if (!$groundTruthData) {
                // ==========================================
                // 1. FULL DUMMY GROUND TRUTH (DATA LENGKAP)
                // ==========================================
                $groundTruthData = [
                    // --- General Contract Info (KL/SP/NOPES/WO) ---
                    "judul_project" => "PENGADAAN TENAGA ADMINISTRASI, DEVICE PENDUKUNG, DAN MANAGE SERVICE JARINGAN UNTUK PT CONCH SOUTH KALIMANTAN CEMENT",
                    "nama_pelanggan" => "PT CONCH SOUTH KALIMANTAN CEMENT",
                    "nomor_surat_utama" => "K.TEL.1644/HK.810/TR6-R600/2024",
                    "nomor_surat_lainnya" => "0035/KOMET/II/2024",
                    "tanggal_kontrak" => "11-02-2024",
                    "delivery" => "11-02-2024",
                    "delivery_date" => "11-02-2024",
                    "metode_pembayaran" => "Bulanan",
                    "terms_of_payment" => "Back to Back",
                    "skema_bisnis" => "a. Tenaga Administrasi dan Manage Service Jaringan Sewa Murni. b. Device Pendukung Beli Putus.",
                    "slg" => "98%",

                    // --- Financial & Duration ---
                    "dpp_raw" => 142371432,
                    "harga_satuan_raw" => 11864286,
                    "detail_rekening" => [
                        "nama_bank" => "Bank Mandiri",
                        "nomor_rekening" => "123-000-4147.312",
                        "atas_nama" => "Koperasi Metropolitan",
                        "kantor_cabang" => null
                    ],
                    "jangka_waktu" => [
                        "duration" => "12 Bulan",
                        "start_date" => "11-02-2024",
                        "end_date" => "10-02-2025"
                    ],

                    // --- Pejabat Penanda Tangan (Untuk validasi footer) ---
                    "pejabat_penanda_tangan" => [
                        "bapl" => [
                            "mitra" => "Ketua atau Pejabat Pengganti yang ditunjuk",
                            "telkom" => "Mgr. Project Operation & Quality Assurance atau Pejabat Pengganti yang ditunjuk"
                        ],
                        "bard" => [
                            "mitra" => "Ketua atau Pejabat Pengganti yang ditunjuk",
                            "telkom" => "Mgr. Project Operation & Quality Assurance atau Pejabat Pengganti yang ditunjuk"
                        ],
                        "bast" => [
                            "mitra" => "Ketua atau Pejabat Pengganti yang ditunjuk",
                            "telkom" => "Mgr. Project Operation & Quality Assurance atau Pejabat Pengganti yang ditunjuk"
                        ],
                        "baut" => [
                            "mitra" => "Ketua atau Pejabat Pengganti yang ditunjuk",
                            "telkom" => "Mgr. Project Operation & Quality Assurance atau Pejabat Pengganti yang ditunjuk"
                        ]
                    ],

                    // --- Rujukan / Referensi ---
                    "rujukan" => [
                        1 => "Surat Penetapan Koperasi Metropolitan Sebagai Partner List Telkom dalam Pemenuhan Layanan Solusi Jaringan dan Device Services untuk Corporate Customer Telkom Nomor: Tel.31/HK.000/SDA-A4000000/2021 Tanggal 04 Agustus 2021.",
                        2 => "Berita Acara Klarifikasi Negosiasi Pengadaan Tenaga Administrasi, Device Pendukung, dan Manage Service Jaringan untuk PT Conch South Kalimantan Cement tanggal 22 Januari 2024.",
                        3 => "Surat Penetapan Calon Mitra Pelaksana Nomor: TEL.0884/LG.270/TR6-R604/2024 tanggal 23 Januari 2024 perihal Penetapan Calon Mitra Pelaksana Pengadaan Tenaga Administrasi, Device Pendukung, dan Manage Service Jaringan untuk PT Conch South Kalimantan Cement.",
                        4 => "Surat Kesanggupan Nomor: 0110/KOMET/SK/I/2024 tanggal 24 Januari 2024 perihal Surat Kesanggupan."
                    ],

                    // --- DATA SPESIFIK DOKUMEN LAINNYA (NESTED) ---

                    // Data NPK
                    "NPK" => [
                        "SID" => "2074928759",
                        "nilai_satuan_usage" => [
                            "Februari 2024" => 4824877,
                            "Maret 2024" => 7364286,
                            "April 2024" => 7364286,
                            "Mei 2024" => 7364286,
                            "Juni 2024" => 7364286,
                            "Juli 2024" => 7364286,
                            "Agustus 2024" => 7364286
                        ],
                        "prorate" => [
                            ["value" => 19, "period" => "Februari 2024"]
                        ]
                    ],

                    // Data BAST
                    "BAST" => [
                        "nomor" => [
                            "mitra" => "0086/KOMET/II/2024",
                            "telkom" => "TEL.1645/LG.320/TR6-R602/2024"
                        ],
                        "tanggal_bast" => "11-02-2024"
                    ],

                    // Data BAUT
                    "BAUT" => [
                        "tanggal_baut" => "11-02-2024"
                    ],

                    // Data BARD
                    "BARD" => [
                        "tanggal_bard" => "11-02-2024"
                    ]
                ];
            }
            */

            // ==========================================
            // 2. REVIEW STAGES LOGIC (Extract issues from review_data/review_result)
            // ==========================================
            $stages = [];
            $docTypeUpper = strtoupper($docType);

            // Check if database record exists
            if ($advanceReviewResult) {
                // Check for error_message first - if exists, return "Tidak ada Review Data"
                if ($advanceReviewResult->error_message) {
                    $stages = $this->createNoReviewDataStage();
                    Log::info('Error message found in database - returning "Tidak ada Review Data"', [
                        'ticket' => $ticketNumber,
                        'doc_type' => $docType,
                        'status' => $advanceReviewResult->status
                    ]);
                } elseif (!empty($reviewData) && is_array($reviewData)) {
                    // Handle new JSON structure
                    $issuesData = null;
                    
                    // Case 1: Check if review_data contains the entire advance_review structure
                    // (advance_review[doc_type].review_data or advance_review[doc_type].review_result)
                    if (isset($reviewData['advance_review']) && is_array($reviewData['advance_review'])) {
                        $advanceReview = $reviewData['advance_review'];
                        // Extract the specific doc_type from advance_review
                        if (isset($advanceReview[$docTypeUpper]) && is_array($advanceReview[$docTypeUpper])) {
                            $docReview = $advanceReview[$docTypeUpper];
                            // Check for review_data or review_result
                            if (isset($docReview['review_data']) && is_array($docReview['review_data'])) {
                                $issuesData = $docReview['review_data'];
                            } elseif (isset($docReview['review_result']) && is_array($docReview['review_result'])) {
                                $issuesData = $docReview['review_result'];
                            }
                        }
                        // Also try with original case doc_type
                        if (!$issuesData && isset($advanceReview[$docType]) && is_array($advanceReview[$docType])) {
                            $docReview = $advanceReview[$docType];
                            if (isset($docReview['review_data']) && is_array($docReview['review_data'])) {
                                $issuesData = $docReview['review_data'];
                            } elseif (isset($docReview['review_result']) && is_array($docReview['review_result'])) {
                                $issuesData = $docReview['review_result'];
                            }
                        }
                    }
                    // Case 2: Check if review_data has nested review_data or review_result directly
                    elseif (isset($reviewData['review_data']) && is_array($reviewData['review_data'])) {
                        $issuesData = $reviewData['review_data'];
                    } elseif (isset($reviewData['review_result']) && is_array($reviewData['review_result'])) {
                        $issuesData = $reviewData['review_result'];
                    }
                    // Case 3: Assume review_data is the issues structure directly
                    else {
                        // Check if it has issue keys (issue_1, issue_2, etc.)
                        $hasIssueKeys = false;
                        foreach (array_keys($reviewData) as $key) {
                            if (str_starts_with($key, 'issue_')) {
                                $hasIssueKeys = true;
                                break;
                            }
                        }
                        
                        if ($hasIssueKeys) {
                            $issuesData = $reviewData;
                        }
                    }
                    
                    // Transform issues data to stages format (only includes is_valid === true)
                    if ($issuesData) {
                        $stages = $this->transformReviewDataToStages($issuesData, $docType);
                        
                        // If no valid issues found (all is_valid === false), show "No review notes"
                        if (empty($stages)) {
                            $stages = $this->createNoReviewNotesStage();
                            Log::info('Review data exists but no valid issues found (all is_valid === false)', [
                                'ticket' => $ticketNumber,
                                'doc_type' => $docType
                            ]);
                        } else {
                            Log::info('Using transformed review_data/review_result from database', [
                                'ticket' => $ticketNumber,
                                'doc_type' => $docType,
                                'stages_count' => count($stages),
                                'total_issues' => !empty($stages[0]['issues']) ? count($stages[0]['issues']) : 0
                            ]);
                        }
                    } else {
                        // No valid issues structure found
                        $stages = $this->createNoReviewNotesStage();
                        Log::info('Review data exists but no valid issues structure found', [
                            'ticket' => $ticketNumber,
                            'doc_type' => $docType,
                            'review_data_keys' => array_keys($reviewData)
                        ]);
                    }
                } else {
                    // Record exists but no review_data and no error_message - show "No review notes"
                    $stages = $this->createNoReviewNotesStage();
                    Log::info('Database record exists but no review_data or error_message - showing "No review notes"', [
                        'ticket' => $ticketNumber,
                        'doc_type' => $docType,
                        'status' => $advanceReviewResult->status
                    ]);
                }
            } else {
                // No database record exists - show "No review notes"
                $stages = $this->createNoReviewNotesStage();
                Log::info('No database record found - showing "No review notes"', [
                    'ticket' => $ticketNumber,
                    'doc_type' => $docType
                ]);
            }

            // Return JSON Response
            return response()->json([
                'doc_type' => $docType,
                'ground_truth' => $groundTruthData, // Data sudah dibersihkan dari whitespace
                'review_stages' => $stages,
                'status' => 'completed',
                'error_message' => null,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting advance result data', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper: Serve PDF File
     */
    public function servePDF(string $ticketNumber, string $docType, string $filename)
    {
        $expectedFilename = "{$docType}.pdf";
        if ($filename !== $expectedFilename) {
            abort(403, 'Filename does not match document type');
        }
        return $this->pdfService->serveAdvanceReviewPDF($ticketNumber, $docType, $filename);
    }
}
