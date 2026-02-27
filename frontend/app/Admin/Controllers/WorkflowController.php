<?php

namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenAdmin\Admin\Admin;
use \App\Models\diskusi;
use \App\Models\projects;
use \App\Models\document;

class WorkflowController extends AdminController
{
	
	public function updateDocObl($ID_RSO) 
	{
		$user = Admin::user();
		
		$list = document::where('id_rso', '=', $ID_RSO)->get();
		foreach ($list as $item) {
			$data = array();
			$data['user_id'] = is_null($user) ? 1 : $user->id;
			$data['user_role'] = is_null($user) ? 1 : $user->roles[0]['id'];
			$data['object_id'] = $item->id;
			$data['comment'] = "** Finalisasi SPH, KB Done ** ";
			
			// Update status document
			$doc = document::findOrFail($item->id);
			$doc->status_doc = '210'; //Penetapan Mitra Pelaksana
			$doc->save();

			// Post Komentar
			diskusi::create($data);			
		}
				
		return $ID_RSO . " Sukses diupdate ke Penetapan Mitra.";
	}

	protected function followupProject(Request $req)
	{
		$data = $req->all();
		$project = projects::findOrFail($data['object_id']);
		
		// Update status project
		$project->status_project = $data['followup'];
		$project->save();
				
		// Post Komentar
		$sql = "
SELECT
	a.next_message AS message
FROM
	0_workflow a
WHERE 
	a.step_id = '". $data['currstatus'] ."' AND a.step_next = '". $data['followup'] ."'
UNION ALL 
SELECT
	a.next1_message AS message
FROM
	0_workflow a
WHERE 
	a.step_id = '". $data['currstatus'] ."' AND a.step_next1 = '". $data['followup'] ."'
UNION ALL 
SELECT
	a.back_message AS message
FROM
	0_workflow a
WHERE 
	a.step_id = '". $data['currstatus'] ."' AND a.step_back = '". $data['followup'] ."'
		";
		
		$message = DB::select(DB::raw($sql));
		$komentar = $data['followup'] == '299' ? 'Project DROPPED' : $message[0]->message;

		$data['comment'] = "** " . $komentar . " **<br/><br/>" . $data['comment'];		
		diskusi::create($data);


		// Sinkronisasi status dengan dokumen OBL
		if (intval($data['followup']) > 120 and intval($data['followup']) != 299) {
			$user = Admin::user();
			
			$list = document::where('id_rso', '=', $data['object_id'])->get();
			foreach ($list as $item) {			
				$req_data = [
					'user_id' => is_null($user) ? 1 : $user->id,
					'user_role' => is_null($user) ? 1 : $user->roles[0]['id'],
					'object_id' => $item->id,
					// 'currstatus' => $item->status_doc,
					'currstatus' => $data['currstatus'],
					'followup' => $data['followup'],
					'comment' => 'Synchronize Update',
				];
				
				$req = new Request();
				$req->merge($req_data);
				$this->followupDoc($req);
			}
		}


		admin_toastr('Process Project berhasil dilakukan.', 'success', ['duration' => 5000]);

		return back();
	}
	
	protected function followupDoc(Request $req)
	{
		$data = $req->all();
		$doc = document::findOrFail($data['object_id']);

		// Update status document
		$doc->status_doc = $data['followup'];
		$doc->save();
		
		// Post Komentar
		$sql = "
SELECT
	a.next_message AS message
FROM
	0_workflow a
WHERE 
	a.step_id = '". $data['currstatus'] ."' AND a.step_next = '". $data['followup'] ."'
UNION ALL 
SELECT
	a.next1_message AS message
FROM
	0_workflow a
WHERE 
	a.step_id = '". $data['currstatus'] ."' AND a.step_next1 = '". $data['followup'] ."'
UNION ALL 
SELECT
	a.back_message AS message
FROM
	0_workflow a
WHERE 
	a.step_id = '". $data['currstatus'] ."' AND a.step_back = '". $data['followup'] ."'
		";
		
		$message = DB::select(DB::raw($sql));
		$komentar = $data['followup'] == '299' ? 'Project DROPPED' : $message[0]->message;

		$data['comment'] = "** " . $komentar . " **<br/><br/>" . $data['comment'];		
		diskusi::create($data);

		
		// Cek & Update bila All Dok OBL sinkron
		$sql = "
SELECT 
xx.id_rso, xx.status_project, AVG(xx.stat_doc) AS avg_skor
FROM
(
SELECT
a.id_rso, a.id_obl,	(case when a.status_doc = '" . $data['followup'] ."' then 1 ELSE 0 END) AS stat_doc
FROM
0_document a
JOIN 0_projects p ON a.id_rso = p.ID_RSO
WHERE
a.id_rso = '$doc->id_rso'
) xx	
GROUP BY 
xx.id_rso, xx.status_project
		";
		
		$item = DB::select(DB::raw($sql));
		if ($item[0]->avg_skor == 1) {
			$user = Admin::user();
					
			$req_data = [
				'user_id' => is_null($user) ? 1 : $user->id,
				'user_role' => is_null($user) ? 1 : $user->roles[0]['id'],
				'object_id' => $item[0]->id_rso,
				// 'currstatus' => $item[0]->status_project,
				'currstatus' => $data['currstatus'],
				'followup' => $data['followup'],
				'comment' => 'Synchronize Update',
			];
			
			$req = new Request();
			$req->merge($req_data);
			$this->followupProject($req);
		}
		

		admin_toastr('Process Dokumen OBL berhasil dilakukan.', 'success', ['duration' => 5000]);

		return back();
	}


}
