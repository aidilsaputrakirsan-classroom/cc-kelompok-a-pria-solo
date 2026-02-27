<?php

namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use \App\Models\diskusi;

class DiskusiController extends AdminController
{
	protected function save(Request $req)
	{
		$data = $req->all();

		diskusi::create($data);
		
		admin_toastr('Komentar berhasil di-simpan.', 'success', ['duration' => 5000]);

		return back();
	}
}
