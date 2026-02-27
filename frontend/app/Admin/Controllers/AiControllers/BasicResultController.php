<?php

namespace App\Admin\Controllers\AiControllers;

use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use \App\Models\Ticket;
use \App\Services\PDFService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class BasicResultController extends Controller
{
    private PDFService $pdfService;

    public function __construct(PDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Show result detail page
     * UPDATED: Load data yang dibutuhkan untuk notes.js
     */
    public function showResultDetail(Ticket $ticket, Content $content)
    {
        // Load relasi yang dibutuhkan
        $ticket->load([
            'company:id,name',
            'groundTruths' => function ($query) {
                $query->select('id', 'ticket_id', 'doc_type', 'extracted_data')
                    ->whereIn('doc_type', ['NOPES', 'KL', 'SP', 'WO'])
                    ->oldest('id')
                    ->limit(1);
            }
        ]);

        // Get DPP formatted dari ground truth
        $dppFormatted = $this->getFormattedDpp($ticket);

        // Buat simplified ticket object (sama seperti AdvanceReviewOverviewController)
        $ticketSimplified = (object) [
            'ticket_number' => $ticket->ticket_number,
            'project_title' => $ticket->project_title,
            'company' => (object) [
                'name' => $ticket->company->name ?? null
            ],
            'groundTruth' => (object) [
                'dpp' => $dppFormatted
            ]
        ];


        // Load Bootstrap Icons for consistent iconography
        Admin::css('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css');
        
        Admin::css(asset('css/notes.css'));
        Admin::css(asset('css/basic-review-result.css'));

        Admin::js('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js');
        Admin::js(asset('js/notes.js'));
        Admin::js(asset('js/basic-review-handler.js'));

        return $content
            ->title('Basic Review Result')
            ->description('Basic review result yang pernah dilakukan')
            ->body(view('advance-reviews.templates.basic-review-result', [
                'ticket' => $ticketSimplified,
                'ticketNumber' => $ticket->ticket_number,
                'isOpenAdmin' => true,
            ]));
    }

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

    /**
     * Format number to Rupiah string
     * Input: 1000 → Output: "Rp 1.000,-"
     */
    private function formatRupiah($number)
    {
        return 'Rp ' . number_format($number, 0, ',', '.') . ',-';
    }

    /**
     * Clean JSON string from invalid characters
     */
    private function cleanJsonString($jsonString)
    {
        // Remove BOM if present
        $jsonString = preg_replace('/^[\x00-\x1F\x80-\xFF]+/', '', $jsonString);
        // Remove trailing non-whitespace after valid JSON
        $jsonString = trim($jsonString);
        return $jsonString;
    }

    /**
     * Serve PDF file
     * Route: /pdf/basic/{ticket}/{docType}/{filename}
     */
    public function servePDF(string $ticketNumber, string $docType, string $filename): BinaryFileResponse
    {
        return $this->pdfService->serveBasicReviewPDF($ticketNumber, $docType, $filename);
    }

    /**
     * Get issues data for ticket (AJAX endpoint)
     */
    public function getTicketIssues(Ticket $ticket): JsonResponse
    {
        try {
            Log::info('=== START getTicketIssues ===', ['ticket' => $ticket->ticket_number]);

            // Load all related data
            $ticket->load([
                'typoErrors.boundingBoxes',
                'priceValidations.boundingBoxes',
                'dateValidations.boundingBoxes'
            ]);
            
            // Debug: Log bounding box counts
            Log::info('Bounding box counts', [
                'typo_errors_count' => $ticket->typoErrors->count(),
                'typo_errors_with_bbox' => $ticket->typoErrors->filter(function($e) {
                    return $e->boundingBoxes->count() > 0;
                })->count(),
                'typo_errors_without_bbox' => $ticket->typoErrors->filter(function($e) {
                    return $e->boundingBoxes->count() === 0;
                })->count(),
                'price_validations_count' => $ticket->priceValidations->count(),
                'date_validations_count' => $ticket->dateValidations->count(),
            ]);

            // ========================================
            // STEP 1: COLLECT ALL DOCUMENTS WITH ISSUES
            // ========================================
            $documentsMap = [];  // docType => document info
            $allIssuesByDocAndPage = [];  // docType => page => issues[]
            $allIssuesByDocType = [];  // docType => count of ALL issues (including those without bounding boxes)

            Log::info('Processing typo errors', ['count' => $ticket->typoErrors->count()]);

            // Process TYPO errors
            foreach ($ticket->typoErrors as $typoError) {
                $docType = $typoError->doc_type;

                // Initialize document if not exists
                if (!isset($documentsMap[$docType])) {
                    $documentsMap[$docType] = [
                        'docType' => $docType,
                        'pagesWithIssues' => [],
                    ];
                    $allIssuesByDocType[$docType] = 0;
                }

                // Count ALL issues (even without bounding boxes) for document-level counting
                $allIssuesByDocType[$docType] = ($allIssuesByDocType[$docType] ?? 0) + 1;

                $bboxCount = $typoError->boundingBoxes->count();
                if ($bboxCount === 0) {
                    Log::warning('TypoError has no bounding boxes - ISSUE CANNOT BE DISPLAYED ON PDF', [
                        'typo_error_id' => $typoError->id,
                        'doc_type' => $docType,
                        'typo_word' => $typoError->typo_word,
                        'correction_word' => $typoError->correction_word,
                        'ticket_id' => $typoError->ticket_id,
                        'NOTE' => 'This error exists but has no bounding boxes. It will appear in the issues list but cannot be drawn on the PDF.'
                    ]);
                    continue;
                }

                foreach ($typoError->boundingBoxes as $bbox) {
                    $page = $bbox->page;
                    
                    // Skip if page is null or empty
                    if ($page === null || $page === '') {
                        Log::warning('BoundingBox has null/empty page', [
                            'bbox_id' => $bbox->id,
                            'typo_error_id' => $typoError->id,
                            'doc_type' => $docType
                        ]);
                        continue;
                    }

                    // Normalize page to integer to ensure consistent array key types
                    $page = (int) $page;

                    // Track page with issues
                    if (!in_array($page, $documentsMap[$docType]['pagesWithIssues'], true)) {
                        $documentsMap[$docType]['pagesWithIssues'][] = $page;
                    }

                    // Store issue data
                    if (!isset($allIssuesByDocAndPage[$docType])) {
                        $allIssuesByDocAndPage[$docType] = [];
                    }
                    if (!isset($allIssuesByDocAndPage[$docType][$page])) {
                        $allIssuesByDocAndPage[$docType][$page] = [];
                    }

                    $allIssuesByDocAndPage[$docType][$page][] = [
                        'type' => 'typo',
                        'id' => $typoError->id,
                        'text' => "{$typoError->typo_word} → {$typoError->correction_word}",
                        'word' => $bbox->word ?? $typoError->typo_word,
                        'bbox' => [
                            'x' => (float) $bbox->x,
                            'y' => (float) $bbox->y,
                            'width' => (float) $bbox->width,
                            'height' => (float) $bbox->height,
                        ]
                    ];
                }
            }

            Log::info('Processing price validations', ['count' => $ticket->priceValidations->count()]);

            // Process PRICE validations
            foreach ($ticket->priceValidations as $priceValidation) {
                $docType = $priceValidation->doc_type;

                if (!isset($documentsMap[$docType])) {
                    $documentsMap[$docType] = [
                        'docType' => $docType,
                        'pagesWithIssues' => [],
                    ];
                    $allIssuesByDocType[$docType] = 0;
                }

                // Count ALL issues (even without bounding boxes) for document-level counting
                $allIssuesByDocType[$docType] = ($allIssuesByDocType[$docType] ?? 0) + 1;

                foreach ($priceValidation->boundingBoxes as $bbox) {
                    $page = $bbox->page;
                    
                    // Skip if page is null or empty
                    if ($page === null || $page === '') {
                        Log::warning('BoundingBox has null/empty page', [
                            'bbox_id' => $bbox->id,
                            'price_validation_id' => $priceValidation->id,
                            'doc_type' => $docType
                        ]);
                        continue;
                    }

                    // Normalize page to integer to ensure consistent array key types
                    $page = (int) $page;

                    if (!in_array($page, $documentsMap[$docType]['pagesWithIssues'], true)) {
                        $documentsMap[$docType]['pagesWithIssues'][] = $page;
                    }

                    if (!isset($allIssuesByDocAndPage[$docType])) {
                        $allIssuesByDocAndPage[$docType] = [];
                    }
                    if (!isset($allIssuesByDocAndPage[$docType][$page])) {
                        $allIssuesByDocAndPage[$docType][$page] = [];
                    }

                    $allIssuesByDocAndPage[$docType][$page][] = [
                        'type' => 'nominal',
                        'id' => $priceValidation->id,
                        'text' => $this->truncateText($priceValidation->extracted_text, 50),
                        'word' => $bbox->word,
                        'bbox' => [
                            'x' => (float) $bbox->x,
                            'y' => (float) $bbox->y,
                            'width' => (float) $bbox->width,
                            'height' => (float) $bbox->height,
                        ]
                    ];
                }
            }

            Log::info('Processing date validations', ['count' => $ticket->dateValidations->count()]);

            // Process DATE validations (only invalid ones)
            foreach ($ticket->dateValidations as $dateValidation) {
                if (!$dateValidation->is_valid) {
                    $docType = $dateValidation->doc_type;

                    if (!isset($documentsMap[$docType])) {
                        $documentsMap[$docType] = [
                            'docType' => $docType,
                            'pagesWithIssues' => [],
                        ];
                        $allIssuesByDocType[$docType] = 0;
                    }

                    // Count ALL issues (even without bounding boxes) for document-level counting
                    $allIssuesByDocType[$docType] = ($allIssuesByDocType[$docType] ?? 0) + 1;

                    foreach ($dateValidation->boundingBoxes as $bbox) {
                        $page = $bbox->page;
                        
                        // Skip if page is null or empty
                        if ($page === null || $page === '') {
                            Log::warning('BoundingBox has null/empty page', [
                                'bbox_id' => $bbox->id,
                                'date_validation_id' => $dateValidation->id,
                                'doc_type' => $docType
                            ]);
                            continue;
                        }

                        // Normalize page to integer to ensure consistent array key types
                        $page = (int) $page;

                        if (!in_array($page, $documentsMap[$docType]['pagesWithIssues'], true)) {
                            $documentsMap[$docType]['pagesWithIssues'][] = $page;
                        }

                        if (!isset($allIssuesByDocAndPage[$docType])) {
                            $allIssuesByDocAndPage[$docType] = [];
                        }
                        if (!isset($allIssuesByDocAndPage[$docType][$page])) {
                            $allIssuesByDocAndPage[$docType][$page] = [];
                        }

                        $allIssuesByDocAndPage[$docType][$page][] = [
                            'type' => 'date',
                            'id' => $dateValidation->id,
                            'text' => $this->truncateText($dateValidation->full_text, 50),
                            'word' => $bbox->word,
                            'bbox' => [
                                'x' => (float) $bbox->x,
                                'y' => (float) $bbox->y,
                                'width' => (float) $bbox->width,
                                'height' => (float) $bbox->height,
                            ]
                        ];
                    }
                }
            }

            // Sort pages for each document (ensure integer sorting)
            foreach ($documentsMap as $docType => &$docInfo) {
                // Ensure all pages are integers before sorting
                $docInfo['pagesWithIssues'] = array_map('intval', $docInfo['pagesWithIssues']);
                $docInfo['pagesWithIssues'] = array_unique($docInfo['pagesWithIssues']);
                sort($docInfo['pagesWithIssues'], SORT_NUMERIC);
                Log::info('Document pages info', [
                    'doc_type' => $docType,
                    'pages_count' => count($docInfo['pagesWithIssues']),
                    'pages' => $docInfo['pagesWithIssues']
                ]);
            }
            unset($docInfo);

            Log::info('Documents with issues', ['documents' => array_keys($documentsMap)]);

            // ========================================
            // STEP 2: CREATE GLOBAL PAGE MAPPING
            // ========================================
            $documents = [];
            $pageMapping = [];
            $issuesByGlobalPage = [];
            $globalPageNum = 1;
            $docIndex = 0;

            foreach ($documentsMap as $docType => $docInfo) {
                // Generate PDF URL
                $pdfUrl = $this->pdfService->generateBasicReviewPDFUrl(
                    $ticket->ticket_number,
                    $docType
                );

                // Add to documents array
                $documents[] = [
                    'docType' => $docType,
                    'pdfUrl' => $pdfUrl,
                    'pagesWithIssues' => $docInfo['pagesWithIssues'],
                    'docIndex' => $docIndex
                ];

                // Create mapping for each page with issues
                if (!empty($docInfo['pagesWithIssues'])) {
                    // If pages with issues exist, map them
                    foreach ($docInfo['pagesWithIssues'] as $pageInDoc) {
                        // Ensure pageInDoc is an integer for consistent key lookup
                        $pageInDoc = (int) $pageInDoc;
                        
                        $pageMapping[$globalPageNum] = [
                            'docType' => $docType,
                            'pageInDoc' => $pageInDoc,
                            'docIndex' => $docIndex,
                            'totalIssues' => null // Will be calculated from issues array for pages with bounding boxes
                        ];

                        // Map issues to global page number
                        if (isset($allIssuesByDocAndPage[$docType][$pageInDoc])) {
                            $issuesByGlobalPage[$globalPageNum] = $allIssuesByDocAndPage[$docType][$pageInDoc];
                        }

                        $globalPageNum++;
                    }
                } else {
                    // If no pages with issues, show first page of the document
                    // This ensures PDFs are visible even when bounding boxes have no page numbers
                    Log::warning('Document has no pages with issues, showing first page', [
                        'doc_type' => $docType
                    ]);
                    
                    // For documents with issues but no bounding boxes, count all issues for this document type
                    // This ensures the page badge shows the correct count even when issues can't be displayed on PDF
                    $totalIssuesForDoc = $allIssuesByDocType[$docType] ?? 0;
                    
                    $pageMapping[$globalPageNum] = [
                        'docType' => $docType,
                        'pageInDoc' => 1, // Show first page
                        'docIndex' => $docIndex,
                        'totalIssues' => $totalIssuesForDoc // Total issues for this document (including those without bounding boxes)
                    ];

                    if ($totalIssuesForDoc > 0) {
                        // Create placeholder issues array with count for display purposes
                        // Note: These issues won't have bounding boxes, so they can't be drawn on PDF
                        $issuesByGlobalPage[$globalPageNum] = [];
                    }

                    $globalPageNum++;
                }

                $docIndex++;
            }

            Log::info('Global page mapping created', [
                'total_global_pages' => count($pageMapping),
                'mapping' => $pageMapping
            ]);

            // ========================================
            // STEP 3: BUILD ISSUES LIST WITH LOCATIONS
            // ========================================
            $issuesList = [
                'typo' => [],
                'price' => [],
                'date' => []
            ];

            // Process TYPO errors for issuesList
            foreach ($ticket->typoErrors as $typoError) {
                $locations = [];
                $docType = $typoError->doc_type;

                foreach ($typoError->boundingBoxes as $bbox) {
                    $pageInDoc = $bbox->page;
                    
                    // Skip if page is null or empty
                    if ($pageInDoc === null || $pageInDoc === '') {
                        continue;
                    }
                    
                    // Normalize to integer for consistent lookup
                    $pageInDoc = (int) $pageInDoc;

                    // Find globalPageNum for this location
                    $globalPageNum = $this->findGlobalPageNum($pageMapping, $docType, $pageInDoc);

                    if ($globalPageNum) {
                        $locations[] = [
                            'docType' => $docType,
                            'pageInDoc' => $pageInDoc,
                            'globalPageNum' => $globalPageNum,
                            'word' => $bbox->word ?? $typoError->typo_word
                        ];
                    }
                }

                // Include issue even if it has no locations (no bounding boxes)
                // This ensures all issues are shown, even if they can't be displayed on PDF
                $issuesList['typo'][] = [
                    'id' => $typoError->id,
                    'text' => "{$typoError->typo_word} → {$typoError->correction_word}",
                    'locations' => $locations
                ];
            }

            // Process PRICE validations for issuesList
            foreach ($ticket->priceValidations as $priceValidation) {
                $locations = [];
                $docType = $priceValidation->doc_type;

                foreach ($priceValidation->boundingBoxes as $bbox) {
                    $pageInDoc = $bbox->page;
                    
                    // Skip if page is null or empty
                    if ($pageInDoc === null || $pageInDoc === '') {
                        continue;
                    }
                    
                    // Normalize to integer for consistent lookup
                    $pageInDoc = (int) $pageInDoc;
                    
                    $globalPageNum = $this->findGlobalPageNum($pageMapping, $docType, $pageInDoc);

                    if ($globalPageNum) {
                        $locations[] = [
                            'docType' => $docType,
                            'pageInDoc' => $pageInDoc,
                            'globalPageNum' => $globalPageNum,
                            'word' => $bbox->word
                        ];
                    }
                }

                // Include issue even if it has no locations (no bounding boxes)
                $issuesList['price'][] = [
                    'id' => $priceValidation->id,
                    'text' => $priceValidation->extracted_text,
                    'correction' => $priceValidation->correction,
                    'locations' => $locations
                ];
            }

            // Process DATE validations for issuesList
            foreach ($ticket->dateValidations as $dateValidation) {
                if (!$dateValidation->is_valid) {
                    $locations = [];
                    $docType = $dateValidation->doc_type;

                    foreach ($dateValidation->boundingBoxes as $bbox) {
                        $pageInDoc = $bbox->page;
                        
                        // Skip if page is null or empty
                        if ($pageInDoc === null || $pageInDoc === '') {
                            continue;
                        }
                        
                        // Normalize to integer for consistent lookup
                        $pageInDoc = (int) $pageInDoc;
                        
                        $globalPageNum = $this->findGlobalPageNum($pageMapping, $docType, $pageInDoc);

                        if ($globalPageNum) {
                            $locations[] = [
                                'docType' => $docType,
                                'pageInDoc' => $pageInDoc,
                                'globalPageNum' => $globalPageNum,
                                'word' => $bbox->word
                            ];
                        }
                    }

                    // Include issue even if it has no locations (no bounding boxes)
                    $issuesList['date'][] = [
                        'id' => $dateValidation->id,
                        'text' => $dateValidation->full_text,
                        'correction' => $dateValidation->correction,
                        'tanggal_bracket' => $dateValidation->tanggal_bracket,
                        'locations' => $locations
                    ];
                }
            }

            // ========================================
            // STEP 4: CALCULATE SUMMARY
            // ========================================
            $totalIssuesWithBbox = array_sum(array_map('count', $issuesByGlobalPage));
            $totalIssuesInList = count($issuesList['typo']) + count($issuesList['price']) + count($issuesList['date']);
            $summary = [
                'totalDocuments' => count($documents),
                'totalPagesWithIssues' => count($pageMapping),
                'totalIssues' => $totalIssuesWithBbox,
                'issuesCounts' => [
                    'typo' => count($issuesList['typo']),
                    'price' => count($issuesList['price']),
                    'date' => count($issuesList['date'])
                ]
            ];

            // Diagnostic: issues exist in list but no bounding box data (e.g. QA DB missing bbox rows)
            $boundingBoxesMissing = $totalIssuesInList > 0 && $totalIssuesWithBbox === 0;
            if ($boundingBoxesMissing) {
                Log::warning('Bounding boxes missing: issues exist in list but none have bbox data (per-page issues empty). Check bounding_boxes table and basic review job on this environment.', [
                    'ticket' => $ticket->ticket_number,
                    'issues_in_list' => $totalIssuesInList,
                    'issues_with_bbox' => $totalIssuesWithBbox
                ]);
            }

            Log::info('Summary calculated', $summary);
            Log::info('=== END getTicketIssues ===');

            // Ensure issues object has string keys for consistent JS lookup (JSON encodes int keys as strings)
            $issuesForResponse = [];
            foreach ($issuesByGlobalPage as $globalPageNum => $pageIssues) {
                $issuesForResponse[(string) $globalPageNum] = $pageIssues;
            }

            // ========================================
            // FINAL RESPONSE
            // ========================================
            return response()->json([
                'documents' => $documents,
                'pageMapping' => $pageMapping,
                'issues' => $issuesForResponse,
                'issuesList' => $issuesList,
                'summary' => $summary,
                'boundingBoxesMissing' => $boundingBoxesMissing,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getTicketIssues', [
                'ticket' => $ticket->ticket_number,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to load ticket data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Find global page number for a specific document page
     */
    private function findGlobalPageNum(array $pageMapping, string $docType, int $pageInDoc): ?int
    {
        foreach ($pageMapping as $globalNum => $info) {
            if ($info['docType'] === $docType && $info['pageInDoc'] === $pageInDoc) {
                return $globalNum;
            }
        }
        return null;
    }

    /**
     * Helper method to truncate long text
     */
    private function truncateText(string $text, int $length = 50): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length) . '...';
    }
}