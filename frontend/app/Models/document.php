<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \App\Models\projects;
use \App\Models\projess_log;
use \App\Models\hasilRapat;
use App\Admin\Controllers\ProjessTaskController;

class document extends Model
{
    protected $table = '0_document';
	// protected $primaryKey = 'id_obl';
	// protected $keyType = 'string';
	
	protected $fillable = [
		"id_rso", "id_obl", "status_doc", "JENIS_SPK", "tipe_spk", 
		"NAMA_PELANGGAN", "LAYANAN", "MITRA", "JANGKA_WAKTU", "NILAI_KL", 
		"NO_KFS_SPK", "NO_P8", "NO_KL_WO_SURAT_PESANAN", "STATUS_SM", "KETERANGAN", 
		"NO_QUOTE", "SID", "NO_ORDER", "prev_kl_no", "prev_kl_tanggal", 
		"prev_kl_judul", "p2_tanggal", "p2_calon_mitra", "p2_dibuat", 
		"p2_diperiksa", "p2_disetujui", "p3_tanggal", "p3_nomor", "p3_dibuat", 
		"p4_tanggal", "p4_list_peserta", "p4_skema_bisnis", "p4_top", 
		"p4_lokasi_instalasi", "p4_tgl_delivery", "p4_jangka_waktu", "p4_slg", 
		"p4_tgl_sph", "is_renewal", "task", "sub_task", "tiket_precise", 
		"created_by", "start_kontrak", "end_kontrak"
	];
	
	protected $casts = [
		'created_at' => 'datetime:Y-m-d H:i',
		'updated_at' => 'datetime:Y-m-d H:i',
		'start_kontrak' => 'date',
		'end_kontrak' => 'date',
		'created_by' => 'string',
		'p2_calon_mitra' => 'array',
		'p2_tanggal' => 'date',
		'p3_tanggal' => 'date',
		'p4_tanggal' => 'date',
		'p4_list_peserta' => 'array',
		'p4_tgl_delivery' => 'date',
		'p4_tgl_sph' => 'date',
		'p4_slg' => 'string',
		'prev_kl_tanggal' => 'date',
		'is_renewal' => 'boolean',
		'task' => 'integer',
		'sub_task' => 'integer',
	];

	/**
	 * Default values untuk attributes
	 *
	 * @var array<string, mixed>
	 */
	protected $attributes = [
		'is_renewal' => false,
	];

	protected $appends = ['full_doc'];
	
	public function getFullDocAttribute()
    {
        return "{$this->id_obl} - {$this->NAMA_PELANGGAN} - {$this->LAYANAN}";
    }

	public function projects()
	{
		return $this->belongsTo(projects::class, 'ID_RSO', 'id_rso');
	}

	public function dstatus()
	{
		return $this->hasOne(workflow::class, 'step_id', 'status_doc');
	}

	public function hasilRapats()
	{
		return $this->hasMany(hasilRapat::class, 'object_id', 'id')
			->where('object', self::class);
	}

	public function getListPesertaAttribute()
	{
		return explode(',', trim($this->p4_list_peserta, '"'));
	}

	/**
	 * Relasi ke logs perubahan document ini
	 *
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function logs()
	{
		return $this->hasMany(projess_log::class, 'model_id', 'id')
			->where('model_type', 'App\Models\document');
	}

	/**
	 * Boot method untuk mendaftarkan model events
	 */
	protected static function boot()
	{
		parent::boot();

		/**
		 * Event: Setelah model di-create
		 * Panggil mapTask untuk mengisi field task dan sub_task berdasarkan is_renewal
		 * is_renewal memiliki default value FALSE, jadi mapTask akan selalu dipanggil
		 */
		static::created(function ($document) {
			try {
				ProjessTaskController::mapTask($document);
				// Refresh model untuk mendapatkan nilai task dan sub_task yang baru diisi oleh mapTask
				$document->refresh();
			} catch (\Exception $e) {
				// Log error jika diperlukan
				\Log::error("Error mapping task untuk document {$document->id}: " . $e->getMessage());
			}
		});

		/**
		 * Event: Setelah model di-update
		 * Jika is_renewal berubah, panggil mapTask untuk update field task dan sub_task
		 */
		static::updated(function ($document) {
			// Cek apakah is_renewal berubah
			if ($document->wasChanged('is_renewal')) {
				try {
					ProjessTaskController::mapTask($document);
					// Refresh model untuk mendapatkan nilai task dan sub_task yang baru diisi oleh mapTask
					$document->refresh();
				} catch (\Exception $e) {
					// Log error jika diperlukan
					\Log::error("Error mapping task untuk document {$document->id}: " . $e->getMessage());
				}
			}
		});
	}
}
