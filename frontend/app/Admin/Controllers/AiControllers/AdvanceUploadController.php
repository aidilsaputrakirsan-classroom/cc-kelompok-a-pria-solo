<?php

namespace App\Admin\Controllers\AiControllers;

use OpenAdmin\Admin\Layout\Content;
use OpenAdmin\Admin\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use \App\Models\Company;
use \App\Jobs\ProcessAdvanceUploadJob;

class AdvanceUploadController extends Controller
{
    public function upload(Request $request)
    {
        $files = $request->file('files', []);

        if (!is_array($files)) {
            $files = $files ? [$files] : [];
        }

        $ticketNumber = $request->input('ticket');
        $companyId = $request->input('company_id');
        $namaMitra = $request->input('nama_mitra');
        $chunkIndex = $request->input('chunk_index');
        $totalChunks = $request->input('total_chunks');
        $isChunked = is_numeric($chunkIndex) && is_numeric($totalChunks) && (int) $totalChunks >= 2;

        $maxFileUploads = (int) ini_get('max_file_uploads') ?: 20;
        if (count($files) > $maxFileUploads) {
            Log::warning('Too many files in advance upload request', [
                'count' => count($files),
                'max' => $maxFileUploads
            ]);
            return response()->json([
                'error' => "Maksimal {$maxFileUploads} file per chunk. Anda mengirim " . count($files) . " file.",
                'max_file_uploads' => $maxFileUploads
            ], 400);
        }

        if (count($files) === 0) {
            Log::warning('No files received in advance upload request');
            return response()->json(['error' => 'No files received'], 400);
        }

        if (!$ticketNumber) {
            return response()->json(['error' => 'Ticket number is required'], 400);
        }

        if (!$companyId) {
            return response()->json(['error' => 'Company ID is required'], 400);
        }

        if (!$namaMitra) {
            return response()->json(['error' => 'Nama mitra is required'], 400);
        }

        $company = Company::find($companyId);
        if (!$company) {
            Log::warning('Company not found', ['company_id' => $companyId]);
            return response()->json(['error' => 'Company not found'], 404);
        }

        Log::info('Files received for information extraction', [
            'count' => count($files),
            'filenames' => array_map(fn($f) => $f->getClientOriginalName(), $files),
            'company_id' => $companyId
        ]);

        // ============================================================
        // STEP 1: Save files to advance-review storage before FastAPI call
        // ============================================================
        $disk = Storage::disk('public');
        $savedFiles = [];
        $multipart = [];
        $multipart[] = ['name' => 'ticket', 'contents' => $ticketNumber];
        $multipart[] = ['name' => 'nama_mitra', 'contents' => $namaMitra];

        foreach ($files as $file) {
            if ($file instanceof \Illuminate\Http\UploadedFile) {
                $originalFilename = $file->getClientOriginalName();
                
                // Save file to advance-review/{ticketNumber}/ with original filename
                $storagePath = "advance-review/{$ticketNumber}/{$originalFilename}";
                
                try {
                    // Ensure the directory exists (makeDirectory is safe if directory already exists)
                    $directoryPath = "advance-review/{$ticketNumber}";
                    if (!$disk->exists($directoryPath)) {
                        $disk->makeDirectory($directoryPath);
                    }
                    
                    // Store the file
                    $storedPath = $disk->putFileAs(
                        $directoryPath,
                        $file,
                        $originalFilename
                    );
                    
                    $savedFiles[] = [
                        'original_name' => $originalFilename,
                        'storage_path' => $storedPath,
                        'full_path' => $disk->path($storedPath)
                    ];
                    
                    Log::info('File saved to advance-review storage', [
                        'ticket' => $ticketNumber,
                        'original_name' => $originalFilename,
                        'storage_path' => $storedPath
                    ]);
                    
                    // Add to multipart for FastAPI (reopen file for sending)
                    $multipart[] = [
                        'name' => 'files',
                        'contents' => fopen($file->getRealPath(), 'r'),
                        'filename' => $originalFilename,
                    ];
                    
                } catch (\Exception $e) {
                    Log::error('Failed to save file to storage', [
                        'ticket' => $ticketNumber,
                        'filename' => $originalFilename,
                        'error' => $e->getMessage()
                    ]);
                    // Continue with other files even if one fails
                }
            }
        }

        if (empty($savedFiles)) {
            Log::warning('No files were saved to storage', [
                'ticket' => $ticketNumber,
                'files_count' => count($files)
            ]);
            return response()->json(['error' => 'Failed to save files to storage'], 500);
        }

        Log::info('Files saved to advance-review storage', [
            'ticket' => $ticketNumber,
            'saved_count' => count($savedFiles),
            'saved_files' => array_column($savedFiles, 'original_name'),
            'chunked' => $isChunked,
            'chunk_index' => $isChunked ? (int) $chunkIndex : null,
            'total_chunks' => $isChunked ? (int) $totalChunks : null
        ]);

        $isLastChunk = $isChunked && ((int) $chunkIndex === (int) $totalChunks - 1);

        if ($isChunked && !$isLastChunk) {
            return response()->json([
                'success' => true,
                'ticket' => $ticketNumber,
                'chunk_index' => (int) $chunkIndex,
                'total_chunks' => (int) $totalChunks,
                'message' => 'Chunk diterima.',
                'status' => 'chunk_received'
            ], 200);
        }

        // ============================================================
        // STEP 2: Run FastAPI processing after response (prevents locking + avoids 60s timeout)
        // ============================================================
        try {
            if ($isLastChunk) {
                $directoryPath = "advance-review/{$ticketNumber}";
                $allPaths = $disk->files($directoryPath);
                $fileNames = array_map(fn ($path) => basename($path), $allPaths);
            } else {
                $fileNames = array_column($savedFiles, 'original_name');
            }

            dispatch(function () use ($ticketNumber, $companyId, $namaMitra, $fileNames) {
                set_time_limit(0); // FastAPI/OCR can take many minutes
                (new ProcessAdvanceUploadJob($ticketNumber, $companyId, $namaMitra, $fileNames))->handle();
            })->afterResponse();

            Log::info('ProcessAdvanceUploadJob scheduled after response', [
                'ticket' => $ticketNumber,
                'company_id' => $companyId,
                'files_count' => count($fileNames)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File berhasil diunggah. Proses ekstraksi informasi sedang berlangsung di background.',
                'ticket' => $ticketNumber,
                'company_id' => $companyId,
                'company_name' => $company->nama_perusahaan,
                'files_count' => count($fileNames),
                'status' => 'processing'
            ], 202);

        } catch (\Throwable $e) {
            Log::error('Failed to dispatch ProcessAdvanceUploadJob', [
                'ticket' => $ticketNumber,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Server error', 'detail' => $e->getMessage()], 500);
        }
    }

    /**
     * Check if ticket processing is complete
     * 
     * @param Request $request
     * @param string $ticketNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus(Request $request, string $ticketNumber)
    {
        try {
            // Check if GroundTruth data exists for this ticket
            $ticket = \App\Models\Ticket::where('ticket_number', $ticketNumber)->first();

            if (!$ticket) {
                return response()->json([
                    'status' => 'processing',
                    'processed' => false,
                    'ticket_number' => $ticketNumber
                ]);
            }

            // If ticket exists, check if it has ground truth data
            $groundTruth = \App\Models\GroundTruth::where('ticket_id', $ticket->id)->first();

            if ($groundTruth) {
                return response()->json([
                    'status' => 'completed',
                    'processed' => true,
                    'ticket_number' => $ticketNumber,
                    'ticket_id' => $ticket->id
                ]);
            }

            return response()->json([
                'status' => 'processing',
                'processed' => false,
                'ticket_number' => $ticketNumber,
                'ticket_id' => $ticket->id
            ]);

        } catch (\Throwable $e) {
            Log::error('Error checking ticket status', [
                'ticket' => $ticketNumber,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Error checking status',
                'status' => 'unknown'
            ], 500);
        }
    }
}