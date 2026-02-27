<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProjessLog
 *
 * Mewakili tabel `1_logs` di database.
 * Model ini mencatat setiap perubahan/pergeseran task dari project.
 * Mendukung tracking perubahan dari berbagai model: projess_task, document, obl, dll.
 *
 * @package App\Models
 */
class projess_log extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     *
     * @var string
     */
    protected $table = '1_logs';

    /**
     * Primary key untuk model ini.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Menunjukkan apakah ID model ini auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Tipe data dari primary key.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Menunjukkan apakah model ini memiliki timestamp (created_at dan updated_at).
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_id',
        'trackable_type',
        'trackable_id',
        'model_type',
        'model_id',
        'id_rso',
        'action_type',
        'from_task_id',
        'from_task_order',
        'from_task_parent',
        'to_task_id',
        'to_task_order',
        'to_task_parent',
        'from_status',
        'to_status',
        'changed_fields',
        'notes',
        'user_id',
        'metadata',
    ];

    /**
     * Cast atribut ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'task_id' => 'integer',
        'trackable_id' => 'integer',
        'model_id' => 'integer',
        'from_task_id' => 'integer',
        'from_task_order' => 'integer',
        'from_task_parent' => 'integer',
        'to_task_id' => 'integer',
        'to_task_order' => 'integer',
        'to_task_parent' => 'integer',
        'user_id' => 'integer',
        'metadata' => 'array',
        'changed_fields' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke task yang dipindahkan
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function task()
    {
        return $this->belongsTo(projess_task::class, 'task_id', 'id');
    }

    /**
     * Relasi ke task sebelumnya (from)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function fromTask()
    {
        return $this->belongsTo(projess_task::class, 'from_task_id', 'id');
    }

    /**
     * Relasi ke task setelahnya (to)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function toTask()
    {
        return $this->belongsTo(projess_task::class, 'to_task_id', 'id');
    }

    /**
     * Relasi ke user yang melakukan perubahan
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(\OpenAdmin\Admin\Auth\Database\Administrator::class, 'user_id', 'id');
    }

    /**
     * Polymorphic relationship untuk trackable model (model yang di-track)
     * Bisa berupa: projects, document, obl, dll
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function trackable()
    {
        return $this->morphTo();
    }

    /**
     * Polymorphic relationship untuk model yang berubah
     * Bisa berupa: projess_task, document, obl, dll
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function model()
    {
        return $this->morphTo('model');
    }

    /**
     * Relasi ke document (jika model_type adalah document)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function document()
    {
        return $this->belongsTo(document::class, 'model_id', 'id')
            ->where('model_type', 'App\Models\document');
    }

    /**
     * Relasi ke projects (jika trackable_type adalah projects)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(projects::class, 'trackable_id', 'ID_RSO')
            ->where('trackable_type', 'App\Models\projects');
    }

    /**
     * Relasi ke obl (jika model_type adalah obl)
     * Note: obl menggunakan primary key 'NO' bukan 'id'
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function obl()
    {
        return $this->belongsTo(\App\Models\obl::class, 'model_id', 'NO')
            ->where('model_type', 'App\Models\obl');
    }

    /**
     * Relasi ke projectsMgmt (jika model_type adalah projectsMgmt)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function projectsMgmt()
    {
        return $this->belongsTo(projectsMgmt::class, 'model_id', 'id')
            ->where('model_type', 'App\Models\projectsMgmt');
    }

    /**
     * Scope untuk filter berdasarkan action type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $actionType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActionType($query, $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope untuk filter berdasarkan task_id
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $taskId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTask($query, $taskId)
    {
        return $query->where('task_id', $taskId);
    }

    /**
     * Scope untuk filter berdasarkan id_rso (project)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $idRso
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForProject($query, $idRso)
    {
        return $query->where('id_rso', $idRso);
    }

    /**
     * Scope untuk filter berdasarkan model type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $modelType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForModelType($query, $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope untuk filter berdasarkan trackable type
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $trackableType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForTrackableType($query, $trackableType)
    {
        return $query->where('trackable_type', $trackableType);
    }

    /**
     * Helper method untuk membuat log entry
     *
     * @param mixed $model Model yang berubah (projess_task, document, dll)
     * @param mixed $trackable Model yang di-track (projects, dll)
     * @param string $actionType Tipe aksi (proceed, return, create, update, delete, move, status_change)
     * @param array $data Data tambahan untuk log
     * @return static
     */
    public static function createLog($model, $trackable = null, $actionType = 'update', $data = [])
    {
        $modelType = get_class($model);
        $modelId = $model->getKey();
        
        $logData = [
            'model_type' => $modelType,
            'model_id' => $modelId,
            'action_type' => $actionType,
            'user_id' => \OpenAdmin\Admin\Admin::user()->id ?? null,
        ];

        // Jika trackable disediakan, set trackable_type dan trackable_id
        if ($trackable) {
            $logData['trackable_type'] = get_class($trackable);
            $logData['trackable_id'] = $trackable->getKey();
            
            // Jika trackable adalah projects, set id_rso
            if ($trackable instanceof projects) {
                $logData['id_rso'] = $trackable->ID_RSO;
            } elseif (isset($trackable->id_rso)) {
                $logData['id_rso'] = $trackable->id_rso;
            }
        }

        // Merge dengan data tambahan
        $logData = array_merge($logData, $data);

        return static::create($logData);
    }
}
