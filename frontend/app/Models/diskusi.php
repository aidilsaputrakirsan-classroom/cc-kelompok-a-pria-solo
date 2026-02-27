<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use \App\Models\projects;

class diskusi extends Model
{
    protected $table = '0_diskusi';
	
	protected $fillable = ["user_id", "user_role", "object_id", "reply_to", "comment"];
	
	public function projects()
	{
		return $this->belongsTo(projects::class, 'object_id', 'ID_RSO');
	}

	public function user()
	{
		return $this->belongsTo(User::class, 'user_id', 'id');
	}

}
