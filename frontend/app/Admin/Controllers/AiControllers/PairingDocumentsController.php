<?php

namespace App\Admin\Controllers\AiControllers;

use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Carbon;

class PairingDocumentsController extends Controller
{
    /**
     * Show the pairing documents selection modal (via AJAX)
     * Returns available PDF documents for the given ticket
     */
    public function getAvailableDocuments($ticketNumber)
    {
        try {
            // Get uploaded files from storage
            $uploadedFiles = $this->getUploadedFilesFromStorage($ticketNumber);

            if (empty($uploadedFiles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No documents found for this ticket',
                    'documents' => []
                ]);
            }

            // Group documents by type for better UI organization
            $groupedDocuments = [];
            foreach ($uploadedFiles as $file) {
                $docType = $file['doc_type'];
                if (!isset($groupedDocuments[$docType])) {
                    $groupedDocuments[$docType] = [];
                }
                $groupedDocuments[$docType][] = [
                    'id' => md5($file['storage_path']), // Unique ID for document
                    'type' => $docType,
                    'filename' => $file['filename'],
                    'path' => $file['storage_path'],
                    'size' => $this->getFileSizeFormatted($file['storage_path']),
                    'modifiedTime' => $file['modified_time']->format('d M Y H:i')
                ];
            }

            return response()->json([
                'success' => true,
                'ticketNumber' => $ticketNumber,
                'documents' => $groupedDocuments,
                'totalDocuments' => count($uploadedFiles)
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting available documents', [
                'ticket_number' => $ticketNumber,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load documents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the comparison page with two selected documents
     */
    public function showComparison($ticketNumber, Request $request, Content $content)
    {
        try {
            // Validate input
            $validated = $request->validate([
                'doc1' => 'required|string',
                'doc2' => 'required|string',
            ]);

            // Get ticket
            $ticket = Ticket::where('ticket_number', $ticketNumber)
                ->select(['id', 'ticket_number', 'project_title', 'company_id'])
                ->with('company:id,name')
                ->first();

            if (!$ticket) {
                abort(404, 'Ticket not found');
            }

            // Get available documents
            $uploadedFiles = $this->getUploadedFilesFromStorage($ticketNumber);
            
            // Find the selected documents
            $doc1 = $this->findDocumentByPath($uploadedFiles, $validated['doc1']);
            $doc2 = $this->findDocumentByPath($uploadedFiles, $validated['doc2']);

            if (!$doc1 || !$doc2) {
                abort(404, 'One or both selected documents not found');
            }

            // Prepare data for view
            $ticketData = (object)[
                'ticket_number' => $ticketNumber,
                'project_title' => $ticket->project_title ?? 'N/A',
                'company' => (object)[
                    'name' => $ticket->company ? $ticket->company->name : 'N/A'
                ]
            ];

            $routePrefix = config('admin.route.prefix');
            $doc1Id = md5($doc1['storage_path']);
            $doc2Id = md5($doc2['storage_path']);

            $doc1Data = (object)[
                'id' => $doc1Id,
                'type' => $doc1['doc_type'],
                'filename' => $doc1['filename'],
                'path' => $doc1['storage_path'],
                'url' => url($routePrefix . '/pdf/pairing/' . $ticketNumber . '/' . $doc1Id)
            ];

            $doc2Data = (object)[
                'id' => $doc2Id,
                'type' => $doc2['doc_type'],
                'filename' => $doc2['filename'],
                'path' => $doc2['storage_path'],
                'url' => url($routePrefix . '/pdf/pairing/' . $ticketNumber . '/' . $doc2Id)
            ];

            // Load custom CSS
            Admin::css(asset('css/notes.css'));
            Admin::css(asset('css/pairing-documents.css'));
            Admin::css('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf_viewer.min.css');

            // Load custom JS
            Admin::js('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js');
            Admin::js('https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf_viewer.min.js');
            Admin::js(asset('js/pairing-documents.js'));

            return $content
                ->title('Pairing Documents Comparison')
                ->description('Compare two documents')
                ->body(view('advance-reviews.templates.pairing-documents-comparison', [
                    'ticket' => $ticketData,
                    'doc1' => $doc1Data,
                    'doc2' => $doc2Data,
                    'allDocuments' => $uploadedFiles,
                    'isOpenAdmin' => true
                ]));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Error showing comparison page', [
                'ticket_number' => $ticketNumber,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Error loading comparison page');
        }
    }

    /**
     * Serve PDF file for viewing
     * Uses document ID to prevent direct path access
     */
    public function servePDF($ticketNumber, $documentId)
    {
        try {
            // Get available documents and find by ID
            $uploadedFiles = $this->getUploadedFilesFromStorage($ticketNumber);
            
            $document = null;
            foreach ($uploadedFiles as $file) {
                if (md5($file['storage_path']) === $documentId) {
                    $document = $file;
                    break;
                }
            }

            if (!$document) {
                abort(404, 'Document not found');
            }

            $disk = Storage::disk('public');
            $filePath = $document['storage_path'];

            if (!$disk->exists($filePath)) {
                abort(404, 'File does not exist');
            }

            // Get full file path
            $fullPath = storage_path('app/public/' . $filePath);

            // Serve the PDF file with proper headers for PDF.js
            return response()->stream(
                function () use ($fullPath) {
                    readfile($fullPath);
                },
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Length' => filesize($fullPath),
                    'Content-Disposition' => 'inline; filename="' . $document['filename'] . '"',
                    'Cache-Control' => 'public, max-age=86400',
                    'Accept-Ranges' => 'bytes'
                ]
            );

        } catch (\Exception $e) {
            Log::error('Error serving PDF', [
                'ticket_number' => $ticketNumber,
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Error serving PDF file');
        }
    }

    /**
     * Get uploaded files from storage for a ticket
     * Scans storage/app/public/advance-review/{ticketNumber}/ directory
     * 
     * This is extracted from AdvanceReviewOverviewController for reusability
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
            Log::debug('Storage directory does not exist', [
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

        Log::debug('Scanning storage for uploaded files in pairing documents', [
            'ticket' => $ticketNumber,
            'total_files' => count($allFiles),
            'pdf_files_count' => count($pdfFiles)
        ]);

        // Document type mapping patterns
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
            'NOPES' => ['NOPES', 'NOTA PESANAN', 'NOTA_PESANAN'],
            'BEBAS PPH' => ['BEBAS PPH', 'BEBAS_PPH'],
            'BAPLA' => ['BAPLA'],
            'SKM' => ['SKM'],
            'P7' => ['P7'],
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
                Log::debug('Could not match filename to document type in pairing', [
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

        Log::debug('Uploaded files found for pairing documents', [
            'ticket' => $ticketNumber,
            'count' => count($uploadedFiles),
            'doc_types' => array_column($uploadedFiles, 'doc_type')
        ]);

        return $uploadedFiles;
    }

    /**
     * Find a document in the list by its storage path
     */
    private function findDocumentByPath($documents, $path)
    {
        foreach ($documents as $doc) {
            if ($doc['storage_path'] === $path) {
                return $doc;
            }
        }
        return null;
    }

    /**
     * Get formatted file size
     */
    private function getFileSizeFormatted($filePath): string
    {
        try {
            $disk = Storage::disk('public');
            $size = $disk->size($filePath);
            
            if ($size === null) {
                return 'Unknown';
            }

            return $this->formatBytes($size);
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes($bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
