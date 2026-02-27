<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoundingBox extends Model
{
    protected $fillable = [
        'boundable_id',
        'boundable_type',
        'page',
        'word',
        'x',
        'y',
        'width',
        'height',
    ];

    protected $casts = [
        'x' => 'decimal:4',
        'y' => 'decimal:4',
        'width' => 'decimal:4',
        'height' => 'decimal:4',
    ];

    public function boundable()
    {
        return $this->morphTo();
    }

    // Helper method untuk mendapatkan bbox sebagai array
    public function getBboxAttribute()
    {
        return [
            'x' => $this->x,
            'y' => $this->y,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }
}