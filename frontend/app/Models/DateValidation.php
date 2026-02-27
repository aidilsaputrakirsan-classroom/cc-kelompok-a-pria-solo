<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DateValidation extends Model
{
    use HasFactory;

    const UPDATED_AT = null; 

    protected $fillable = [
        'ticket_id',
        'doc_type',  
        'full_text',
        'tanggal_bracket',
        'is_valid',
        'correction',
    ];

    protected $casts = [
        'is_valid' => 'boolean',
    ];

    /**
     * Get the ticket that owns this date validation
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get all bounding boxes for this date validation (polymorphic)
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
     * Scope: Only invalid dates
     */
    public function scopeInvalid($query)
    {
        return $query->where('is_valid', false);
    }

    /**
     * Scope: Only valid dates
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    /**
     * Check if date has correction
     */
    public function hasCorrection(): bool
    {
        return !empty($this->correction);
    }

    /**
     * Get display text (correction if available, otherwise full_text)
     */
    public function getDisplayTextAttribute(): string
    {
        return $this->correction ?? $this->full_text;
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->is_valid ? 'Valid' : 'Invalid';
    }
}
