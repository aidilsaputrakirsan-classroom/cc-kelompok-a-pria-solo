<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'notes',
    ];

    protected $casts = [
        'notes' => 'array',
    ];

    /**
     * Get the ticket that owns this note
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get note by category
     */
    public function getNoteByCategory(string $category): ?string
    {
        return $this->notes[$category] ?? null;
    }

    /**
     * Set note by category
     */
    public function setNoteByCategory(string $category, string $value): void
    {
        $notes = $this->notes ?? [];
        $notes[$category] = $value;
        $this->notes = $notes;
    }

    /**
     * Get all available categories
     */
    public static function getCategories(): array
    {
        return [
            'mitra' => 'Mitra',
            'obl' => 'OBL',
            'internal_telkom' => 'Internal Telkom',
            'segmen_witel' => 'Segmen / Witel',
            'revisi_precise' => 'Revisi Precise',
        ];
    }
}