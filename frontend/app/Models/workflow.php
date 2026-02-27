<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \App\Models\projects;

class workflow extends Model
{
    protected $table = '0_workflow';
		
	public function projects()
	{
		return $this->belongsTo(projects::class, 'status_project', 'step_id');
	}

	public function document()
	{
		return $this->belongsTo(document::class, 'status_doc', 'step_id');
	}

}