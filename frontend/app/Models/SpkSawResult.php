<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpkSawResult extends Model
{
    protected $fillable = [
        'ticket_id',
        'c1_score',
        'c2_score',
        'c3_score',
        'c4_score',
        'c5_score',
        'c1_normalized',
        'c2_normalized',
        'c3_normalized',
        'c4_normalized',
        'c5_normalized',
        'preference_value',
        'recommendation',
        'calculated_at',
    ];

    protected $casts = [
        'c1_score' => 'float',
        'c2_score' => 'float',
        'c3_score' => 'float',
        'c4_score' => 'float',
        'c5_score' => 'float',
        'c1_normalized' => 'float',
        'c2_normalized' => 'float',
        'c3_normalized' => 'float',
        'c4_normalized' => 'float',
        'c5_normalized' => 'float',
        'preference_value' => 'float',
        'calculated_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
