<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use OpenAdmin\Admin\Facades\Admin;
use OpenAdmin\Admin\Controllers\Dashboard;
use OpenAdmin\Admin\Layout\Column;
use OpenAdmin\Admin\Layout\Content;
use OpenAdmin\Admin\Layout\Row;
use Revolution\Google\Sheets\Facades\Sheets;
use OpenAdmin\Admin\Widgets\InfoBox;
use OpenAdmin\Admin\Widgets\Table;
use Illuminate\Support\Facades\DB;
use \App\Models\projects;
use \App\Models\document;
use \App\Models\projectsMgmt;

class HomeController extends Controller
{

	public function dashboardSolution()
	{
		$content = new Content();
		
		$sql = "
SELECT
a.Witel, 
SUM(case when a.status_project = '20' then 1 ELSE 0 END) AS qty_req_rev,
SUM(case when a.status_project = '70' then 1 ELSE 0 END) AS qty_coll_doc,
SUM(case when a.status_project = '10' then 1 ELSE 0 END) AS qty_req_prop,
SUM(case when a.status_project = '15' then 1 ELSE 0 END) AS qty_req_progress,
SUM(case when a.status_project = '30' then 1 ELSE 0 END) AS qty_rab_created,
SUM(case when a.status_project = '60' then 1 ELSE 0 END) AS qty_rab_rev,
SUM(case when a.status_project = '100' then 1 ELSE 0 END) AS qty_post_ko_rev,
SUM(case when a.status_project = '50' then 1 ELSE 0 END) AS qty_rev_prop,
SUM(case when a.status_project = '90' then 1 ELSE 0 END) AS qty_wait_ko,
SUM(case when a.status_project = '170' then 1 ELSE 0 END) AS qty_finalisasi_sph,
SUM(case when a.status_project = '212' then 1 ELSE 0 END) AS qty_draft_kb,
SUM(case when a.status_project = '216' then 1 ELSE 0 END) AS qty_sirkulir_kb,
SUM(case when a.status_project = '218' then 1 ELSE 0 END) AS qty_input_quote,
SUM(case when a.status_project = '227' then 1 ELSE 0 END) AS qty_input_order,
SUM(case when CAST(a.status_project as UNSIGNED) BETWEEN 170 AND 228 then 1 ELSE 0 END) AS qty_obl_inprogress,
SUM(case when a.status_project = '228' then 1 ELSE 0 END) AS qty_obl_done,
SUM(case when a.status_project = '120' AND a.is_verified != '1' then 1 ELSE 0 END) AS qty_post_ko_obl,
SUM(case when a.status_project = '120' AND a.is_verified = '1' then 1 ELSE 0 END) AS qty_post_ko_obl_verified
FROM
0_projects a
GROUP BY
a.Witel WITH ROLLUP
		";
		$data = DB::select(DB::raw($sql));

		return $content
			// ->css_file(Admin::asset("open-admin/css/pages/dashboard.css"))
			->title('Dashboard')
			->description('Solution Regional')
			->row(function (Row $row) {

				$row->column(2, function (Column $column) {
					$data = projects::selectRaw('count(1) as total')->get();
					
					$infoBox1 = new InfoBox('TOTAL', 'clipboard-list', 'primary', '/projess/projects', $data[0]->total);
					$column->append($infoBox1->render());
				});

				$row->column(2, function (Column $column) {
					$data = projects::selectRaw('count(1) as total')->where('is_win', '=', '1')->get();
					
					$infoBox2 = new InfoBox('WIN', 'chart-pie', 'success', '/projess/projects?is_win=1', $data[0]->total);
					$column->append($infoBox2->render());
				});

				$row->column(2, function (Column $column) {
					$data = projects::selectRaw('count(1) as total')->where('is_verified', '=', '1')->get();
					
					$infoBox2 = new InfoBox('VERIFIED', 'chart-area', 'warning', '/projess/projects?is_verified=1', $data[0]->total);
					$column->append($infoBox2->render());
				});

				$row->column(3, function (Column $column) {
					$data = projects::selectRaw('round(sum(Nilai_Project_Total)/1000000,0) as total')->where('is_win', '=', '1')->get();
					
					$infoBox2 = new InfoBox('Nilai Kontrak WIN', 'chart-line', 'secondary', '/projess/projects?is_win=1', number_format($data[0]->total, 0, ',', '.') . ' (Juta)');
					$column->append($infoBox2->render());
				});

				$row->column(3, function (Column $column) {
					$sql_noobl = "
SELECT
	COUNT(1) AS total
FROM
	0_projects a
	left JOIN 0_document b ON a.ID_RSO = b.id_rso
WHERE
	a.status_project = '120'
	AND a.is_verified = '1'
	AND b.id_rso IS NULL		
					";
					$data_noobl = DB::select(DB::raw($sql_noobl));
					
					$infoBox1 = new InfoBox('Perlu Create OBL', 'info-circle', 'danger', '/projess/projects?noOBL=1', $data_noobl[0]->total);
					$column->append($infoBox1->render());
				});
			})
			->body(view('projects.db_solution',
				['data' => $data]
				));
	}	

	public function dashboardSE()
	{
		$content = new Content();
		
		return $content
			// ->css_file(Admin::asset("open-admin/css/pages/dashboard.css"))
			->title('Dashboard')
			->description('Solution Engineer Witel ' . Admin::user()->witel)
			->row(function (Row $row) {

				$row->column(2, function (Column $column) {
					$data = projects::selectRaw('count(1) as total')->whereRaw($this->whereBuild())->get();
					
					$infoBox1 = new InfoBox('TOTAL', 'clipboard-list', 'primary', '/projess/projects', $data[0]->total);
					$column->append($infoBox1->render());
				});

				$row->column(2, function (Column $column) {
					$data = projects::selectRaw('count(1) as total')->where('is_win', '=', '1')->whereRaw($this->whereBuild())->get();
					
					$infoBox2 = new InfoBox('WIN', 'chart-pie', 'success', '/projess/projects?is_win=1', $data[0]->total);
					$column->append($infoBox2->render());
				});

				$row->column(2, function (Column $column) {
					$data = projects::selectRaw('count(1) as total')->where('is_verified', '=', '1')->whereRaw($this->whereBuild())->get();
					
					$infoBox2 = new InfoBox('VERIFIED', 'chart-area', 'warning', '/projess/projects?is_verified=1', $data[0]->total);
					$column->append($infoBox2->render());
				});

				$row->column(3, function (Column $column) {
					$data = projects::selectRaw('round(sum(Nilai_Project_Total)/1000000,0) as total')->where('is_win', '=', '1')->whereRaw($this->whereBuild())->get();
					
					$infoBox2 = new InfoBox('Nilai Kontrak WIN', 'chart-line', 'secondary', '/projess/projects?is_win=1', number_format($data[0]->total, 0, ',', '.') . ' (Juta)');
					$column->append($infoBox2->render());
				});
				
				$row->column(3, function (Column $column) {
					$witel = $this->whereBuild();
					$sql_noobl = "
SELECT
	COUNT(1) AS total
FROM
	0_projects a
	left JOIN 0_document b ON a.ID_RSO = b.id_rso
WHERE
	a.status_project = '120'
	AND a.is_verified = '1'
	AND $witel
	AND b.id_rso IS NULL		
					";
					$data_noobl = DB::select(DB::raw($sql_noobl));
					
					$infoBox1 = new InfoBox('Perlu Create OBL', 'info-circle', 'danger', '/projess/projects?noOBL=1', $data_noobl[0]->total);
					$column->append($infoBox1->render());
				});

			})
			->row(function (Row $row) {
				$row->column(4, function (Column $column) {
					$sql = "
SELECT
	a.status_project, b.step, COUNT(1) AS qty
FROM
	0_projects a
	JOIN 0_workflow b ON a.status_project = b.step_id
WHERE
	" . $this->whereBuild() . "
GROUP BY 
	a.status_project, b.step
ORDER BY 
	CAST(a.status_project AS UNSIGNED)
					";
					
					$data = DB::select(DB::raw($sql));
					
					$column->append(view('projects.db_se_summary', ['data' => $data, 'colors' => config('appConst.statusColors')]));
				});
				$row->column(8, function (Column $column) {
					$data_inprogress = projects::select('ID_RSO','Customer','Nama_Project','Nilai_Project_Total','status_project','is_win','is_verified','is_ngtma','is_ibl','0_workflow.step')
										->join('0_workflow', '0_projects.status_project', '=', '0_workflow.step_id')
										->where('is_win', '=', '0')
										->where('status_project', '!=', '299')
										->whereRaw($this->whereBuild())
										->orderbyRaw('CAST(Nilai_Project_Total AS UNSIGNED) DESC')
										->limit(5)
										->get();

					$data_win = projects::select('ID_RSO','Customer','Nama_Project','Nilai_Project_Total','status_project','is_win','is_verified','is_ngtma','is_ibl','0_workflow.step')
										->join('0_workflow', '0_projects.status_project', '=', '0_workflow.step_id')
										->where('is_win', '=', '1')
										->where('status_project', '!=', '299')
										->whereRaw($this->whereBuild())
										->orderbyRaw('CAST(Nilai_Project_Total AS UNSIGNED) DESC')
										->limit(5)
										->get();
										
					$column->append(view('projects.db_se_list',
						['data_inprogress' => $data_inprogress,
						'data_win' => $data_win,
						'colors' => config('appConst.statusColors'),
						]
					));
				});
			});
	}	

	protected function whereBuild() 
	{
		$whSegmen = is_null(Admin::user()->segmen) ? '1' : "segmen = '" . Admin::user()->segmen . "'";
		$whSegmen = Admin::user()->segmen == 'REPS' ? "segmen IN ('RES', 'RPS')" : $whSegmen;
		$whWitel = Admin::user()->witel == 'Regional' ? '1' : "Witel = '" . Admin::user()->witel . "'";
		$whWitel = Admin::user()->witel == 'Kalselteng' ? "Witel IN ('Kalsel', 'Kalteng', 'Kalselteng')" : $whWitel;
		$whWitel = Admin::user()->witel == 'Kaltimtara' ? "Witel IN ('Kaltim', 'Kaltara', 'Kaltimtara')" : $whWitel;
		
		return $whSegmen . " AND " . $whWitel;
	}
	
	public function dashboardSsegmen()
	{
		$content = new Content();
		
		$lsegmen = config('appConst.segmens')[Admin::user()->segmen];
		$lwitel = config('appConst.witels')[Admin::user()->witel];
				
		return $content
			// ->css_file(Admin::asset("open-admin/css/pages/dashboard.css"))
			->title('Dashboard')
			// ->description('Support Segmen ' . Admin::user()->segmen . ' - ' . Admin::user()->witel)
			->description('Support ' . $lsegmen . ' - ' . $lwitel)
			->row(function (Row $row) {

				$row->column(2, function (Column $column) {

					$data = projects::selectRaw('count(1) as total')->whereRaw($this->whereBuild())->get();
					
					$infoBox1 = new InfoBox('TOTAL', 'clipboard-list', 'primary', '/projess/projects', $data[0]->total);
					$column->append($infoBox1->render());
				});

				$row->column(2, function (Column $column) {
					$data = projects::selectRaw('count(1) as total')->where('is_win', '=', '1')->whereRaw($this->whereBuild())->get();
					
					$infoBox2 = new InfoBox('WIN', 'chart-pie', 'success', '/projess/projects?is_win=1', $data[0]->total);
					$column->append($infoBox2->render());
				});

				$row->column(2, function (Column $column) {
					$data = projects::selectRaw('count(1) as total')->where('is_verified', '=', '1')->whereRaw($this->whereBuild())->get();
					
					$infoBox2 = new InfoBox('VERIFIED', 'chart-area', 'warning', '/projess/projects?is_verified=1', $data[0]->total);
					$column->append($infoBox2->render());
				});

				$row->column(3, function (Column $column) {
					$data = projects::selectRaw('round(sum(Nilai_Project_Total)/1000000,0) as total')->where('is_win', '=', '1')->whereRaw($this->whereBuild())->get();
					
					$infoBox2 = new InfoBox('Nilai Kontrak WIN', 'chart-line', 'secondary', '/projess/projects?is_win=1', number_format($data[0]->total, 0, ',', '.') . ' (Juta)');
					$column->append($infoBox2->render());
				});
				
				$row->column(3, function (Column $column) {
					$witel = Admin::user()->witel;
					$sql_noobl = "
SELECT
	COUNT(1) AS total
FROM
	0_projects a
	left JOIN 0_document b ON a.ID_RSO = b.id_rso
WHERE
	a.status_project = '120'
	AND a.is_verified = '1'
	AND b.id_rso IS NULL
	AND " . $this->whereBuild()
					;
					$data_noobl = DB::select(DB::raw($sql_noobl));
					
					$infoBox1 = new InfoBox('Perlu Create OBL', 'info-circle', 'danger', '/projess/projects?noOBL=1', $data_noobl[0]->total);
					$column->append($infoBox1->render());
				});

			})
			->row(function (Row $row) {
				$row->column(4, function (Column $column) {
					$sql = "
SELECT
	a.status_project, b.step, COUNT(1) AS qty
FROM
	0_projects a
	JOIN 0_workflow b ON a.status_project = b.step_id
WHERE
	". $this->whereBuild() ."
GROUP BY 
	a.status_project, b.step
ORDER BY 
	CAST(a.status_project AS UNSIGNED)
					";
					
					$data = DB::select(DB::raw($sql));
					
					$column->append(view('projects.db_ss_summary', 
					[
						'data' => $data, 
						'witel' => Admin::user()->witel,
						'segmen' => Admin::user()->segmen,
						'colors' => config('appConst.statusColors'),
					]));
				});
				$row->column(8, function (Column $column) {
					$data_inprogress = projects::select('ID_RSO','Customer','Nama_Project','Nilai_Project_Total','status_project','is_win','is_verified','is_ngtma','is_ibl','0_workflow.step')
										->join('0_workflow', '0_projects.status_project', '=', '0_workflow.step_id')
										->where('is_win', '=', '0')
										->where('status_project', '!=', '299')
										->whereRaw($this->whereBuild())
										->orderbyRaw('CAST(Nilai_Project_Total AS UNSIGNED) DESC')
										->limit(10)
										->get();

					$data_win = projects::select('ID_RSO','Customer','Nama_Project','Nilai_Project_Total','status_project','is_win','is_verified','is_ngtma','is_ibl','0_workflow.step')
										->join('0_workflow', '0_projects.status_project', '=', '0_workflow.step_id')
										->where('is_win', '=', '1')
										->where('status_project', '!=', '299')
										->whereRaw($this->whereBuild())
										->orderbyRaw('CAST(Nilai_Project_Total AS UNSIGNED) DESC')
										->limit(10)
										->get();
										
					$column->append(view('projects.db_se_list',
						['data_inprogress' => $data_inprogress,
						'data_win' => $data_win,
						'colors' => config('appConst.statusColors'),
						]
					));
				});
			});
	}	

	public function dashboardBMBS()
	{
		$content = new Content();
		
		$sql = "
SELECT
	p.Witel, 
	SUM(case when a.status_doc = '130' then 1 ELSE 0 END) AS s_start,
	SUM(case when a.status_doc = '131' then 1 ELSE 0 END) AS s_p2,
	SUM(case when a.status_doc = '132' then 1 ELSE 0 END) AS s_p3,
	SUM(case when a.status_doc = '133' then 1 ELSE 0 END) AS s_p4,
	SUM(case when a.status_doc = '134' then 1 ELSE 0 END) AS s_sph,
	SUM(case when a.status_doc = '140' then 1 ELSE 0 END) AS m_start,
	SUM(case when a.status_doc = '141' then 1 ELSE 0 END) AS m_contest,
	SUM(case when a.status_doc = '142' then 1 ELSE 0 END) AS m_p2,
	SUM(case when a.status_doc = '143' then 1 ELSE 0 END) AS m_p3,
	SUM(case when a.status_doc = '144' then 1 ELSE 0 END) AS m_p4,
	SUM(case when a.status_doc = '145' then 1 ELSE 0 END) AS m_sph,
	SUM(case when a.status_doc = '146' then 1 ELSE 0 END) AS m_skoring,
	SUM(case when a.status_doc = '150' then 1 ELSE 0 END) AS nego,
	SUM(case when a.status_doc = '155' then 1 ELSE 0 END) AS p6,
	SUM(case when a.status_doc = '160' then 1 ELSE 0 END) AS skm,
	SUM(case when a.status_doc = '170' then 1 ELSE 0 END) AS rab_final,
	SUM(case when a.status_doc = '212' then 1 ELSE 0 END) AS draft_kb,
	SUM(case when a.status_doc = '214' then 1 ELSE 0 END) AS review_kb,
	SUM(case when a.status_doc = '216' then 1 ELSE 0 END) AS sirkulir_kb,
	SUM(case when a.status_doc = '218' then 1 ELSE 0 END) AS input_quote,
	SUM(case when a.status_doc = '220' then 1 ELSE 0 END) AS draft_kl,
	SUM(case when a.status_doc = '221' then 1 ELSE 0 END) AS review_kl,
	SUM(case when a.status_doc = '222' then 1 ELSE 0 END) AS review_kl_mitra,
	SUM(case when a.status_doc = '223' then 1 ELSE 0 END) AS verifikasi_dok,
	SUM(case when a.status_doc = '224' then 1 ELSE 0 END) AS sirkulir_internal,
	SUM(case when a.status_doc = '225' then 1 ELSE 0 END) AS sirkulir_mitra,
	SUM(case when a.status_doc = '226' then 1 ELSE 0 END) AS closing_sm,
	SUM(case when a.status_doc = '227' then 1 ELSE 0 END) AS input_order,
	SUM(case when a.status_doc = '228' then 1 ELSE 0 END) AS obl_done
FROM
	0_document a
	JOIN 0_projects p ON a.id_rso = p.ID_RSO
GROUP BY
	p.Witel WITH ROLLUP 
	";
		$data = DB::select(DB::raw($sql));
		
		return $content
			// ->css_file(Admin::asset("open-admin/css/pages/dashboard.css"))
			->title('Dashboard')
			->description('Bidding Management Regional')
			->row(function (Row $row) {

				$row->column(3, function (Column $column) {
					$sql_noobl = "
SELECT
	COUNT(1) AS total
FROM
	0_projects a
	left JOIN 0_document b ON a.ID_RSO = b.id_rso
WHERE
	a.status_project = '120'
	AND a.is_verified = '1'
	AND b.id_rso IS NULL		
					";
					$data_noobl = DB::select(DB::raw($sql_noobl));
					
					$infoBox1 = new InfoBox('Perlu Create OBL', 'info-circle', 'danger', '/projess/projects?noOBL=1', $data_noobl[0]->total);
					$column->append($infoBox1->render());
				});

				$row->column(2, function (Column $column) {
					$data = document::selectRaw('count(1) as total')->get();
					
					$infoBox1 = new InfoBox('Total OBL', 'clipboard-list', 'primary', '/projess/document', $data[0]->total);
					$column->append($infoBox1->render());
				});

				$row->column(2, function (Column $column) {
					$data = document::selectRaw('count(1) as total')->where('status_doc', '=', '170')->get();
					
					$infoBox2 = new InfoBox('Wait KB', 'chart-pie', 'success', '/projess/document?status_doc=170', $data[0]->total);
					$column->append($infoBox2->render());
				});

				$row->column(2, function (Column $column) {
					// $data = document::selectRaw('count(1) as total')->where('status_doc', '!=', '170')->get();
					$data = document::selectRaw('count(1) as total')->whereRaw("CAST(status_doc AS UNSIGNED) < 170")->get();
					
					$infoBox2 = new InfoBox('InProgress', 'chart-area', 'warning', '/projess/document', $data[0]->total);
					$column->append($infoBox2->render());
				});

				$row->column(3, function (Column $column) {
					$data = document::selectRaw('round(sum(NILAI_KL)/1000000,0) as total')->get();
					
					$infoBox2 = new InfoBox('Nilai Kontrak Layanan', 'chart-line', 'secondary', '/projess/document', number_format($data[0]->total, 0, ',', '.') . ' (Juta)');
					$column->append($infoBox2->render());
				});
			})
			->body(view('projects.db_bmbs',
				['data' => $data]
				));
	}	

	public function dashboardPM()
	{
		$content = new Content();
		
		$sql = "
SELECT
    witel,
    status_delivery,
    COUNT(id) AS jumlah_proyek,
    ROUND(AVG(durasi_delivery), 2) AS rata_rata_durasi_delivery_hari,
    MIN(tgl_rfs) AS rfs_paling_awal,
    MAX(tgl_rfs) AS rfs_paling_akhir
FROM
    0_project_mgmt
WHERE
    -- Filter untuk hanya mengambil baris yang memiliki data valid di kolom pengelompokan
    witel IS NOT NULL AND witel != '' AND
    status_delivery IS NOT NULL AND status_delivery != '' AND
    tipe IS NOT NULL AND tipe != ''
GROUP BY
    witel,
    status_delivery
ORDER BY
    witel,
    status_delivery DESC;
	";
		$data = DB::select(DB::raw($sql));
		
		return $content
			// ->css_file(Admin::asset("open-admin/css/pages/dashboard.css"))
			->title('Dashboard')
			->description('Project Management Regional')
			->row(function (Row $row) {

				$row->column(3, function (Column $column) {
					$data = projectsMgmt::selectRaw('count(1) as total')->get();
					
					$infoBox1 = new InfoBox('Total Project', 'clipboard-list', 'primary', '/projess/project-management', $data[0]->total);
					$column->append($infoBox1->render());
				});

				$row->column(3, function (Column $column) {
					$data = projectsMgmt::selectRaw('count(1) as total')->where('status_delivery', '=', 'Completed')->get();
					
					$infoBox2 = new InfoBox('Project Completed', 'chart-pie', 'success', '/projess/project-management?status_delivery=Completed', $data[0]->total);
					$column->append($infoBox2->render());
				});

				$row->column(3, function (Column $column) {
					$data = projectsMgmt::selectRaw('count(1) as total')->where('status_delivery', '=', 'Inprogress')->get();
					
					$infoBox2 = new InfoBox('Project InProgress', 'chart-area', 'warning', '/projess/project-management?status_delivery=Inprogress', $data[0]->total);
					$column->append($infoBox2->render());
				});

				$row->column(3, function (Column $column) {
					$data = projectsMgmt::selectRaw('count(1) as total')->where('status_delivery', '=', 'Issue')->get();
					
					$infoBox2 = new InfoBox('Project Kendala', 'chart-line', 'danger', '/projess/project-management?status_delivery=Issue', $data[0]->total);
					$column->append($infoBox2->render());
				});
			})
			->body(view('projects.db_pm',
				['data' => $data]
				));
	}	

	public function dashboardSOS()
	{
		$content = new Content();
		
		$sql = "
SELECT
    witel,
    status_delivery,
    COUNT(id) AS jumlah_proyek,
    ROUND(AVG(durasi_delivery), 2) AS rata_rata_durasi_delivery_hari,
    MIN(tgl_rfs) AS rfs_paling_awal,
    MAX(tgl_rfs) AS rfs_paling_akhir
FROM
    0_project_mgmt
WHERE
    -- Filter untuk hanya mengambil baris yang memiliki data valid di kolom pengelompokan
    witel IS NOT NULL AND witel != '' AND
    status_delivery IS NOT NULL AND status_delivery != '' AND
    tipe IS NOT NULL AND tipe != ''
GROUP BY
    witel,
    status_delivery
ORDER BY
    witel,
    status_delivery DESC;
	";
		$data = DB::select(DB::raw($sql));
		
		return $content
			// ->css_file(Admin::asset("open-admin/css/pages/dashboard.css"))
			->title('Dashboard')
			->description('Project Management Regional')
			->row(function (Row $row) {

				$row->column(3, function (Column $column) {
					$data = projectsMgmt::selectRaw('count(1) as total')->get();
					
					$infoBox1 = new InfoBox('Total Project', 'clipboard-list', 'primary', '/projess/project-management', $data[0]->total);
					$column->append($infoBox1->render());
				});

				$row->column(3, function (Column $column) {
					$data = projectsMgmt::selectRaw('count(1) as total')->where('status_delivery', '=', 'Completed')->get();
					
					$infoBox2 = new InfoBox('Project Completed', 'chart-pie', 'success', '/projess/project-management?status_delivery=Completed', $data[0]->total);
					$column->append($infoBox2->render());
				});

				$row->column(3, function (Column $column) {
					$data = projectsMgmt::selectRaw('count(1) as total')->where('status_delivery', '=', 'Inprogress')->get();
					
					$infoBox2 = new InfoBox('Project InProgress', 'chart-area', 'warning', '/projess/project-management?status_delivery=Inprogress', $data[0]->total);
					$column->append($infoBox2->render());
				});

				$row->column(3, function (Column $column) {
					$data = projectsMgmt::selectRaw('count(1) as total')->where('status_delivery', '=', 'Issue')->get();
					
					$infoBox2 = new InfoBox('Project Kendala', 'chart-line', 'danger', '/projess/project-management?status_delivery=Issue', $data[0]->total);
					$column->append($infoBox2->render());
				});
			})
			->body(view('projects.db_pm',
				['data' => $data]
				));
	}	

	public function dashboardPMnew()
	{
		$content = new Content;
		
		return $content
			->title('Dashboard')
			->description('Project Management Regional')			
			->body(view('pm.home'));
	}
	
    public function index(Content $content)
    {
		// Check user roles
		
		// Dashboard AM
		if (Admin::user()->inRoles(['account_manager'])) {
			return redirect(env('APP_URL') . config('admin.route.prefix') . "/projects");
		}

		// Manajemen Witel, Segmen & Regional 
		// Under Construction
		
		// Dashboard PIS
		if (Admin::user()->inRoles(['regional_partner_invoicing'])) {return redirect('/projess/validasi-dokumen');}

		// Dashboard Support Segmen / B2B Specialist
		if (Admin::user()->inRoles(['support_segmen', 'management_segmen'])) {return $this->dashboardSsegmen();}
		
		// Dashboard BMBS Regional
		if (Admin::user()->inRoles(['bmbs_regional'])) {return $this->dashboardBMBS();}
		
		// Dashboard SOS Regional
		if (Admin::user()->inRoles(['service_operation'])) {return $this->dashboardSOS();}

		// Dashboard Solution Regional
		if (Admin::user()->inRoles(['SO_regional', 'management_regional'])) {return $this->dashboardSolution();}
		
		// Dashboard Project Management
		if (Admin::user()->inRoles(['project_mgmt'])) {return $this->dashboardPMnew();} //{return $this->dashboardPM();}

		// Dashboard Solution Engineer
		if (Admin::user()->inRoles(['solution_engineer', 'management_witel'])) {return $this->dashboardSE();}

		// Support NJKI
		if (Admin::user()->inRoles(['support_njki'])) {return redirect('/projess/investasi');}
		
		// Dashboard Admin
        return $content
            ->css_file(Admin::asset("open-admin/css/pages/dashboard.css"))
            ->title('Dashboard')
            ->description('Projess System')
            ->row(Dashboard::title())
            ->row(function (Row $row) {

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::environment());
                });

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::extensions());
                });

                $row->column(4, function (Column $column) {
                    $column->append(Dashboard::dependencies());
                });
            });
    }
}
