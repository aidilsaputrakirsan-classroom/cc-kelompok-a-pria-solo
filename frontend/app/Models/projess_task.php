<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ProjessTask
 *
 * Mewakili tabel `1_tasks` di database.
 * Model ini berisi semua kolom yang dapat diisi (fillable)
 * dari tabel tersebut.
 *
 * @package App\Models
 */
class projess_task extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     *
     * @var string
     */
    protected $table = '1_tasks';

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
        'task_order',
        'task_parent',
        'task_name',
        'task_description',
        'task_roles',
        'is_optional',
    ];

    /**
     * Cast atribut ke tipe data tertentu.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'task_order' => 'integer',
        'task_parent' => 'integer',
        'is_optional' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relasi ke logs perubahan task ini
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function logs()
    {
        return $this->hasMany(projess_log::class, 'task_id', 'id');
    }

    /**
     * Relasi ke parent task
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(projess_task::class, 'task_parent', 'id');
    }

    /**
     * Relasi ke child tasks
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(projess_task::class, 'task_parent', 'id')->orderBy('task_order', 'asc');
    }

    /**
     * Relasi ke projects yang menggunakan task ini sebagai task utama
     * (hanya untuk task dengan task_parent = 0)
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projectsAsTask()
    {
        return $this->hasMany(projects::class, 'task', 'id');
    }

    /**
     * Relasi ke projects yang menggunakan task ini sebagai sub_task
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projectsAsSubTask()
    {
        return $this->hasMany(projects::class, 'sub_task', 'id');
    }
}
