<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravelista\Comments\Commentable;
use \App\Models\obl;
use \App\Models\files;

class lop extends Model
{
    use Commentable; // SoftDeletes;

    protected $table = '0_lop';
	protected $primaryKey = 'ID_RSO';
	protected $keyType = 'string';
	
	protected $fillable =[
"ID_RSO","Witel","segmen","AM","Project_Tahun","Customer","Nama_Project","Tipe_KL","Flag_KL","Nilai_Project_Total","Nilai_OBL","Profit","share_profit","Posisi_Berkas","Keterangan","created_by"
	];
	
	public function obl()
	{
		return $this->hasOne(obl::class, 'ID_RSO', 'ID_RSO');
	}

	public function files()
	{
		return $this->hasMany(files::class, 'ID_RSO', 'ID_RSO');
	}
}
