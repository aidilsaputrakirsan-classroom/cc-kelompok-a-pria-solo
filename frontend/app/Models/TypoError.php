<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypoError extends Model
{
    use HasFactory;

    const UPDATED_AT = null; 

    protected $fillable = [
        'ticket_id',  
        'doc_type',
        'typo_word',
        'correction_word',
    ];

    /**
     * Get the ticket that owns this typo error
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get all bounding boxes for this typo error (polymorphic)
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
     * Scope: Search by typo word
     */
    public function scopeSearchTypo($query, string $search)
    {
        return $query->where('typo_word', 'like', "%{$search}%");
    }

    /**
     * Get formatted error message
     */
    public function getFormattedErrorAttribute(): string
    {
        return "'{$this->typo_word}' should be '{$this->correction_word}'";
    }
}