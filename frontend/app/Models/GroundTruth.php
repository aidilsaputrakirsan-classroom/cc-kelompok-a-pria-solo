<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroundTruth extends Model
{
    use HasFactory;

    protected $table = 'ground_truths';

    protected $fillable = [
        'ticket_id',
        'doc_type',
        'extracted_data',
    ];

    protected $casts = [
        'extracted_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Relationship: Ground Truth belongs to Ticket
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Relationship: Ground Truth has many Advance Review Results
     */
    public function advanceReviewResults(): HasMany
    {
        return $this->hasMany(AdvanceReviewResult::class);
    }

    // ========================================
    // ADVANCE REVIEW HELPERS
    // ========================================

    /**
     * Get advance review result for specific doc_type
     */
    public function getAdvanceReviewForDocType(string $docType): ?AdvanceReviewResult
    {
        return $this->advanceReviewResults()
            ->where('doc_type', $docType)
            ->first();
    }

    /**
     * Check if has advance review for doc_type
     */
    public function hasAdvanceReviewForDocType(string $docType): bool
    {
        return $this->advanceReviewResults()
            ->where('doc_type', $docType)
            ->exists();
    }

    /**
     * Get all advance review doc_types
     */
    public function getAdvanceReviewDocTypes(): array
    {
        return $this->advanceReviewResults()
            ->pluck('doc_type')
            ->toArray();
    }

    /**
     * Get advance review summary
     */
    public function getAdvanceReviewSummary(): array
    {
        return [
            'total' => $this->advanceReviewResults()->count(),
            'success' => $this->advanceReviewResults()->where('status', 'success')->count(),
            'error' => $this->advanceReviewResults()->where('status', 'error')->count(),
            'doc_types' => $this->getAdvanceReviewDocTypes(),
        ];
    }

    // ========================================
    // EXTRACTED DATA HELPERS
    // ========================================

    /**
     * Get specific field from extracted_data
     */
    public function getField(string $key, $default = null)
    {
        return data_get($this->extracted_data, $key, $default);
    }

    /**
     * Set specific field in extracted_data
     */
    public function setField(string $key, $value): void
    {
        $data = $this->extracted_data ?? [];
        data_set($data, $key, $value);
        $this->extracted_data = $data;
    }

    /**
     * Check if field exists in extracted_data
     */
    public function hasField(string $key): bool
    {
        return data_get($this->extracted_data, $key) !== null;
    }

    /**
     * Get all field keys from extracted_data
     */
    public function getFieldKeys(): array
    {
        return array_keys($this->extracted_data ?? []);
    }

    /**
     * Get metadata from extracted_data
     */
    public function getMetadata(): ?array
    {
        return $this->getField('_metadata');
    }

    /**
     * Set metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->setField('_metadata', $metadata);
    }

    /**
     * Get extracted data without metadata
     */
    public function getDataWithoutMetadata(): array
    {
        $data = $this->extracted_data ?? [];
        unset($data['_metadata']);
        return $data;
    }

    // ========================================
    // DOCUMENT TYPE HELPERS
    // ========================================

    /**
     * Check if this is a primary document type
     */
    public function isPrimaryDocument(): bool
    {
        $primaryTypes = ['Kontrak Layanan', 'Work Order', 'Surat Pesanan', 'Nota Pesanan'];
        return in_array($this->doc_type, $primaryTypes);
    }

    /**
     * Check if this is an optional document type
     */
    public function isOptionalDocument(): bool
    {
        $optionalTypes = ['NPK', 'BAUT', 'BARD', 'BAST', 'P7'];
        return in_array($this->doc_type, $optionalTypes);
    }

    /**
     * Get document category
     */
    public function getDocumentCategory(): string
    {
        if ($this->isPrimaryDocument()) {
            return 'primary';
        }
        if ($this->isOptionalDocument()) {
            return 'optional';
        }
        return 'unknown';
    }

    // ========================================
    // COMMON FIELDS BY DOC TYPE
    // ========================================

    /**
     * Get common fields based on doc_type
     */
    public function getCommonFields(): array
    {
        switch ($this->doc_type) {
            case 'NPK':
                return [
                    'SID' => $this->getField('SID'),
                    'prorate' => $this->getField('prorate'),
                    'nilai_satuan_usage' => $this->getField('nilai_satuan_usage'),
                ];
            case 'Nota Pesanan':
            case 'Surat Pesanan':
            case 'Work Order':
            case 'Kontrak Layanan':
                return [
                    'judul_project' => $this->getField('judul_project'),
                    'nomor_surat_utama' => $this->getField('nomor_surat_utama'),
                    'tanggal_kontrak' => $this->getField('tanggal_kontrak'),
                    'delivery' => $this->getField('delivery'),
                    'dpp' => $this->getField('dpp'),
                    'dpp_raw' => $this->getField('dpp_raw'),
                    'nama_pelanggan' => $this->getField('nama_pelanggan'),
                ];
            case 'BAST':
                return [
                    'nomor_telkom' => $this->getField('nomor_telkom'),
                    'nomor_mitra' => $this->getField('nomor_mitra'),
                    'tanggal_bast' => $this->getField('tanggal_bast'),
                ];
            case 'BAUT':
                return [
                    'tanggal_baut' => $this->getField('tanggal_baut'),
                ];
            case 'BARD':
                return [
                    'tanggal_bard' => $this->getField('tanggal_bard'),
                ];
            case 'P7':
                return [
                    'nomor_surat_penetapan_calon_mitra' => $this->getField('nomor_surat_penetapan_calon_mitra'),
                    'tanggal_surat_penetapan_calon_mitra' => $this->getField('tanggal_surat_penetapan_calon_mitra'),
                ];
            default:
                return $this->getDataWithoutMetadata();
        }
    }

    // ========================================
    // VALIDATION
    // ========================================

    /**
     * Validate required fields based on doc_type
     */
    public function validateRequiredFields(): array
    {
        switch ($this->doc_type) {
            case 'NPK':
                $requiredFields = ['SID', 'nilai_satuan_usage'];
                break;
            case 'Nota Pesanan':
            case 'Surat Pesanan':
            case 'Work Order':
            case 'Kontrak Layanan':
                $requiredFields = [
                    'nomor_surat_utama',
                    'tanggal_kontrak',
                    'dpp_raw',
                ];
                break;
            case 'BAST':
                $requiredFields = ['nomor_telkom', 'nomor_mitra', 'tanggal_bast'];
                break;
            case 'BAUT':
                $requiredFields = ['tanggal_baut'];
                break;
            case 'BARD':
                $requiredFields = ['tanggal_bard'];
                break;
            case 'P7':
                $requiredFields = ['nomor_surat_penetapan_calon_mitra', 'tanggal_surat_penetapan_calon_mitra'];
                break;
            default:
                $requiredFields = [];
        }

        $missing = [];
        foreach ($requiredFields as $field) {
            if (!$this->hasField($field)) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    /**
     * Check if all required fields are present
     */
    public function hasAllRequiredFields(): bool
    {
        return empty($this->validateRequiredFields());
    }

    /**
     * Get validation status
     */
    public function getValidationStatus(): array
    {
        $missingFields = $this->validateRequiredFields();

        return [
            'is_valid' => empty($missingFields),
            'missing_fields' => $missingFields,
            'field_count' => count($this->getDataWithoutMetadata()),
            'required_field_count' => $this->getRequiredFieldCount(),
        ];
    }

    /**
     * Get count of required fields for this doc_type
     */
    private function getRequiredFieldCount(): int
    {
        switch ($this->doc_type) {
            case 'NPK':
                return 2;
            case 'Nota Pesanan':
            case 'Surat Pesanan':
            case 'Work Order':
            case 'Kontrak Layanan':
                return 3;
            case 'BAST':
                return 3;
            case 'BAUT':
                return 1;
            case 'BARD':
                return 1;
            case 'P7':
                return 2;
            default:
                return 0;
        }
    }

    // ========================================
    // SUMMARY & UTILITIES
    // ========================================

    /**
     * Get summary of this ground truth
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'doc_type' => $this->doc_type,
            'category' => $this->getDocumentCategory(),
            'ticket_number' => $this->ticket->ticket_number ?? null,
            'field_count' => count($this->getDataWithoutMetadata()),
            'has_metadata' => $this->hasField('_metadata'),
            'common_fields' => $this->getCommonFields(),
            'validation_status' => $this->getValidationStatus(),
            'advance_review_summary' => $this->getAdvanceReviewSummary(),
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        ];
    }

    /**
     * Get frontend key for this doc_type
     * Maps database doc_type to JavaScript GROUND_TRUTH_DATA key
     * Must match JavaScript DOC_TYPE_MAPPING values
     */
    public function getFrontendKey(): string
    {
        // Map to short codes that JavaScript expects
        $mapping = [
            'Kontrak Layanan' => 'KL',
            'Nota Pesanan' => 'NOPES',
            'Work Order' => 'WO',
            'Surat Pesanan' => 'SP',
            'NPK' => 'NPK',
            'BAUT' => 'BAUT',
            'BARD' => 'BARD',
            'BAST' => 'BAST',
            'P7' => 'P7'
        ];

        return $mapping[$this->doc_type] ?? strtoupper(str_replace(' ', '_', $this->doc_type));
    }

    /**
     * Get database doc_type from frontend key
     * Reverse of getFrontendKey() - converts JavaScript keys back to database format
     */
    public static function getDocTypeFromFrontendKey(string $frontendKey): string
    {
        $mapping = [
            'KL' => 'Kontrak Layanan',
            'NOPES' => 'Nota Pesanan',
            'WO' => 'Work Order',
            'SP' => 'Surat Pesanan',
            'NPK' => 'NPK',
            'BAUT' => 'BAUT',
            'BARD' => 'BARD',
            'BAST' => 'BAST',
            'P7' => 'P7'
        ];

        return $mapping[$frontendKey] ?? ucwords(strtolower(str_replace('_', ' ', $frontendKey)));
    }

    /**
     * Clone this ground truth to another ticket
     */
    public function cloneTo(int $targetTicketId): self
    {
        return self::create([
            'ticket_id' => $targetTicketId,
            'doc_type' => $this->doc_type,
            'extracted_data' => $this->extracted_data,
        ]);
    }

    /**
     * Merge data from another ground truth
     */
    public function mergeFrom(GroundTruth $other): void
    {
        $currentData = $this->extracted_data ?? [];
        $otherData = $other->extracted_data ?? [];

        $merged = array_merge($otherData, $currentData);
        $this->extracted_data = $merged;
    }

    // ========================================
    // QUERY SCOPES
    // ========================================

    /**
     * Scope: Filter by doc_type
     */
    public function scopeOfType($query, string $docType)
    {
        return $query->where('doc_type', $docType);
    }

    /**
     * Scope: Filter by ticket
     */
    public function scopeForTicket($query, int $ticketId)
    {
        return $query->where('ticket_id', $ticketId);
    }

    /**
     * Scope: Get by ticket number
     */
    public function scopeForTicketNumber($query, string $ticketNumber)
    {
        return $query->whereHas('ticket', function ($q) use ($ticketNumber) {
            $q->where('ticket_number', $ticketNumber);
        });
    }

    /**
     * Scope: Primary documents only
     */
    public function scopePrimary($query)
    {
        return $query->whereIn('doc_type', [
            'Kontrak Layanan',
            'Work Order',
            'Surat Pesanan',
            'Nota Pesanan'
        ]);
    }

    /**
     * Scope: Optional documents only
     */
    public function scopeOptional($query)
    {
        return $query->whereIn('doc_type', ['NPK', 'BAUT', 'BARD', 'BAST', 'P7']);
    }
}