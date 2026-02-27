<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    use HasFactory;

    const TYPE_PERPANJANGAN = 'Perpanjangan';
    const TYPE_NON_PERPANJANGAN = 'Non-Perpanjangan';

    protected $fillable = [
        'ticket_number',
        'company_id',
        'project_title',
        'type',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    /**
     * Route key name for URL binding
     */
    public function getRouteKeyName(): string
    {
        return 'ticket_number';
    }

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Relationship: Ticket belongs to Company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relationship: Ticket has one Ticket Note
     */
    public function notes(): HasOne
    {
        return $this->hasOne(TicketNote::class);
    }

    /**
     * Relationship: Ticket has many Ground Truths
     */
    public function groundTruths(): HasMany
    {
        return $this->hasMany(GroundTruth::class);
    }

    /**
     * Relationship: Ticket has one Ground Truth (primary/first)
     * FIXED: Use oldest() instead of oldestOfMany() to avoid ambiguous column error
     */
    public function groundTruth(): HasOne
    {
        return $this->hasOne(GroundTruth::class)->oldest('ground_truths.id');
    }

    /**
     * Relationship: Ticket has many Typo Errors
     */
    public function typoErrors(): HasMany
    {
        return $this->hasMany(TypoError::class);
    }

    /**
     * Relationship: Ticket has many Price Validations
     */
    public function priceValidations(): HasMany
    {
        return $this->hasMany(PriceValidation::class);
    }

    /**
     * Relationship: Ticket has many Date Validations
     */
    public function dateValidations(): HasMany
    {
        return $this->hasMany(DateValidation::class);
    }

    // ========================================
    // GROUND TRUTH HELPERS
    // ========================================

    /**
     * Get ground truth by doc_type
     */
    public function getGroundTruth(string $docType): ?GroundTruth
    {
        return $this->groundTruths()
            ->where('doc_type', $docType)
            ->first();
    }

    /**
     * Get primary ground truth (based on priority)
     */
    public function getPrimaryGroundTruth(): ?GroundTruth
    {
        $priority = ['KL', 'SP', 'WO', 'NOTA_PESANAN'];

        foreach ($priority as $docType) {
            $gt = $this->getGroundTruth($docType);
            if ($gt) {
                return $gt;
            }
        }

        return $this->groundTruths()->first();
    }

    /**
     * Get optional ground truths
     */
    public function getOptionalGroundTruths()
    {
        return $this->groundTruths()
            ->whereIn('doc_type', ['NPK', 'BAUT', 'BARD', 'P7'])
            ->get();
    }

    /**
     * Check if has ground truth for specific doc_type
     */
    public function hasGroundTruth(string $docType): bool
    {
        return $this->groundTruths()
            ->where('doc_type', $docType)
            ->exists();
    }

    /**
     * Check if has any ground truth
     */
    public function hasAnyGroundTruth(): bool
    {
        return $this->groundTruths()->exists();
    }

    /**
     * Get all ground truth doc_types
     */
    public function getGroundTruthDocTypes(): array
    {
        return $this->groundTruths()
            ->pluck('doc_type')
            ->toArray();
    }

    /**
     * Get merged ground truth data from all documents
     */
    public function getMergedGroundTruthData(): array
    {
        $priority = ['KL', 'SP', 'WO', 'NOTA_PESANAN', 'NPK', 'BAUT', 'BARD', 'P7'];
        $mergedData = [];

        foreach ($priority as $docType) {
            $gt = $this->getGroundTruth($docType);
            if ($gt) {
                $mergedData = array_merge($gt->extracted_data ?? [], $mergedData);
            }
        }

        return $mergedData;
    }

    // ========================================
    // VALIDATION ERRORS HELPERS
    // ========================================

    /**
     * Get total errors count (attribute accessor)
     */
    public function getTotalErrorsAttribute(): int
    {
        return $this->typoErrors()->count()
            + $this->priceValidations()->count()
            + $this->dateValidations()->where('is_valid', false)->count();
    }

    /**
     * Get all bounding boxes from all validation types
     */
    public function getAllBoundingBoxes()
    {
        $typoBoxes = $this->typoErrors()->with('boundingBoxes')->get()
            ->pluck('boundingBoxes')->flatten();

        $priceBoxes = $this->priceValidations()->with('boundingBoxes')->get()
            ->pluck('boundingBoxes')->flatten();

        $dateBoxes = $this->dateValidations()->with('boundingBoxes')->get()
            ->pluck('boundingBoxes')->flatten();

        return collect()
            ->concat($typoBoxes)
            ->concat($priceBoxes)
            ->concat($dateBoxes);
    }

    /**
     * Check if has any validation errors
     */
    public function hasValidationErrors(): bool
    {
        return $this->typoErrors()->exists()
            || $this->priceValidations()->exists()
            || $this->dateValidations()->where('is_valid', false)->exists();
    }

    /**
     * Get validation summary
     */
    public function getValidationSummary(): array
    {
        return [
            'typo_errors' => $this->typoErrors()->count(),
            'price_errors' => $this->priceValidations()->count(),
            'date_errors' => $this->dateValidations()->where('is_valid', false)->count(),
            'total_errors' => $this->total_errors,
        ];
    }

    /**
     * Get all validation errors with relationships
     */
    public function getAllValidationErrors(): array
    {
        return [
            'typos' => $this->typoErrors()->with('boundingBoxes')->get(),
            'prices' => $this->priceValidations()->with('boundingBoxes')->get(),
            'dates' => $this->dateValidations()->where('is_valid', false)->with('boundingBoxes')->get(),
        ];
    }

    // ========================================
    // NOTES HELPERS
    // ========================================

    /**
     * Check if has notes
     */
    public function hasNotes(): bool
    {
        return $this->notes()->exists();
    }

    /**
     * Get note by category
     */
    public function getNoteByCategory(string $category): ?string
    {
        if (!$this->hasNotes()) {
            return null;
        }

        return $this->notes->getNoteByCategory($category);
    }

    // ========================================
    // STATUS HELPERS
    // ========================================

    /**
     * Check if ticket is ready for processing
     */
    public function isReadyForProcessing(): bool
    {
        return $this->hasAnyGroundTruth();
    }

    /**
     * Check if ticket is complete (no errors)
     */
    public function isComplete(): bool
    {
        return $this->hasAnyGroundTruth() && !$this->hasValidationErrors();
    }

    /**
     * Get completion status
     */
    public function getCompletionStatus(): array
    {
        return [
            'has_ground_truth' => $this->hasAnyGroundTruth(),
            'has_primary_gt' => $this->getPrimaryGroundTruth() !== null,
            'has_validations' => $this->hasValidationErrors(),
            'has_notes' => $this->hasNotes(),
            'ground_truth_count' => $this->groundTruths()->count(),
            'validation_summary' => $this->getValidationSummary(),
            'is_ready' => $this->isReadyForProcessing(),
            'is_complete' => $this->isComplete(),
        ];
    }

    /**
     * Get ticket summary
     */
    public function getSummary(): array
    {
        $primary = $this->getPrimaryGroundTruth();

        return [
            'ticket_number' => $this->ticket_number,
            'project_title' => $this->project_title,
            'company' => $this->company ? $this->company->name : null,
            'type' => $this->type,
            'primary_doc_type' => $primary ? $primary->doc_type : null,
            'ground_truths' => $this->getGroundTruthDocTypes(),
            'validations' => $this->getValidationSummary(),
            'completion_status' => $this->getCompletionStatus(),
        ];
    }

    // ========================================
    // TYPE HELPERS
    // ========================================

    /**
     * Check if type is Perpanjangan
     */
    public function isPerpanjangan(): bool
    {
        return $this->type === self::TYPE_PERPANJANGAN;
    }

    /**
     * Check if type is Non-Perpanjangan
     */
    public function isNonPerpanjangan(): bool
    {
        return $this->type === self::TYPE_NON_PERPANJANGAN;
    }

    /**
     * Get available types
     */
    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_PERPANJANGAN,
            self::TYPE_NON_PERPANJANGAN,
        ];
    }

    // ========================================
    // QUERY SCOPES
    // ========================================

    /**
     * Scope: Filter by company
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: Search by ticket number or project title
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('ticket_number', 'like', "%{$search}%")
                ->orWhere('project_title', 'like', "%{$search}%");
        });
    }

    /**
     * Scope: Tickets with validation errors
     */
    public function scopeWithErrors($query)
    {
        return $query->whereHas('typoErrors')
            ->orWhereHas('priceValidations')
            ->orWhereHas('dateValidations', function ($q) {
                $q->where('is_valid', false);
            });
    }

    /**
     * Scope: Complete tickets (no errors)
     */
    public function scopeComplete($query)
    {
        return $query->whereDoesntHave('typoErrors')
            ->whereDoesntHave('priceValidations')
            ->whereDoesntHave('dateValidations', function ($q) {
                $q->where('is_valid', false);
            });
    }

    /**
     * Scope: Filter by type Perpanjangan
     */
    public function scopePerpanjangan($query)
    {
        return $query->where('type', self::TYPE_PERPANJANGAN);
    }

    /**
     * Scope: Filter by type Non-Perpanjangan
     */
    public function scopeNonPerpanjangan($query)
    {
        return $query->where('type', self::TYPE_NON_PERPANJANGAN);
    }

    /**
     * Scope: Filter by specific type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}