<?php

namespace App\Admin\Controllers\AiControllers;

use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use \App\Models\Ticket;
use \App\Models\AdvanceReviewResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon; // Pastikan import ini ada untuk dummy date

class AdvanceReviewOverviewController extends Controller
{
    /**
     * Show advance review overview page
     * MODIFIED: Shows cards based on uploaded files in storage
     */
    public function showReviews($ticketNumber, Content $content)
    {
        // Define custom order untuk sorting
        $customOrder = [
            'PR',
            'PO',
            'GR',
            'CL OBL',
            'NPK',
            'KKP',
            'SPB',
            'INVOICE',
            'KUITANSI',
            'FAKTUR PAJAK',
            'ENOFA',
            'BEBAS PPH',
            'BAPLA',
            'BAST',
            'BAUT',
            'BARD',
            'LPL',
            'WO',
            'P8',
            'SP',
            'KL',
            'KB',
            'BASO',
            'BA SPLITTING',
            'CHECKLIST OBL'
        ];

        // ==========================================
        // GET TICKET DATA
        // ==========================================
        $ticket = Ticket::where('ticket_number', $ticketNumber)
            ->select(['tickets.id', 'tickets.ticket_number', 'tickets.project_title', 'tickets.company_id'])
            ->with([
                'company:id,name',
                'groundTruths' => function ($query) {
                    $query->select('id', 'ticket_id', 'doc_type', 'extracted_data')
                        ->whereIn('doc_type', ['NOPES', 'KL', 'SP', 'WO'])
                        ->oldest('id')
                        ->limit(1);
                },
            ])
            ->first();

        // Get DPP dari ground truth (NOPES/KL/SP/WO)
        $dppFormatted = $ticket ? $this->getFormattedDpp($ticket) : null;

        // Create simplified ticket object
        $ticketSimplified = (object) [
            'ticket_number' => $ticketNumber,
            'project_title' => $ticket ? ($ticket->project_title ?? 'N/A') : 'N/A',
            'company' => (object) [
                'name' => $ticket && $ticket->company ? $ticket->company->name : null
            ],
            'groundTruth' => (object) [
                'dpp' => $dppFormatted ?? null
            ]
        ];

        // ==========================================
        // SCAN STORAGE FOR UPLOADED FILES
        // ==========================================
        $uploadedFiles = $this->getUploadedFilesFromStorage($ticketNumber);
        
        // ==========================================
        // GET REVIEW RESULTS FROM DATABASE
        // ==========================================
        $advanceReviewResults = collect([]);
        if ($ticket) {
            $advanceReviewResults = AdvanceReviewResult::whereHas('groundTruth', function ($query) use ($ticket) {
                $query->where('ticket_id', $ticket->id);
            })
                ->select(['id', 'ground_truth_id', 'doc_type', 'status', 'created_at', 'review_data'])
                ->get()
                ->keyBy('doc_type'); // Key by doc_type for easy lookup
        }

        // ==========================================
        // BUILD REVIEW RESULTS COLLECTION
        // ==========================================
        $reviewResults = collect([]);

        // A. Add Basic Review Card (always first)
        if ($ticket) {
            $basicReviewCounts = $this->getBasicReviewCounts($ticket->id);
        } else {
            $basicReviewCounts = [
                'typo_count' => 0,
                'date_count' => 0,
                'price_count' => 0,
                'total_errors' => 0
            ];
        }
        
        $reviewResults->push((object) [
            'doc_type' => 'Validasi Teks, Tanggal, dan Harga',
            'status' => $this->determineBasicReviewStatus($basicReviewCounts),
            'created_at' => Carbon::now(),
            'is_basic_review' => true,
            'typo_count' => $basicReviewCounts['typo_count'],
            'date_count' => $basicReviewCounts['date_count'],
            'price_count' => $basicReviewCounts['price_count'],
        ]);

        // B. Add cards for each uploaded file (excluding ground truth files)
        // Ground truth document types: NOPES, KL, SP, WO
        $groundTruthDocTypes = ['NOPES', 'KL', 'SP', 'WO', 'KONTRAK LAYANAN', 'NOTA PESANAN', 'SURAT PESANAN', 'WORK ORDER'];
        
        foreach ($uploadedFiles as $fileInfo) {
            $docType = $fileInfo['doc_type'];
            
            // Skip ground truth files - they should not appear as cards
            if (in_array($docType, $groundTruthDocTypes, true)) {
                Log::debug('Skipping ground truth file from cards', [
                    'ticket' => $ticketNumber,
                    'doc_type' => $docType,
                    'filename' => $fileInfo['filename']
                ]);
                continue;
            }
            
            $filename = $fileInfo['filename'];
            $filePath = $fileInfo['storage_path'];
            $fileModifiedTime = $fileInfo['modified_time'] ?? Carbon::now();

            // Get review result from database if exists
            $dbReviewResult = $advanceReviewResults->get($docType);

            // Get counts from database if ticket exists
            $counts = ['typo_count' => 0, 'date_count' => 0, 'price_count' => 0, 'total_errors' => 0];
            if ($ticket) {
                $counts = $this->getDocumentTypeCounts($ticket->id, $docType);
            }

            // Determine status
            $status = 'pending';
            if ($dbReviewResult) {
                $status = $dbReviewResult->status ?? 'pending';
            } elseif ($ticket && $counts['total_errors'] > 0) {
                $status = 'error';
            } else {
                $status = 'completed';
            }

            $reviewResults->push((object) [
                'id' => $dbReviewResult->id ?? null,
                'ground_truth_id' => $dbReviewResult->ground_truth_id ?? null,
                'doc_type' => $docType,
                'status' => $status,
                'created_at' => $dbReviewResult->created_at ?? $fileModifiedTime,
                'is_basic_review' => false,
                'typo_count' => $counts['typo_count'],
                'date_count' => $counts['date_count'],
                'price_count' => $counts['price_count'],
                'review_data' => $dbReviewResult->review_data ?? null,
                'filename' => $filename, // Store original filename
            ]);
        }

        // Sort by custom order
        $reviewResults = $reviewResults->sortBy(function ($result) use ($customOrder) {
            if ($result->is_basic_review ?? false) {
                return -1; // Basic review always first
            }
            $position = array_search($result->doc_type, $customOrder);
            return $position !== false ? $position : 999;
        })->values();

        // Load custom CSS files (not OpenAdmin CSS)
        Admin::css(asset('css/notes.css'));
        Admin::css(asset('css/review-overview.css'));

        // Load custom JS files
        Admin::js(asset('js/notes.js'));
        Admin::js(asset('js/review-overview.js'));
        Admin::js(asset('js/pairing-documents-modal.js'));

        // Return View
        return $content
            ->title('Overview Review')
            ->description('Overview review yang pernah dilakukan')
            ->body(view('advance-reviews.templates.review-overview', [
                'ticket' => $ticketSimplified,
                'reviewResults' => $reviewResults,
                'isOpenAdmin' => true,
            ]));
    }

    /**
     * API: Get overview data (untuk AJAX jika diperlukan)
     * MODIFIED: MENGGUNAKAN DUMMY DATA
     */
    public function getOverviewData($ticketNumber)
    {
        try {
            $customOrder = [
                'PR',
                'PO',
                'GR',
                'CL OBL',
                'NPK',
                'KKP',
                'SPB',
                'INVOICE',
                'KUITANSI',
                'FAKTUR PAJAK',
                'ENOFA',
                'BEBAS PPH',
                'BAPLA',
                'BAST',
                'BAUT',
                'BARD',
                'LPL'
            ];

            // ==========================================
            // DATABASE LOGIC (COMMENTED OUT)
            // ==========================================
            /*
            $ticket = Ticket::where('ticket_number', $ticketNumber)
                ->select(['tickets.id', 'tickets.ticket_number', 'tickets.project_title', 'tickets.company_id'])
                ->with([
                    'company:id,name',
                    'groundTruths' => function ($query) {
                        $query->select('id', 'ticket_id', 'doc_type', 'extracted_data')
                            ->whereIn('doc_type', ['NOPES', 'KL', 'SP', 'WO'])
                            ->oldest('id')
                            ->limit(1);
                    }
                ])
                ->first();

            if (!$ticket) {
                return response()->json(['message' => 'Ticket not found'], 404);
            }

            $dppFormatted = $this->getFormattedDpp($ticket);

            $advanceReviewResults = AdvanceReviewResult::whereHas('groundTruth', function ($query) use ($ticket) {
                $query->where('ticket_id', $ticket->id);
            })
                ->select(['id', 'ground_truth_id', 'doc_type', 'status', 'created_at'])
                ->get()
                ->sortBy(function ($result) use ($customOrder) {
                    $position = array_search($result->doc_type, $customOrder);
                    return $position !== false ? $position : 999;
                })
                ->values();

            $basicReviewData = $this->getBasicReviewCounts($ticket->id);

            $totalDocs = $advanceReviewResults->count() + 1;
            $completedDocs = $advanceReviewResults->where('status', 'completed')->count();
            if ($basicReviewData['total_errors'] === 0) {
                $completedDocs++;
            }
            $errorDocs = $totalDocs - $completedDocs;

            $requiredDocs = ['PR', 'PO', 'GR', 'NPK', 'KWITANSI', 'BAUT', 'FAKTUR PAJAK', 'KB', 'BA SPLIT'];
            $existingDocs = $advanceReviewResults->pluck('doc_type')->toArray();
            $missingDocs = array_diff($requiredDocs, $existingDocs);

            // ... Logic array building asli ...
            */

            // ==========================================
            // DUMMY DATA LOGIC (ACTIVE)
            // ==========================================

            // Generate Review Results Array
            $reviewResultsArray = [];

            // 1. Basic Review (Card 1)
            $reviewResultsArray[] = [
                'doc_type' => 'Validasi Teks, Tanggal, dan Harga',
                'status' => 'completed',
                'is_basic_review' => true,
                'typo_count' => 0,
                'date_count' => 0,
                'price_count' => 0,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'formatted_date' => Carbon::now()->format('d M Y')
            ];

            // 2. Other Documents (Card 2 dst - Lengkap)
            foreach ($customOrder as $doc) {
                $reviewResultsArray[] = [
                    'doc_type' => $doc,
                    'status' => 'completed',
                    'is_basic_review' => false,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'formatted_date' => Carbon::now()->format('d M Y')
                ];
            }

            // Statistics (Perfect Score)
            $totalDocs = count($reviewResultsArray); // Basic + Documents
            $completedDocs = $totalDocs;
            $errorDocs = 0;
            $missingDocs = []; // Kosong karena semua ada

            return response()->json([
                'ticket' => [
                    'ticket_number' => $ticketNumber,
                    'project_title' => 'PENGADAAN BARANG DUMMY (FE DEV)',
                    'company_name' => 'PT. DUMMY CORP',
                    'contract_value' => 'Rp 5.250.000.000,-'
                ],
                'review_results' => $reviewResultsArray,
                'statistics' => [
                    'total_documents' => $totalDocs,
                    'completed' => $completedDocs,
                    'errors' => $errorDocs,
                    'completion_rate' => 100
                ],
                'missing_documents' => [
                    'count' => 0,
                    'list' => []
                ]
            ]);

        } catch (\Exception $e) {
            // Keep error handling active just in case dummy logic fails
            Log::error('Error in getOverviewData', [
                'ticket_number' => $ticketNumber,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to load overview data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // --- HELPER FUNCTIONS (Dibiarkan saja, tidak dipanggil saat mode Dummy) ---

    /**
     * Get formatted DPP from ground truth (NOPES, KL, SP, WO)
     * Returns: "Rp 1.000,-" format
     */
    private function getFormattedDpp($ticket)
    {
        $groundTruth = $ticket->groundTruths->first();

        if (!$groundTruth) {
            return null;
        }

        try {
            $extractedData = $groundTruth->extracted_data;

            // If it's already an array
            if (is_array($extractedData)) {
                $dppRaw = data_get($extractedData, 'dpp_raw');
            }
            // If it's a string, decode it
            else if (is_string($extractedData)) {
                $cleanedJson = $this->cleanJsonString($extractedData);
                $decoded = json_decode($cleanedJson, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $dppRaw = data_get($decoded, 'dpp_raw');
                } else {
                    Log::warning('Failed to decode extracted_data JSON for DPP', [
                        'ground_truth_id' => $groundTruth->id,
                        'doc_type' => $groundTruth->doc_type,
                        'error' => json_last_error_msg()
                    ]);
                    return null;
                }
            } else {
                return null;
            }

            // Format DPP: 101306604 → "Rp 101.306.604,-"
            if ($dppRaw && is_numeric($dppRaw)) {
                return $this->formatRupiah($dppRaw);
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Error getting DPP from ground truth', [
                'ground_truth_id' => $groundTruth->id,
                'doc_type' => $groundTruth->doc_type,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function formatRupiah($number)
    {
        return 'Rp ' . number_format($number, 0, ',', '.') . ',-';
    }

    /**
     * Get Basic Review counts aggregated across ALL document types for a ticket
     * Basic Review validates typo, date, and price errors across all documents
     * 
     * @param int $ticketId
     * @return array
     */
    private function getBasicReviewCounts($ticketId)
    {
        try {
            // Aggregate counts from ALL document types (no doc_type filter)
            $typoCount = \App\Models\TypoError::where('ticket_id', $ticketId)
                ->count();

            $dateCount = \App\Models\DateValidation::where('ticket_id', $ticketId)
                ->where('is_valid', false)
                ->count();

            $priceCount = \App\Models\PriceValidation::where('ticket_id', $ticketId)
                ->count();

            return [
                'typo_count' => $typoCount,
                'date_count' => $dateCount,
                'price_count' => $priceCount,
                'total_errors' => $typoCount + $dateCount + $priceCount
            ];
        } catch (\Exception $e) {
            Log::error('Error getting basic review counts', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);
            
            return ['typo_count' => 0, 'date_count' => 0, 'price_count' => 0, 'total_errors' => 0];
        }
    }

    /**
     * Get error counts for a specific document type
     * 
     * @param int $ticketId
     * @param string $docType
     * @return array
     */
    private function getDocumentTypeCounts($ticketId, $docType)
    {
        try {
            $typoCount = \App\Models\TypoError::where('ticket_id', $ticketId)
                ->where('doc_type', $docType)
                ->count();

            $dateCount = \App\Models\DateValidation::where('ticket_id', $ticketId)
                ->where('doc_type', $docType)
                ->where('is_valid', false)
                ->count();

            $priceCount = \App\Models\PriceValidation::where('ticket_id', $ticketId)
                ->where('doc_type', $docType)
                ->count();

            return [
                'typo_count' => $typoCount,
                'date_count' => $dateCount,
                'price_count' => $priceCount,
                'total_errors' => $typoCount + $dateCount + $priceCount
            ];
        } catch (\Exception $e) {
            Log::error('Error getting document type counts', [
                'ticket_id' => $ticketId,
                'doc_type' => $docType,
                'error' => $e->getMessage()
            ]);
            
            return ['typo_count' => 0, 'date_count' => 0, 'price_count' => 0, 'total_errors' => 0];
        }
    }

    private function determineBasicReviewStatus($basicReviewData)
    {
        return $basicReviewData['total_errors'] > 0 ? 'error' : 'completed';
    }

    private function cleanJsonString($jsonString)
    {
        $jsonString = preg_replace('/^[\x00-\x1F\x80-\xFF]+/', '', $jsonString);
        $jsonString = trim($jsonString);
        return $jsonString;
    }

    /**
     * Get uploaded files from storage for a ticket
     * Scans storage/app/public/advance-review/{ticketNumber}/ directory
     * Maps filenames to document types
     * 
     * @param string $ticketNumber
     * @return array Array of file info with doc_type, filename, storage_path, modified_time
     */
    private function getUploadedFilesFromStorage(string $ticketNumber): array
    {
        $disk = Storage::disk('public');
        $directoryPath = "advance-review/{$ticketNumber}";
        
        $uploadedFiles = [];

        // Check if directory exists
        if (!$disk->exists($directoryPath)) {
            Log::info('Storage directory does not exist', [
                'ticket' => $ticketNumber,
                'directory_path' => $directoryPath
            ]);
            return [];
        }

        // Get all files in the directory
        $allFiles = $disk->files($directoryPath);
        
        // Filter only PDF files
        $pdfFiles = array_filter($allFiles, function ($file) {
            return strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf';
        });

        Log::info('Scanning storage for uploaded files', [
            'ticket' => $ticketNumber,
            'total_files' => count($allFiles),
            'pdf_files_count' => count($pdfFiles)
        ]);

        // Document type mapping patterns
        // Format: 'DOC_TYPE' => [patterns to match in filename]
        $docTypePatterns = [
            'PR' => ['PR', 'PURCHASE REQUEST', 'PURCHASE_REQUEST'],
            'PO' => ['PO', 'PURCHASE ORDER', 'PURCHASE_ORDER'],
            'GR' => ['GR', 'GOODS RECEIPT', 'GOODS_RECEIPT'],
            'CL OBL' => ['CL OBL', 'CHECKLIST OBL', 'CHECKLIST_OBL'],
            'NPK' => ['NPK'],
            'KKP' => ['KKP'],
            'SPB' => ['SPB'],
            'INVOICE' => ['INVOICE', 'INV'],
            'KUITANSI' => ['KUITANSI', 'KWITANSI'],
            'FAKTUR PAJAK' => ['FAKTUR PAJAK', 'FAKTUR_PAJAK', 'FAKTUR'],
            'ENOFA' => ['ENOFA'],
            'BEBAS PPH' => ['BEBAS PPH', 'BEBAS_PPH'],
            'BAPLA' => ['BAPLA'],
            'BAST' => ['BAST'],
            'BAUT' => ['BAUT'],
            'BARD' => ['BARD'],
            'LPL' => ['LPL'],
            'WO' => ['WO', 'WORK ORDER', 'WORK_ORDER'],
            'P8' => ['P8'],
            'SP' => ['SP', 'SURAT PESANAN', 'SURAT_PESANAN'],
            'KL' => ['KL', 'KONTRAK LAYANAN', 'KONTRAK_LAYANAN'],
            'KB' => ['KB'],
            'BASO' => ['BASO'],
            'BA SPLITTING' => ['BA SPLITTING', 'BA_SPLITTING', 'SPLITTING'],
            'CHECKLIST OBL' => ['CHECKLIST OBL', 'CHECKLIST_OBL'],
        ];

        // Process each PDF file
        foreach ($pdfFiles as $filePath) {
            $filename = basename($filePath);
            $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
            $filenameUpper = strtoupper($filenameWithoutExt);
            
            // Remove common prefixes like "1. ", "2. ", etc.
            $cleanFilename = preg_replace('/^\d+\.?\s*/', '', $filenameUpper);
            
            // Try to match filename to a document type
            $matchedDocType = null;
            
            foreach ($docTypePatterns as $docType => $patterns) {
                foreach ($patterns as $pattern) {
                    $patternUpper = strtoupper($pattern);
                    
                    // Exact match
                    if ($cleanFilename === $patternUpper || $filenameUpper === $patternUpper) {
                        $matchedDocType = $docType;
                        break 2;
                    }
                    
                    // Contains match
                    if (strpos($cleanFilename, $patternUpper) !== false || 
                        strpos($filenameUpper, $patternUpper) !== false) {
                        $matchedDocType = $docType;
                        break 2;
                    }
                    
                    // Regex match for patterns like "NPK_1", "NPK_2", etc.
                    if (preg_match('/^' . preg_quote($patternUpper, '/') . '(?:_[12])?(?:[\s\-_\.]|$)/i', $cleanFilename)) {
                        $matchedDocType = $docType;
                        break 2;
                    }
                }
            }

            // If no match found, use filename without extension as doc_type
            if (!$matchedDocType) {
                $matchedDocType = $cleanFilename ?: $filenameWithoutExt;
                Log::warning('Could not match filename to document type', [
                    'ticket' => $ticketNumber,
                    'filename' => $filename,
                    'using_as_doc_type' => $matchedDocType
                ]);
            }

            // Get file modification time
            $modifiedTime = Carbon::createFromTimestamp($disk->lastModified($filePath));

            $uploadedFiles[] = [
                'doc_type' => $matchedDocType,
                'filename' => $filename,
                'storage_path' => $filePath,
                'modified_time' => $modifiedTime,
            ];
        }

        Log::info('Uploaded files found in storage', [
            'ticket' => $ticketNumber,
            'count' => count($uploadedFiles),
            'doc_types' => array_column($uploadedFiles, 'doc_type')
        ]);

        return $uploadedFiles;
    }
}