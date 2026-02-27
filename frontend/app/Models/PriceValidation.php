<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceValidation extends Model
{
    use HasFactory;

    const UPDATED_AT = null; // No updated_at column

    protected $fillable = [
        'ticket_id', 
        'doc_type',
        'extracted_text',
        'correction',
    ];

    /**
     * Get the ticket that owns this price validation
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get all bounding boxes for this price validation (polymorphic)
     */
    public function boundingBoxes()
    {
        return $this->morphMany(BoundingBox::class, 'boundable');
    }

    /**
     * Scope: Filter by ticket
     */
    public function scopeForTicket($query, int $ticketId)
    {
        return $query->where('ticket_id', $ticketId);
    }

    /**
     * Check if price has correction
     */
    public function hasCorrection(): bool
    {
        return !empty($this->correction);
    }

    /**
     * Get display text (correction if available, otherwise extracted)
     */
    public function getDisplayTextAttribute(): string
    {
        return $this->correction ?? $this->extracted_text;
    }

    /**
     * Check if price needs correction
     */
    public function needsCorrection(): bool
    {
        return $this->extracted_text !== $this->correction;
    }
}
