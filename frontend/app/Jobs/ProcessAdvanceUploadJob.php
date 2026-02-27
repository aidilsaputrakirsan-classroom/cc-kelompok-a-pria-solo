<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\Ticket;
use App\Models\Company;
use App\Models\GroundTruth;

class ProcessAdvanceUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $ticketNumber;
    protected int $companyId;
    protected string $namaMitra;
    protected array $fileNames;

    /**
     * Create a new job instance.
     */
    public function __construct(string $ticketNumber, int $companyId, string $namaMitra, array $fileNames)
    {
        $this->ticketNumber = $ticketNumber;
        $this->companyId = $companyId;
        $this->namaMitra = $namaMitra;
        $this->fileNames = $fileNames;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting ProcessAdvanceUploadJob', [
            'ticket' => $this->ticketNumber,
            'company_id' => $this->companyId,
            'files_count' => count($this->fileNames)
        ]);

        try {
            // Build multipart data from saved files
            $multipart = [];
            $multipart[] = ['name' => 'ticket', 'contents' => $this->ticketNumber];
            $multipart[] = ['name' => 'nama_mitra', 'contents' => $this->namaMitra];

            $disk = Storage::disk('public');
            
            foreach ($this->fileNames as $fileName) {
                $filePath = "advance-review/{$this->ticketNumber}/{$fileName}";
                
                if ($disk->exists($filePath)) {
                    $fullPath = $disk->path($filePath);
                    $multipart[] = [
                        'name' => 'files',
                        'contents' => fopen($fullPath, 'r'),
                        'filename' => $fileName,
                    ];
                } else {
                    Log::warning('File not found for upload to FastAPI', [
                        'ticket' => $this->ticketNumber,
                        'file' => $fileName,
                        'path' => $filePath
                    ]);
                }
            }

            // Send to FastAPI for information extraction
            $client = new Client(['timeout' => 300]); // 5 minutes for background job
            $pythonApiUrl = env('URL_VM_PYTHON');
            
            Log::info('Sending files to FastAPI', [
                'ticket' => $this->ticketNumber,
                'url' => $pythonApiUrl . '/information-extraction',
                'files_count' => count($this->fileNames)
            ]);

            $res = $client->request('POST', $pythonApiUrl . '/information-extraction', [
                'multipart' => $multipart
            ]);

            $responseBody = $res->getBody()->getContents();
            $json = json_decode($responseBody, true);

            Log::info('Response from FastAPI (Information Extraction)', [
                'status' => $res->getStatusCode(),
                'ticket' => $this->ticketNumber,
                'extraction_status' => $json['status'] ?? 'unknown',
                'total_files' => $json['total_files'] ?? 0,
                'ground_truth_count' => count($json['ground_truth_results'] ?? [])
            ]);

            // Save to database
            $this->saveToDatabase($json);

            Log::info('ProcessAdvanceUploadJob completed successfully', [
                'ticket' => $this->ticketNumber
            ]);

        } catch (RequestException $e) {
            $msg = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            Log::error('Guzzle RequestException in ProcessAdvanceUploadJob', [
                'ticket' => $this->ticketNumber,
                'message' => $msg
            ]);
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Unexpected error in ProcessAdvanceUploadJob', [
                'ticket' => $this->ticketNumber,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function saveToDatabase(array $jsonData): void
    {
        DB::beginTransaction();

        try {
            $groundTruthResults = $jsonData['ground_truth_results'] ?? [];

            // Check if ticket exists and delete old data
            $existingTicket = Ticket::where('ticket_number', $this->ticketNumber)->first();

            if ($existingTicket) {
                Log::info('Existing ticket found - Deleting all old data', [
                    'ticket_id' => $existingTicket->id,
                    'ticket_number' => $this->ticketNumber
                ]);

                $disk = Storage::disk('public');
                $folderPath = "advance-review/{$this->ticketNumber}";
                
                // Delete associated ground truths
                $deletedGroundTruths = GroundTruth::where('ticket_id', $existingTicket->id)->delete();

                Log::info('Old ground truth data deleted', [
                    'ticket_id' => $existingTicket->id,
                    'deleted_count' => $deletedGroundTruths
                ]);

                $existingTicket->delete();
            }

            // Create new ticket
            $company = Company::find($this->companyId);
            $projectTitle = $this->extractProjectTitle($groundTruthResults);

            $ticket = Ticket::create([
                'ticket_number' => $this->ticketNumber,
                'company_id' => $this->companyId,
                'project_title' => $projectTitle,
            ]);

            Log::info('New ticket created', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'company_id' => $ticket->company_id,
                'project_title' => $ticket->project_title
            ]);

            // Save ground truth data
            $dataToStore = $groundTruthResults;
            $dataToStore['_metadata'] = [
                'nama_mitra' => $this->namaMitra,
                'extraction_status' => $jsonData['status'] ?? null,
                'extracted_at' => now()->toISOString(),
                'doc_types' => array_keys($groundTruthResults),
                'total_doc_types' => count($groundTruthResults)
            ];

            $groundTruth = GroundTruth::create([
                'ticket_id' => $ticket->id,
                'doc_type' => 'Ground Truth',
                'extracted_data' => $dataToStore,
            ]);

            Log::info('Single Ground Truth created', [
                'ground_truth_id' => $groundTruth->id,
                'ticket_id' => $ticket->id,
                'doc_types' => array_keys($groundTruthResults),
                'total_doc_types' => count($groundTruthResults)
            ]);

            DB::commit();

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Failed to save information extraction to database', [
                'ticket' => $this->ticketNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function extractProjectTitle(array $groundTruthResults): ?string
    {
        $priority = ['KL', 'SP', 'WO', 'NOPES', 'NOTA_PESANAN'];

        foreach ($priority as $docType) {
            if (isset($groundTruthResults[$docType])) {
                $data = $groundTruthResults[$docType];

                if (isset($data['judul_project']) && !empty($data['judul_project'])) {
                    return $data['judul_project'];
                }
                if (isset($data['nama_project']) && !empty($data['nama_project'])) {
                    return $data['nama_project'];
                }
                if (isset($data['project_title']) && !empty($data['project_title'])) {
                    return $data['project_title'];
                }
            }
        }

        return null;
    }
}
