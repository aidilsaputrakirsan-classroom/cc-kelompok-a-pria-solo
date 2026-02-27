<?php

namespace App\Admin\Controllers\AiControllers;

use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use \App\Models\Ticket;
use \App\Models\GroundTruth;
use \App\Services\PDFService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GroundTruthController extends Controller
{
    protected $pdfService;

    public function __construct(PDFService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Show validation page
     * Route: GET /validate-ground-truth/{ticket}
     */
    public function show($ticket_number, Content $content)
    {
        // Validasi format ticket number
        if (!preg_match('/^\d{6}-[A-Z]{3}-\d{3}$/', $ticket_number)) {
            Log::warning('Invalid ticket format', ['ticket' => $ticket_number]);
            abort(400, 'Format ticket number tidak valid');
        }

        try {
            // Cari ticket dari database dengan relasi groundTruths
            $ticket = Ticket::where('ticket_number', $ticket_number)
                ->with('groundTruths')
                ->firstOrFail();

            Log::info('Ticket found', [
                'ticket_number' => $ticket_number,
                'ticket_id' => $ticket->id,
                'ground_truths_count' => $ticket->groundTruths->count()
            ]);

            // ============================================================
            // NEW: Get SINGLE ground truth with doc_type = "Ground Truth"
            // ============================================================
            $groundTruthData = $this->getGroundTruthData($ticket);

            // Available documents untuk ticket ini (check dari file system)
            $availableDocuments = $this->getAvailableDocuments($ticket_number);

            // Generate PDF URLs using BACKEND KEY
            foreach ($availableDocuments as &$doc) {
                $doc['pdf_url'] = $this->pdfService->generateGroundTruthPDFUrl(
                    $ticket_number,
                    $doc['backend_key']
                );

                Log::debug('PDF URL generated', [
                    'frontend_key' => $doc['type'],
                    'backend_key' => $doc['backend_key'],
                    'pdf_url' => $doc['pdf_url']
                ]);
            }

            Log::info('Ground Truth validation page loaded', [
                'ticket' => $ticket_number,
                'documents_count' => count($availableDocuments),
                'data_keys' => array_keys($groundTruthData)
            ]);

            // Load Bootstrap Icons for consistent iconography
            Admin::css('https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css');
            
            // Load custom CSS files
            Admin::css(asset('css/validate-ground-truth.css'));
            Admin::css(asset('css/templates/template-npk.css'));
            Admin::css(asset('css/templates/template-kontrak.css'));

            // Inject config for validate-ground-truth.js (required when using OpenAdmin Content — @stack('scripts') is not rendered)
            $prefix = config('admin.route.prefix');
            Admin::script(
                'const GROUND_TRUTH_DATA = ' . json_encode($groundTruthData) . ';' .
                'const TICKET_NUMBER = ' . json_encode($ticket_number) . ';' .
                'const AVAILABLE_DOCUMENTS = ' . json_encode($availableDocuments) . ';' .
                'const CSRF_TOKEN = ' . json_encode(csrf_token()) . ';' .
                'const REVIEW_SUBMIT_URL = ' . json_encode(url($prefix . '/api/review/submit')) . ';' .
                'const REVIEW_STATUS_URL = ' . json_encode(url($prefix . '/api/review/status')) . ';'
            );

            // Load custom JS files (must run after the config script above)
            Admin::js('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js');
            Admin::js(asset('js/form-templates/form-npk.js'));
            Admin::js(asset('js/form-templates/form-npk-readonly.js'));
            Admin::js(asset('js/form-templates/form-kontrak.js'));
            Admin::js(asset('js/form-templates/form-kontrak-readonly.js'));
            Admin::js(asset('js/validate-ground-truth.js'));

            return $content
                ->title('Validasi Ground Truth')
                ->description('Validasi ground truth yang pernah dilakukan')
                ->body(view('advance-reviews.templates.validate-ground-truth', [
                    'ticketNumber' => $ticket_number,
                    'groundTruthData' => $groundTruthData,
                    'availableDocuments' => $availableDocuments,
                    'isOpenAdmin' => true,
                ]));

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Ticket not found', ['ticket' => $ticket_number]);
            abort(404, 'Ticket tidak ditemukan');
        } catch (\Exception $e) {
            Log::error('Error loading ground truth page', [
                'ticket' => $ticket_number,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Terjadi kesalahan saat memuat halaman');
        }
    }

    /**
     * Save validated data (NEW STRUCTURE)
     * Route: POST /validate-ground-truth/{ticket}/save
     */
    public function save(Request $request, $ticket_number)
    {
        // Validasi format ticket number
        if (!preg_match('/^\d{6}-[A-Z]{3}-\d{3}$/', $ticket_number)) {
            return response()->json([
                'success' => false,
                'message' => 'Format ticket number tidak valid'
            ], 400);
        }

        // Validasi request data
        $validated = $request->validate([
            'doc_type' => 'required|string',
            'data' => 'required|array'
        ]);

        try {
            // Cari ticket dari database
            $ticket = Ticket::where('ticket_number', $ticket_number)->firstOrFail();

            Log::info('Saving ground truth data (Single GT structure)', [
                'ticket' => $ticket_number,
                'ticket_id' => $ticket->id,
                'doc_type_to_update' => $validated['doc_type']
            ]);

            // ============================================================
            // NEW: Get or create SINGLE ground truth
            // ============================================================
            $groundTruth = GroundTruth::where('ticket_id', $ticket->id)
                ->where('doc_type', 'Ground Truth')
                ->first();

            if (!$groundTruth) {
                // Create new ground truth if doesn't exist
                $groundTruth = GroundTruth::create([
                    'ticket_id' => $ticket->id,
                    'doc_type' => 'Ground Truth',
                    'extracted_data' => []
                ]);

                Log::info('Created new Ground Truth record', [
                    'ground_truth_id' => $groundTruth->id,
                    'ticket_id' => $ticket->id
                ]);
            }

            // ============================================================
            // NEW: Update specific doc_type within extracted_data
            // ============================================================
            $extractedData = $groundTruth->extracted_data ?? [];
            
            // Update the specific doc_type data
            $extractedData[$validated['doc_type']] = $validated['data'];
            
            // Save back to ground truth
            $groundTruth->extracted_data = $extractedData;
            $groundTruth->save();

            Log::info('Ground truth data updated successfully', [
                'ground_truth_id' => $groundTruth->id,
                'doc_type_updated' => $validated['doc_type'],
                'total_doc_types' => count($extractedData),
                'updated_at' => $groundTruth->updated_at->toDateTimeString()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan',
                'data' => [
                    'id' => $groundTruth->id,
                    'doc_type' => $groundTruth->doc_type,
                    'updated_doc_type' => $validated['doc_type'],
                    'total_doc_types' => count($extractedData),
                    'updated_at' => $groundTruth->updated_at->toDateTimeString()
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Ticket not found for save', ['ticket' => $ticket_number]);
            return response()->json([
                'success' => false,
                'message' => 'Ticket tidak ditemukan'
            ], 404);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed', [
                'ticket' => $ticket_number,
                'errors' => $e->errors()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error saving ground truth', [
                'ticket' => $ticket_number,
                'doc_type' => $validated['doc_type'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Serve PDF file using PDFService
     * Route: GET /validate-ground-truth/{ticket}/{docType}/{filename}
     */
    public function servePDF($ticket_number, $doc_type, $filename)
    {
        // Validasi format ticket number
        if (!preg_match('/^\d{6}-[A-Z]{3}-\d{3}$/', $ticket_number)) {
            Log::warning('Invalid ticket format in PDF serve', ['ticket' => $ticket_number]);
            abort(400, 'Format ticket number tidak valid');
        }

        // Validate filename matches doc_type
        $expectedFilename = "{$doc_type}.pdf";

        if ($filename !== $expectedFilename) {
            Log::warning('Filename mismatch in Ground Truth PDF serve', [
                'ticket' => $ticket_number,
                'doc_type' => $doc_type,
                'requested' => $filename,
                'expected' => $expectedFilename
            ]);
            abort(403, 'Nama file tidak valid');
        }

        Log::info('Serving PDF for Ground Truth validation', [
            'ticket' => $ticket_number,
            'doc_type' => $doc_type,
            'filename' => $filename
        ]);

        try {
            // Use PDFService to serve the file
            return $this->pdfService->serveGroundTruthPDF($ticket_number, $doc_type, $filename);

        } catch (\Exception $e) {
            Log::error('Error serving PDF', [
                'ticket' => $ticket_number,
                'doc_type' => $doc_type,
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            abort(404, 'File PDF tidak ditemukan');
        }
    }

    /**
     * Get ground truth data from database (NEW STRUCTURE)
     * Transform dari SINGLE ground truth dengan doc_type = "Ground Truth"
     * ke format yang dibutuhkan frontend
     */
    private function getGroundTruthData(Ticket $ticket): array
    {
        // ============================================================
        // NEW: Get SINGLE ground truth with doc_type = "Ground Truth"
        // ============================================================
        $groundTruth = GroundTruth::where('ticket_id', $ticket->id)
            ->where('doc_type', 'Ground Truth')
            ->first();

        if (!$groundTruth) {
            Log::warning('No ground truth found for ticket', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number
            ]);
            return [];
        }

        $extractedData = $groundTruth->extracted_data ?? [];
        
        // Remove _metadata if exists
        unset($extractedData['_metadata']);

        // Sort data untuk setiap doc_type
        $data = [];
        foreach ($extractedData as $docType => $docData) {
            // Sort date-related data
            $sortedData = $this->sortDateRelatedData($docData, $docType);
            $data[$docType] = $sortedData;
        }

        Log::info('Ground truth data retrieved from database (Single GT)', [
            'ticket' => $ticket->ticket_number,
            'ground_truth_id' => $groundTruth->id,
            'doc_types_count' => count($data),
            'doc_types' => array_keys($data)
        ]);

        return $data;
    }

    /**
     * Sort data yang berkaitan dengan bulan dan tahun
     * Menangani: NPK prorate, BAUT monthly values, dll
     */
    private function sortDateRelatedData(array $data, string $docType): array
    {
        // Dokumen yang memiliki data dengan bulan/tahun
        $dateRelatedFields = [
            'NPK' => ['prorate'],
            'BAUT' => ['monthly_usage_values'],
        ];

        // Cek apakah dokumen ini punya field yang perlu di-sort
        if (!isset($dateRelatedFields[$docType])) {
            return $data;
        }

        $fieldsToSort = $dateRelatedFields[$docType];

        foreach ($fieldsToSort as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                // Sort berdasarkan bulan dan tahun
                $data[$field] = $this->sortByMonthYear($data[$field]);

                Log::debug('Sorted date-related field', [
                    'doc_type' => $docType,
                    'field' => $field,
                    'count' => count($data[$field])
                ]);
            }
        }

        return $data;
    }

    /**
     * Sort array berdasarkan bulan dan tahun
     */
    private function sortByMonthYear(array $items): array
    {
        // Check if it's an associative array (object-like)
        if ($this->isAssocArray($items)) {
            // Convert to indexed array with period info
            $converted = [];
            foreach ($items as $period => $value) {
                $converted[] = [
                    'period' => $period,
                    'value' => $value,
                    'sort_key' => $this->parsePeriodForSort($period)
                ];
            }

            // Sort by parsed date
            usort($converted, function ($a, $b) {
                return $a['sort_key'] <=> $b['sort_key'];
            });

            return $converted;
        }

        // It's already an indexed array, sort normally
        usort($items, function ($a, $b) {
            $yearA = isset($a['tahun']) ? (int) $a['tahun'] : 0;
            $yearB = isset($b['tahun']) ? (int) $b['tahun'] : 0;

            $monthA = isset($a['bulan']) ? $this->getMonthNumber($a['bulan']) : 0;
            $monthB = isset($b['bulan']) ? $this->getMonthNumber($b['bulan']) : 0;

            if ($yearA != $yearB) {
                return $yearA <=> $yearB;
            }

            return $monthA <=> $monthB;
        });

        return $items;
    }

    /**
     * Check if array is associative (object-like)
     */
    private function isAssocArray(array $arr): bool
    {
        if (empty($arr))
            return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Parse period string like "Maret 2025" into sortable integer YYYYMM
     */
    private function parsePeriodForSort(string $period): int
    {
        $parts = explode(' ', trim($period));

        if (count($parts) >= 2) {
            $month = $this->getMonthNumber($parts[0]);
            $year = (int) $parts[1];

            return ($year * 100) + $month;
        }

        return 0;
    }

    /**
     * Convert nama bulan ke angka untuk sorting
     */
    private function getMonthNumber($month): int
    {
        if (is_numeric($month)) {
            return (int) $month;
        }

        $months = [
            'januari' => 1, 'jan' => 1,
            'februari' => 2, 'feb' => 2,
            'maret' => 3, 'mar' => 3,
            'april' => 4, 'apr' => 4,
            'mei' => 5,
            'juni' => 6, 'jun' => 6,
            'juli' => 7, 'jul' => 7,
            'agustus' => 8, 'agu' => 8, 'agt' => 8,
            'september' => 9, 'sep' => 9, 'sept' => 9,
            'oktober' => 10, 'okt' => 10, 'oct' => 10,
            'november' => 11, 'nov' => 11,
            'desember' => 12, 'des' => 12, 'dec' => 12,
        ];

        $monthLower = strtolower(trim($month));

        return $months[$monthLower] ?? 0;
    }

    /**
     * Get available documents for ticket from file system
     */
    private function getAvailableDocuments(string $ticket_number): array
    {
        $disk = Storage::disk('public');
        $directoryPath = "advance-review/{$ticket_number}";

        $documents = [
            'Kontrak Layanan' => ['KL', 'Kontrak Layanan', ['KL', 'KONTRAK LAYANAN', 'Kontrak Layanan']],
            'Nota Pesanan' => ['NOPES', 'Nota Pesanan', ['NOPES', 'NOTA PESANAN', 'Nota Pesanan', 'NOTA_PESANAN']],
            'Work Order' => ['WO', 'Work Order', ['WO', 'WORK ORDER', 'Work Order', 'WORK_ORDER']],
            'Surat Pesanan' => ['SP', 'Surat Pesanan', ['SP', 'SURAT PESANAN', 'Surat Pesanan', 'SURAT_PESANAN']],
            'NPK' => ['NPK', 'NPK', ['NPK']],
            'BAUT' => ['BAUT', 'BAUT', ['BAUT']],
            'BARD' => ['BARD', 'BARD', ['BARD']],
            'BAST' => ['BAST', 'BAST', ['BAST']],
            'P7' => ['P7', 'P7', ['P7']]
        ];

        $available = [];

        if (!$disk->exists($directoryPath)) {
            Log::warning('Directory does not exist', [
                'ticket' => $ticket_number,
                'directory_path' => $directoryPath
            ]);
            return [];
        }

        $allFiles = $disk->files($directoryPath);
        $pdfFiles = array_filter($allFiles, function ($file) {
            return strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf';
        });

        Log::info('Scanning directory for PDF files', [
            'ticket' => $ticket_number,
            'directory_path' => $directoryPath,
            'total_files' => count($allFiles),
            'pdf_files_count' => count($pdfFiles)
        ]);

        foreach ($pdfFiles as $filePath) {
            $filename = basename($filePath);
            $filenameUpper = strtoupper(pathinfo($filename, PATHINFO_FILENAME));
            $cleanFilename = preg_replace('/^\d+\.?\s*/', '', $filenameUpper);

            foreach ($documents as $frontendKey => $config) {
                $backendKey = $config[0];
                $label = $config[1];
                $patterns = $config[2];

                $alreadyMatched = false;
                foreach ($available as $existing) {
                    if ($existing['backend_key'] === $backendKey) {
                        $alreadyMatched = true;
                        break;
                    }
                }

                if ($alreadyMatched) continue;

                $matched = false;
                foreach ($patterns as $pattern) {
                    $patternUpper = strtoupper($pattern);

                    if ($cleanFilename === $patternUpper || $filenameUpper === $patternUpper) {
                        $matched = true;
                        break;
                    }

                    if (strpos($cleanFilename, $patternUpper) !== false || strpos($filenameUpper, $patternUpper) !== false) {
                        $matched = true;
                        break;
                    }

                    if (preg_match('/^' . preg_quote($patternUpper, '/') . '(?:_[12])?(?:[\s\-_\.]|$)/i', $cleanFilename)) {
                        $matched = true;
                        break;
                    }
                }

                if ($matched) {
                    $fullPath = $disk->path($filePath);

                    $available[] = [
                        'type' => $frontendKey,
                        'label' => $label,
                        'backend_key' => $backendKey,
                        'file_path' => $fullPath,
                        'file_size' => $disk->size($filePath),
                        'original_filename' => $filename
                    ];

                    Log::debug('Document file matched', [
                        'ticket' => $ticket_number,
                        'frontend_key' => $frontendKey,
                        'backend_key' => $backendKey,
                        'original_filename' => $filename
                    ]);
                    break;
                }
            }
        }

        if (empty($available)) {
            Log::warning('No documents found for ticket', [
                'ticket' => $ticket_number,
                'storage_base_path' => $directoryPath
            ]);
            return [];
        }

        $available = $this->sortAndFilterDocuments($available);

        Log::info('Available documents found (sorted)', [
            'ticket' => $ticket_number,
            'count' => count($available),
            'doc_types' => array_column($available, 'type')
        ]);

        return $available;
    }

    /**
     * Sort and filter documents
     */
    private function sortAndFilterDocuments(array $available): array
    {
        $priorityOrder = [
            'Kontrak Layanan' => 1,
            'Nota Pesanan' => 2,
            'Work Order' => 3,
            'Surat Pesanan' => 4,
            'NPK' => 5,
            'BAUT' => 6,
            'BARD' => 7,
            'BAST' => 8,
            'P7' => 9,
        ];

        $firstGroupTypes = ['Kontrak Layanan', 'Nota Pesanan', 'Work Order', 'Surat Pesanan'];
        $firstGroupDocs = [];
        $otherDocs = [];

        foreach ($available as $doc) {
            if (in_array($doc['type'], $firstGroupTypes)) {
                $firstGroupDocs[] = $doc;
            } else {
                $otherDocs[] = $doc;
            }
        }

        $selectedFirstDoc = null;
        if (!empty($firstGroupDocs)) {
            usort($firstGroupDocs, function ($a, $b) use ($priorityOrder) {
                return ($priorityOrder[$a['type']] ?? 999) - ($priorityOrder[$b['type']] ?? 999);
            });
            $selectedFirstDoc = $firstGroupDocs[0];
        }

        $sorted = [];
        if ($selectedFirstDoc) {
            $sorted[] = $selectedFirstDoc;
        }

        usort($otherDocs, function ($a, $b) use ($priorityOrder) {
            return ($priorityOrder[$a['type']] ?? 999) - ($priorityOrder[$b['type']] ?? 999);
        });

        $sorted = array_merge($sorted, $otherDocs);

        return $sorted;
    }

    /**
     * Complete validation
     */
    public function complete($ticket_number)
    {
        try {
            $ticket = Ticket::where('ticket_number', $ticket_number)->firstOrFail();

            Log::info('Ticket validation completed', [
                'ticket' => $ticket_number,
                'ground_truths_count' => $ticket->groundTruths->count()
            ]);

            return redirect()
                ->route('tickets.index')
                ->with('success', 'Validasi ground truth berhasil diselesaikan');

        } catch (\Exception $e) {
            Log::error('Error completing validation', [
                'ticket' => $ticket_number,
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Gagal menyelesaikan validasi');
        }
    }
}