<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use \App\Models\projects;

class files extends Model
{
    // use SoftDeletes;

    protected $table = '0_files';
	protected $primaryKey = 'ID_RSO';
	protected $keyType = 'string';
	
	protected $fillable = ['ID_RSO','path','order','upload_by'];
	
	public function projects()
	{
		return $this->belongsTo(projects::class, 'ID_RSO', 'ID_RSO');
	}
}