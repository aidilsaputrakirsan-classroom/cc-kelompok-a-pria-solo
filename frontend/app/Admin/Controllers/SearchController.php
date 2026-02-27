<?php

namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\Layout\Column;
use OpenAdmin\Admin\Layout\Content;
use OpenAdmin\Admin\Layout\Row;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends AdminController
{
	protected function search(Content $content)
	{	
        return $content
            ->title('SEARCH')
            ->description('Search Project & Status OBL')
			->view('projects.search', ['result' => null, 'tipe' => null]);
	}

	protected function searchPost(Content $content, Request $req)
	{
		$data = $req->all();

		switch ($data['selectSearch']) {
		  case 'project' :
			$result = DB::table('0_projects')
						->selectRaw('ID_RSO, 0_projects.Witel, 0_projects.segmen, AM, Project_Tahun, Customer, Nama_Project, Keterangan, 0_workflow.step, admin_users.name')
						->join('0_workflow', '0_projects.status_project', '=', '0_workflow.step_id')
						->leftjoin('admin_users', '0_projects.created_by', '=', 'admin_users.id')
						->whereRaw('MATCH (Customer, Nama_Project, Keterangan, ID_RSO) AGAINST ("' . $data['search'] . '") > 0.7')
						->limit(10)
						->get();
			break;
		  case 'obl' :
			$result = DB::table('0_document')
						->selectRaw('id_rso, id_obl, 0_document.id, NAMA_PELANGGAN, LAYANAN, MITRA, JANGKA_WAKTU, 0_workflow.step, admin_users.name')
						->join('0_workflow', '0_document.status_doc', '=', '0_workflow.step_id')
						->leftjoin('admin_users', '0_document.created_by', '=', 'admin_users.id')
						->whereRaw('MATCH (NAMA_PELANGGAN, LAYANAN, NO_KFS_SPK, NO_KL_WO_SURAT_PESANAN, NO_QUOTE, SID, NO_ORDER, id_rso) AGAINST ("' . $data['search'] . '") > 0.7')
						->limit(10)
						->get();
						// dd($result);
			break;
		  default:
			//code block
		}

        return $content
            ->title('SEARCH')
            ->description('Search Project & Status OBL')
			->view('projects.search', 
				['result' => $result,
				'tipe' => $data['selectSearch'],
				'prefix' => env('APP_URL') . config('admin.route.prefix'),
				]
			);
	}
}
