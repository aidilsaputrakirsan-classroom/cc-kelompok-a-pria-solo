<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\projects;
use App\Models\document;

class hasilRapat extends Model
{
    protected $table = '1_hasil_rapat';

    public const TIPE_OPTIONS = [
        '1. Bedah Project',
        '2. Project Internal Meeting',
        '3. Project Launch Meeting',
        '4. KickOff Delivery',
        '5. Others',
    ];

    protected $fillable = [
        'object',
        'object_id',
        'id_rso',
        'tipe',
        'note',
        'created_by',
    ];

    protected $casts = [
        'object_id' => 'integer',
        'created_by' => 'integer',
        'id_rso' => 'string',
    ];

    public static function tipeOptions(): array
    {
        return self::TIPE_OPTIONS;
    }

    public function project()
    {
        return $this->belongsTo(projects::class, 'id_rso', 'ID_RSO')
            ->where('object', projects::class);
    }

    public function document()
    {
        return $this->belongsTo(document::class, 'object_id', 'id')
            ->where('object', document::class);
    }
}
