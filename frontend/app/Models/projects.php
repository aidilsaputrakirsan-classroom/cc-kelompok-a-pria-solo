<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravelista\Comments\Commentable;
use \App\Models\obl;
use \App\Models\files;
use \App\Models\projess_log;
use \App\Models\projess_task;
use \App\Models\hasilRapat;

class projects extends Model
{
    use Commentable; // SoftDeletes;

    protected $table = '0_projects';
	protected $primaryKey = 'ID_RSO';
	protected $keyType = 'string';
	
	protected $fillable =[
"ID_RSO","Witel","segmen","AM","Project_Tahun","Customer","Nama_Project","Tipe_KL","Flag_KL","Nilai_Project_Total","nilai_obl","nilai_ibl","profit","cogs","share_profit","status_project","jangka_waktu","is_njki","nilai_boq","nilai_irr","npv","peb","start_kontrak","end_kontrak","tanggal_rfs","tanggal_layanan","skema_pembayaran","skema_bisnis","is_win","is_verified","is_ngtma","is_ibl","is_renewal_only","tipe_projek","Keterangan","p1_tanggal","p1_nomor","p1_namaKontrak","draft_kb","created_by","task","sub_task"
	];
	
	protected $casts = [
		'created_at' => 'datetime:Y-m-d H:i',
		'updated_at' => 'datetime:Y-m-d H:i',
		'start_kontrak' => 'datetime:Y-m-d',
		'end_kontrak' => 'datetime:Y-m-d',
		'tanggal_rfs' => 'datetime:Y-m-d',
		'tanggal_layanan' => 'datetime:Y-m-d',
		'p1_tanggal' => 'datetime:Y-m-d',
		'created_by' => 'integer',
		'task' => 'integer',
		'sub_task' => 'integer',
	];

	public function obl()
	{
		return $this->hasOne(obl::class, 'ID_RSO', 'ID_RSO');
	}
	
	public function pstatus()
	{
		return $this->hasOne(workflow::class, 'step_id', 'status_project');
	}

	public function files()
	{
		return $this->hasMany(files::class, 'ID_RSO', 'ID_RSO');
	}

	public function diskusi()
	{
		return $this->hasMany(diskusi::class, 'object_id', 'ID_RSO');
	}

	public function pkickoff()
	{
		return $this->hasMany(kickoff::class, 'id_rso', 'ID_RSO');
	}

	public function doc()
	{
		return $this->hasMany(document::class, 'id_rso', 'ID_RSO');
	}
	
	public function hasilRapats()
	{
		return $this->hasMany(hasilRapat::class, 'id_rso', 'ID_RSO')
			->where('object', self::class);
	}
	
	public function scopeSumTotal($query)
    {
        return $query->where('votes', '>', 100);
    }

	/**
	 * Relasi ke logs perubahan project ini
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function logs()
	{
		return $this->hasMany(projess_log::class, 'trackable_id', 'ID_RSO')
			->where('trackable_type', 'App\Models\projects');
	}

	/**
	 * Relasi ke task utama (hanya task dengan task_parent = 0)
	 * 
	 * Catatan: Field 'task' di model projects HANYA berelasi dengan task 
	 * di model projess_task yang memiliki task_parent = 0 atau null.
	 * Constraint ini harus di-handle saat query atau di level aplikasi.
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function task()
	{
		return $this->belongsTo(projess_task::class, 'task', 'id');
	}

	/**
	 * Relasi ke sub task
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function subTask()
	{
		return $this->belongsTo(projess_task::class, 'sub_task', 'id');
	}
	
	/**
	 * Get the route key for the model.
	 * This ensures OpenAdmin resource routes use ID_RSO instead of 'id'
	 */
	public function getRouteKeyName()
	{
		return 'ID_RSO';
	}

}