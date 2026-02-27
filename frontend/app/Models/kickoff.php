<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \App\Models\projects;

class kickoff extends Model
{
    protected $table = '0_kickoff';
	
	protected $fillable = ["created_by", "id_rso", "peserta", "waktu", "lokasi", "summary"];
	
	public function projects()
	{
		return $this->belongsTo(projects::class, 'ID_RSO', 'id_rso');
	}

}
