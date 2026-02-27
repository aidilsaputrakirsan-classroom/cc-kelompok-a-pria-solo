<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvanceReviewResult extends Model
{
    use HasFactory;

    protected $table = 'advance_review_results';

    protected $fillable = [
        'ground_truth_id',
        'doc_type',
        'status',
        'error_message',
        'review_data',
    ];

    protected $casts = [
        'review_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Relationship: Advance Review Result belongs to Ground Truth
     */
    public function groundTruth(): BelongsTo
    {
        return $this->belongsTo(GroundTruth::class);
    }

    /**
     * Relationship: Get ticket through ground truth
     */
    public function ticket(): BelongsTo
    {
        return $this->groundTruth->ticket();
    }

    // ========================================
    // STATUS HELPERS
    // ========================================

    /**
     * Check if review is successful
     */
    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if review has error
     */
    public function hasError(): bool
    {
        return $this->status === 'error';
    }

    /**
     * Check if review is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if review is completed (success or error, not pending)
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['success', 'error']);
    }

    // ========================================
    // REVIEW DATA HELPERS
    // ========================================

    /**
     * Get specific stage data from review_data
     */
    public function getStage(string $stageName, $default = null)
    {
        return data_get($this->review_data, $stageName, $default);
    }

    /**
     * Get stage review text
     */
    public function getStageReview(string $stageName): ?string
    {
        return data_get($this->review_data, "{$stageName}.review");
    }

    /**
     * Get stage keterangan text
     */
    public function getStageKeterangan(string $stageName): ?string
    {
        return data_get($this->review_data, "{$stageName}.keterangan");
    }

    /**
     * Check if stage exists
     */
    public function hasStage(string $stageName): bool
    {
        return isset($this->review_data[$stageName]);
    }

    /**
     * Get all stage names
     */
    public function getStageNames(): array
    {
        return array_keys($this->review_data ?? []);
    }

    /**
     * Count total stages
     */
    public function getStageCount(): int
    {
        return count($this->review_data ?? []);
    }

    /**
     * Get all stages data
     */
    public function getAllStages(): array
    {
        return $this->review_data ?? [];
    }

    /**
     * Check if any stage has errors/warnings
     */
    public function hasStageIssues(): bool
    {
        if (empty($this->review_data)) {
            return false;
        }

        foreach ($this->review_data as $stage) {
            $keterangan = $stage['keterangan'] ?? '';

            // Check for common error indicators
            if (
                str_contains(strtolower($keterangan), 'ada yang salah') ||
                str_contains(strtolower($keterangan), 'tidak ditemukan') ||
                str_contains(strtolower($keterangan), 'missing') ||
                str_contains(strtolower($keterangan), 'error')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get stages with issues
     */
    public function getStagesWithIssues(): array
    {
        $stagesWithIssues = [];

        foreach ($this->review_data ?? [] as $stageName => $stageData) {
            $keterangan = $stageData['keterangan'] ?? '';

            if (
                str_contains(strtolower($keterangan), 'ada yang salah') ||
                str_contains(strtolower($keterangan), 'tidak ditemukan') ||
                str_contains(strtolower($keterangan), 'missing') ||
                str_contains(strtolower($keterangan), 'error')
            ) {
                $stagesWithIssues[$stageName] = $stageData;
            }
        }

        return $stagesWithIssues;
    }

    // ========================================
    // ERROR HANDLING
    // ========================================

    /**
     * Get formatted error message
     */
    public function getFormattedErrorMessage(): ?string
    {
        if (!$this->error_message) {
            return null;
        }

        // Clean up error message
        $message = $this->error_message;

        // Remove stack traces
        if (str_contains($message, '#0')) {
            $message = explode('#0', $message)[0];
        }

        return trim($message);
    }

    /**
     * Check if error is due to missing prompt template
     */
    public function isMissingPromptTemplate(): bool
    {
        return $this->hasError() &&
            str_contains($this->error_message ?? '', 'No prompt template found');
    }

    /**
     * Check if error is validation error
     */
    public function isValidationError(): bool
    {
        return $this->hasError() &&
            str_contains($this->error_message ?? '', 'validation failed');
    }

    /**
     * Check if error is API error
     */
    public function isApiError(): bool
    {
        return $this->hasError() &&
            (str_contains($this->error_message ?? '', 'API') ||
                str_contains($this->error_message ?? '', 'Error code'));
    }

    /**
     * Get error type
     */
    public function getErrorType(): ?string
    {
        if (!$this->hasError()) {
            return null;
        }

        if ($this->isMissingPromptTemplate()) {
            return 'missing_template';
        }

        if ($this->isValidationError()) {
            return 'validation_error';
        }

        if ($this->isApiError()) {
            return 'api_error';
        }

        return 'unknown_error';
    }

    // ========================================
    // SUMMARY & UTILITIES
    // ========================================

    /**
     * Get summary
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'doc_type' => $this->doc_type,
            'status' => $this->status,
            'is_success' => $this->isSuccess(),
            'has_error' => $this->hasError(),
            'error_type' => $this->getErrorType(),
            'error_message' => $this->getFormattedErrorMessage(),
            'stage_count' => $this->getStageCount(),
            'stages' => $this->getStageNames(),
            'has_stage_issues' => $this->hasStageIssues(),
            'stages_with_issues' => array_keys($this->getStagesWithIssues()),
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        ];
    }

    /**
     * Get detailed report
     */
    public function getDetailedReport(): array
    {
        return [
            'basic_info' => [
                'id' => $this->id,
                'doc_type' => $this->doc_type,
                'status' => $this->status,
                'ground_truth_id' => $this->ground_truth_id,
            ],
            'status_flags' => [
                'is_success' => $this->isSuccess(),
                'has_error' => $this->hasError(),
                'is_pending' => $this->isPending(),
                'is_completed' => $this->isCompleted(),
            ],
            'review_data' => [
                'stage_count' => $this->getStageCount(),
                'stages' => $this->getAllStages(),
                'has_stage_issues' => $this->hasStageIssues(),
                'stages_with_issues' => $this->getStagesWithIssues(),
            ],
            'error_info' => [
                'has_error' => $this->hasError(),
                'error_type' => $this->getErrorType(),
                'error_message' => $this->getFormattedErrorMessage(),
                'raw_error_message' => $this->error_message,
            ],
            'timestamps' => [
                'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
                'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
            ],
        ];
    }

    // ========================================
    // QUERY SCOPES
    // ========================================

    /**
     * Scope: Filter by status success
     */
    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope: Filter by status error
     */
    public function scopeWithErrors($query)
    {
        return $query->where('status', 'error');
    }

    /**
     * Scope: Filter by status pending
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Filter by completed (success or error)
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['success', 'error']);
    }

    /**
     * Scope: Filter by doc_type
     */
    public function scopeOfType($query, string $docType)
    {
        return $query->where('doc_type', $docType);
    }

    /**
     * Scope: Filter by ground truth
     */
    public function scopeForGroundTruth($query, int $groundTruthId)
    {
        return $query->where('ground_truth_id', $groundTruthId);
    }

    /**
     * Scope: Recent first
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope: Oldest first
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    // ========================================
    // STATIC HELPERS
    // ========================================

    /**
     * Get review_data from advance_review_results based on pdfFilename
     * 
     * @param string $ticketNumber The ticket number
     * @param string $pdfFilename The PDF filename (e.g., "BAST.pdf", "NPK.pdf")
     * @return array|null The review_data array or null if not found
     */
    public static function getReviewDataByPdfFilename(string $ticketNumber, string $pdfFilename): ?array
    {
        // Extract doc_type from filename (remove .pdf extension)
        $docType = pathinfo($pdfFilename, PATHINFO_FILENAME);
        
        // Query advance_review_results through ground_truth relationship
        // Use nested whereHas to filter by ticket_number
        $reviewResult = static::whereHas('groundTruth', function ($query) use ($ticketNumber) {
            $query->whereHas('ticket', function ($q) use ($ticketNumber) {
                $q->where('ticket_number', $ticketNumber);
            });
        })
        ->where('doc_type', $docType)
        ->first();
        
        return $reviewResult ? $reviewResult->review_data : null;
    }

    /**
     * Get typo, date, and price counts based on pdfFilename
     * 
     * @param string $ticketNumber The ticket number
     * @param string $pdfFilename The PDF filename (e.g., "BAST.pdf", "NPK.pdf")
     * @return array Array with 'typo_count', 'date_count', 'price_count', 'total_errors'
     */
    public static function getCountsByPdfFilename(string $ticketNumber, string $pdfFilename): array
    {
        // Extract doc_type from filename (remove .pdf extension)
        $docType = pathinfo($pdfFilename, PATHINFO_FILENAME);
        
        // Get ticket ID
        $ticket = \App\Models\Ticket::where('ticket_number', $ticketNumber)->first();
        if (!$ticket) {
            return ['typo_count' => 0, 'date_count' => 0, 'price_count' => 0, 'total_errors' => 0];
        }
        
        // Get counts from database tables
        $typoCount = \App\Models\TypoError::where('ticket_id', $ticket->id)
            ->where('doc_type', $docType)
            ->count();

        $dateCount = \App\Models\DateValidation::where('ticket_id', $ticket->id)
            ->where('doc_type', $docType)
            ->where('is_valid', false)
            ->count();

        $priceCount = \App\Models\PriceValidation::where('ticket_id', $ticket->id)
            ->where('doc_type', $docType)
            ->count();

        return [
            'typo_count' => $typoCount,
            'date_count' => $dateCount,
            'price_count' => $priceCount,
            'total_errors' => $typoCount + $dateCount + $priceCount
        ];
    }
}