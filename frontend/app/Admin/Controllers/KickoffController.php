<?php

namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use OpenAdmin\Admin\Layout\Content;
use OpenAdmin\Admin\Admin;
use \App\Models\projects;
use \App\Models\kickoff;
use \App\Models\diskusi;

class KickoffController extends AdminController
{
	protected function view(Content $content, $id_rso, $id)
	{
		$kickoff = kickoff::findOrFail($id);
		$project = projects::findOrFail($id_rso);
				
        return $content
            ->title('Kickoff Summary')
            ->description($project->Customer . ' - ' . $project->Nama_Project)
			->body(view('projects.viewKickoff', [
				'item' => $kickoff, 
				'url_prefix' => env('APP_URL') . config('admin.route.prefix') . '/projects',
				] ));
	}
	
	protected function form(Content $content, $ID_RSO)
	{
		$user = Admin::user();
		$project = projects::findOrFail($ID_RSO);
				
		$form = new \OpenAdmin\Admin\Widgets\Form();
		$form->action('/' . config('admin.route.prefix') . '/kickoff/projects/save');
		$form->hidden('created_by')->default($user->id);
		$form->hidden('id_rso')->default($ID_RSO);
		$form->ckeditor('peserta')->required();
		$form->text('lokasi')->required();
		$form->datetime('waktu', 'Waktu')->format('YYYY-MM-DD HH:mm')->required();
		$form->ckeditor('summary', 'Summary Hasil Kickoff')->required();

        return $content
            ->title($project->Customer)
            ->description($project->Nama_Project)
			->body($form->render());
			
            // ->view('projects.diskusi', 
				// [
					// 'diskusi' => $data,
					// 'user' => $user,
					// 'form' => $form->render(),
					// 'host' => env('APP_URL'),
					// 'def_avatar' => config('admin.default_avatar'),
				// ]);
	}

	protected function save(Request $req)
	{
		$data = $req->all();

		// Post Komentar
		$diskusi['user_id'] = Admin::user()->id;
		$diskusi['object_id'] = $data['id_rso'];
		$diskusi['comment'] = "** Summary Hasil Kickoff Saved - Kickoff Done **";	
		diskusi::create($diskusi);

		// Save Summary Kickoff
		kickoff::create($data);
		
		admin_toastr('Summary Hasil Kickoff berhasil di-simpan.', 'success', ['duration' => 5000]);

		return redirect(env('APP_URL') . config('admin.route.prefix') . "/projects/" . $data['id_rso'] ."/diskusi");
	}
}
