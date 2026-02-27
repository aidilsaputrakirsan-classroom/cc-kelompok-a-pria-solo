<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PDFService
{
    /**
     * ========================================
     * ACTIVE METHODS
     * ========================================
     */

    /**
     * Serve PDF for both Basic and Advance Review
     * Note: Files are stored with original filenames, so we need to find by pattern matching
     * Path: storage/app/public/advance-review/{ticket}/{original_filename}.pdf
     */
    public function serveAdvanceReviewPDF(string $ticketNumber, string $docType, string $filename): BinaryFileResponse
    {
        $this->validateTicketNumber($ticketNumber);
        $this->validateFilenameAdvance($filename);

        $expectedFilename = "{$docType}.pdf";
        if ($filename !== $expectedFilename) {
            Log::warning('Filename mismatch', [
                'ticket' => $ticketNumber,
                'doc_type' => $docType,
                'requested' => $filename,
                'expected' => $expectedFilename
            ]);
            abort(403, 'Invalid filename');
        }

        $disk = Storage::disk('public');
        $directoryPath = "advance-review/{$ticketNumber}";
        
        // First try exact filename match (in case file is stored as {docType}.pdf)
        $storagePath = "{$directoryPath}/{$filename}";
        
        if ($disk->exists($storagePath)) {
            $pdfPath = $disk->path($storagePath);
            Log::info('Serving Advance Review PDF (exact match)', [
                'ticket' => $ticketNumber,
                'doc_type' => $docType,
                'filename' => $filename,
                'storage_path' => $storagePath
            ]);
            return $this->servePDFFile($pdfPath, $filename, $ticketNumber, 'review');
        }
        
        // If not found, search for files matching the doc_type pattern
        if (!$disk->exists($directoryPath)) {
            Log::error('Directory does not exist', [
                'ticket' => $ticketNumber,
                'directory_path' => $directoryPath
            ]);
            abort(404, 'PDF file not found');
        }
        
        // Get all PDF files and find matching one
        $allFiles = $disk->files($directoryPath);
        $pdfFiles = array_filter($allFiles, function ($file) {
            return strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf';
        });
        
        // Pattern matching for doc_type
        $patterns = $this->getDocTypePatterns($docType);
        
        foreach ($pdfFiles as $filePath) {
            $actualFilename = basename($filePath);
            $filenameUpper = strtoupper(pathinfo($actualFilename, PATHINFO_FILENAME));
            $cleanFilename = preg_replace('/^\d+[A-Z]?\.?\s*/', '', $filenameUpper);
            $cleanFilename = trim($cleanFilename);
            
            foreach ($patterns as $pattern) {
                $patternUpper = strtoupper($pattern);
                
                // Check for matches (more flexible matching for advance review)
                if ($cleanFilename === $patternUpper || 
                    $filenameUpper === $patternUpper ||
                    strpos($cleanFilename, $patternUpper) !== false ||
                    strpos($filenameUpper, $patternUpper) !== false ||
                    preg_match('/^' . preg_quote($patternUpper, '/') . '(?:_[12])?(?:[\s\-_\.]|$)/i', $cleanFilename) ||
                    preg_match('/\b' . preg_quote($patternUpper, '/') . '\b/i', $filenameUpper)) {
                    
                    $fullPath = $disk->path($filePath);
                    Log::info('Serving Advance Review PDF (pattern match)', [
                        'ticket' => $ticketNumber,
                        'doc_type' => $docType,
                        'requested_filename' => $filename,
                        'actual_filename' => $actualFilename,
                        'matched_pattern' => $pattern,
                        'storage_path' => $filePath
                    ]);
                    return $this->servePDFFile($fullPath, $filename, $ticketNumber, 'review');
                }
            }
        }
        
        Log::error('PDF file not found (pattern matching)', [
            'ticket' => $ticketNumber,
            'doc_type' => $docType,
            'requested_filename' => $filename,
            'directory_path' => $directoryPath,
            'available_files' => array_map('basename', $pdfFiles)
        ]);
        abort(404, 'PDF file not found');
    }

    /**
     * Generate PDF URL (used by both Basic and Advance Review)
     */
    public function generateAdvanceReviewPDFUrl(string $ticketNumber, string $docType): string
    {
        $filename = "{$docType}.pdf";
        $prefix = config('admin.route.prefix', 'admin');
        return url("{$prefix}/pdf/advance/{$ticketNumber}/{$docType}/{$filename}");
    }

    /**
     * Serve PDF for Basic Review
     * Note: Files are stored with original filenames, so we need to find by pattern matching
     * Path: storage/app/public/advance-review/{ticket}/{original_filename}.pdf
     */
    public function serveBasicReviewPDF(string $ticketNumber, string $docType, string $filename): BinaryFileResponse
    {
        $this->validateTicketNumber($ticketNumber);
        
        $disk = Storage::disk('public');
        $directoryPath = "advance-review/{$ticketNumber}";
        
        // First try exact filename match (in case file is stored as {docType}.pdf)
        $storagePath = "{$directoryPath}/{$filename}";
        if ($disk->exists($storagePath)) {
            $pdfPath = $disk->path($storagePath);
            Log::info('Serving Basic Review PDF (exact match)', [
                'ticket' => $ticketNumber,
                'doc_type' => $docType,
                'filename' => $filename,
                'storage_path' => $storagePath
            ]);
            return $this->servePDFFile($pdfPath, $filename, $ticketNumber, 'basic-review');
        }
        
        // If not found, search for files matching the doc_type pattern
        if (!$disk->exists($directoryPath)) {
            Log::error('Directory does not exist', [
                'ticket' => $ticketNumber,
                'directory_path' => $directoryPath
            ]);
            abort(404, 'PDF file not found');
        }
        
        // Get all PDF files and find matching one
        $allFiles = $disk->files($directoryPath);
        $pdfFiles = array_filter($allFiles, function ($file) {
            return strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf';
        });
        
        // Pattern matching for doc_type
        $patterns = $this->getDocTypePatterns($docType);
        
        foreach ($pdfFiles as $filePath) {
            $actualFilename = basename($filePath);
            $filenameUpper = strtoupper(pathinfo($actualFilename, PATHINFO_FILENAME));
            $cleanFilename = preg_replace('/^\d+[A-Z]?\.?\s*/', '', $filenameUpper);
            $cleanFilename = trim($cleanFilename);
            
            foreach ($patterns as $pattern) {
                $patternUpper = strtoupper($pattern);
                
                // Check for matches (more flexible matching for basic review)
                if ($cleanFilename === $patternUpper || 
                    $filenameUpper === $patternUpper ||
                    strpos($cleanFilename, $patternUpper) !== false ||
                    strpos($filenameUpper, $patternUpper) !== false ||
                    preg_match('/^' . preg_quote($patternUpper, '/') . '(?:_[12])?(?:[\s\-_\.]|$)/i', $cleanFilename) ||
                    preg_match('/\b' . preg_quote($patternUpper, '/') . '\b/i', $filenameUpper)) {
                    
                    $fullPath = $disk->path($filePath);
                    Log::info('Serving Basic Review PDF (pattern match)', [
                        'ticket' => $ticketNumber,
                        'doc_type' => $docType,
                        'requested_filename' => $filename,
                        'actual_filename' => $actualFilename,
                        'matched_pattern' => $pattern,
                        'storage_path' => $filePath
                    ]);
                    return $this->servePDFFile($fullPath, $filename, $ticketNumber, 'basic-review');
                }
            }
        }
        
        Log::error('PDF file not found (pattern matching)', [
            'ticket' => $ticketNumber,
            'doc_type' => $docType,
            'requested_filename' => $filename,
            'directory_path' => $directoryPath,
            'available_files' => array_map('basename', $pdfFiles)
        ]);
        abort(404, 'PDF file not found');
    }

    /**
     * Generate PDF URL for Basic Review
     * Note: Now uses same path as Advance Review (no separation anymore)
     */
    public function generateBasicReviewPDFUrl(string $ticketNumber, string $docType): string
    {
        $filename = "{$docType}.pdf";
        $prefix = config('admin.route.prefix', 'admin');
        return url("{$prefix}/pdf/basic/{$ticketNumber}/{$docType}/{$filename}");
    }

    /**
     * Serve PDF for Ground Truth
     * Note: Files are stored with original filenames, so we need to find by pattern matching
     * Path: storage/app/public/advance-review/{ticket}/{original_filename}.pdf
     */
    public function serveGroundTruthPDF(string $ticketNumber, string $docType, string $filename): BinaryFileResponse
    {
        $this->validateTicketNumber($ticketNumber);
        
        $disk = Storage::disk('public');
        $directoryPath = "advance-review/{$ticketNumber}";
        
        // First try exact filename match
        $storagePath = "{$directoryPath}/{$filename}";
        if ($disk->exists($storagePath)) {
            $pdfPath = $disk->path($storagePath);
            Log::info('Serving PDF (exact match)', [
                'ticket' => $ticketNumber,
                'doc_type' => $docType,
                'filename' => $filename,
                'storage_path' => $storagePath
            ]);
            return $this->servePDFFile($pdfPath, $filename, $ticketNumber, 'ground-truth');
        }
        
        // If not found, search for files matching the doc_type pattern
        if (!$disk->exists($directoryPath)) {
            Log::error('Directory does not exist', [
                'ticket' => $ticketNumber,
                'directory_path' => $directoryPath
            ]);
            abort(404, 'PDF file not found');
        }
        
        // Get all PDF files and find matching one
        $allFiles = $disk->files($directoryPath);
        $pdfFiles = array_filter($allFiles, function ($file) {
            return strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf';
        });
        
        // Pattern matching for doc_type (similar to getAvailableDocuments)
        $patterns = $this->getDocTypePatterns($docType);
        
        foreach ($pdfFiles as $filePath) {
            $actualFilename = basename($filePath);
            $filenameUpper = strtoupper(pathinfo($actualFilename, PATHINFO_FILENAME));
            $cleanFilename = preg_replace('/^\d+\.?\s*/', '', $filenameUpper);
            
            foreach ($patterns as $pattern) {
                $patternUpper = strtoupper($pattern);
                
                // Check for matches
                if ($cleanFilename === $patternUpper || 
                    $filenameUpper === $patternUpper ||
                    strpos($cleanFilename, $patternUpper) !== false ||
                    strpos($filenameUpper, $patternUpper) !== false ||
                    preg_match('/^' . preg_quote($patternUpper, '/') . '(?:_[12])?(?:[\s\-_\.]|$)/i', $cleanFilename)) {
                    
                    $fullPath = $disk->path($filePath);
                    Log::info('Serving PDF (pattern match)', [
                        'ticket' => $ticketNumber,
                        'doc_type' => $docType,
                        'requested_filename' => $filename,
                        'actual_filename' => $actualFilename,
                        'matched_pattern' => $pattern,
                        'storage_path' => $filePath
                    ]);
                    return $this->servePDFFile($fullPath, $filename, $ticketNumber, 'ground-truth');
                }
            }
        }
        
        Log::error('PDF file not found (pattern matching)', [
            'ticket' => $ticketNumber,
            'doc_type' => $docType,
            'requested_filename' => $filename,
            'directory_path' => $directoryPath,
            'available_files' => array_map('basename', $pdfFiles)
        ]);
        abort(404, 'PDF file not found');
    }
    
    /**
     * Get filename patterns for a document type
     */
    private function getDocTypePatterns(string $docType): array
    {
        $patterns = [
            'KL' => ['KL', 'KONTRAK LAYANAN', 'Kontrak Layanan'],
            'NOPES' => ['NOPES', 'NOTA PESANAN', 'Nota Pesanan', 'NOTA_PESANAN'],
            'WO' => ['WO', 'WORK ORDER', 'Work Order', 'WORK_ORDER'],
            'SP' => ['SP', 'SURAT PESANAN', 'Surat Pesanan', 'SURAT_PESANAN'],
            'SPB' => ['SPB'],
            'NPK' => ['NPK'],
            'BAUT' => ['BAUT'],
            'BARD' => ['BARD'],
            'BAST' => ['BAST'],
            'BAK' => ['BAK', 'BAPL'],
            'P7' => ['P7'],
            'KB' => ['KB'],
            'SKM' => ['SKM'],
            'CL OBL' => ['CL OBL', 'CL_OBL', 'CLOBL']
        ];
        
        return $patterns[$docType] ?? [$docType];
    }

    /**
     * Generate PDF URL for Ground Truth
     * Note: Uses url() helper with admin route prefix
     */
    public function generateGroundTruthPDFUrl(string $ticketNumber, string $docType): string
    {
        $filename = "{$docType}.pdf";
        $prefix = config('admin.route.prefix', 'admin');
        return url("{$prefix}/pdf/ground-truth/{$ticketNumber}/{$docType}/{$filename}");
    }

    /**
     * ========================================
     * VALIDATION METHODS
     * ========================================
     */

    private function validateTicketNumber(string $ticketNumber): void
    {
        if (!preg_match('/^[0-9]{6}-[A-Z]{3}-[0-9]{3}$/', $ticketNumber)) {
            Log::warning('Invalid ticket number format', [
                'ticket' => $ticketNumber
            ]);
            abort(400, 'Invalid ticket number format');
        }
    }

    private function validateFilenameAdvance(string $filename): void
    {
        // Allow alphanumeric, space, underscore, hyphen, and period in doc type (for filenames like "A. SKM.pdf")
        if (!preg_match('/^[a-zA-Z0-9\s_.-]+\.pdf$/', $filename)) {
            Log::warning('Invalid filename format for advance review', [
                'filename' => $filename
            ]);
            abort(400, 'Invalid filename format');
        }
    }

    /**
     * ========================================
     * CORE PDF SERVING METHOD
     * ========================================
     */

    private function servePDFFile(
        string $pdfPath,
        string $filename,
        string $ticketNumber,
        string $context
    ): BinaryFileResponse {
        // Normalize path for cross-platform compatibility
        $pdfPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $pdfPath);

        if (!file_exists($pdfPath)) {
            Log::error('PDF file not found', [
                'ticket' => $ticketNumber,
                'path' => $pdfPath,
                'context' => $context
            ]);
            abort(404, 'PDF file not found');
        }

        if (!is_readable($pdfPath)) {
            Log::error('PDF file not readable', [
                'ticket' => $ticketNumber,
                'path' => $pdfPath,
                'context' => $context
            ]);
            abort(403, 'Cannot read PDF file');
        }

        Log::info('Successfully serving PDF', [
            'ticket' => $ticketNumber,
            'filename' => $filename,
            'context' => $context,
            'size' => filesize($pdfPath)
        ]);

        return response()->file($pdfPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}