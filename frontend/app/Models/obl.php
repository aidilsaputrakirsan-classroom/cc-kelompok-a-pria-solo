<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \App\Models\projects;

class obl extends Model
{
    protected $table = '0_obl';
	protected $primaryKey = 'NO';
	// protected $keyType = 'string';
	
	protected $fillable = [
"NO"  ,"PROSES"  ,"TANGGAL_SUBMIT"  ,"TANGGAL_UPDATE"  ,"SEGMEN"  ,"FOLDER"  ,"FOLDER_OBL"  ,"WITEL"  ,"TAHUN"  ,"JENIS_SPK"  ,"NAMA_PELANGGAN"  ,"LAYANAN"  ,"NAMA_VENDOR"  ,"JANGKA_WAKTU"  ,"NILAI_KL"  ,"NO_KFS_SPK"  ,"NO_P8"  ,"NO_KL_WO_SURAT_PESANAN"  ,"PIC_MITRA"  ,"STATUS"  ,"STATUS_SM"  ,"KETERANGAN"  ,"ORDER_PROSES"  ,"ID_RSO"  ,"NO_QUOTE"  ,"SID"  ,"NO_ORDER"  ,"UMUR_ORDER"  ,"STATUS_OBL_DR"  ,"STATUS_KL_DR"  ,"ID_OBL"  ,	
	];
	
	public function projects()
	{
		return $this->belongsTo(projects::class, 'ID_RSO', 'ID_RSO');
	}
}
