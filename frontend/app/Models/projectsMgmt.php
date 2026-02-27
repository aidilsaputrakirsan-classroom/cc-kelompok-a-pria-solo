<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MasterProyek
 *
 * Mewakili tabel `master_proyek` di database.
 * Model ini berisi semua kolom yang dapat diisi (fillable)
 * dari tabel tersebut.
 *
 * @package App\Models
 */
class projectsMgmt extends Model
{
    use HasFactory;

    /**
     * Nama tabel yang terhubung dengan model ini.
     *
     * @var string
     */
    protected $table = '0_project_mgmt';

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
     * Tabel `master_proyek` tidak memilikinya, jadi di-set false.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wbs',
        'nama_pesanan',
        'sow_singkat',
        'id_rso',
        'keterangan_konteks',
        'skema',
        'witel',
        'po',
        'tipe',
        'mitra_penyedia',
        'note_kick_off',
        'tgl_kick_off',
        'tgl_rfs',
        'durasi_delivery',
        'tgl_selesai_layanan',
        'durasi_layanan',
        'status_dok_perikatan',
        'status_delivery',
        'update_delivery',
        'no_kl_wo_sp',
        'jenis_dok_delivery',
        'kebutuhan_evidence',
        'status_dok_delivery',
    ];
}
