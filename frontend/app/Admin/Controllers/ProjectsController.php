<?php

namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Grid\Displayers\Actions\ContextMenuActions;
use OpenAdmin\Admin\Form;
// use OpenAdmin\Admin\Widgets\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;
use App\Http\Controllers\Controller;
use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\Layout\Column;
use OpenAdmin\Admin\Layout\Content;
use OpenAdmin\Admin\Layout\Row;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenAdmin\Admin\Widgets\Box;
use \App\Models\projects;
use \App\Models\document;
use \App\Models\diskusi;
use \App\Models\User;
use App\Admin\Actions\filesAction;
use App\Admin\Actions\diskusiAction;
use App\Admin\Controllers\ProjessTaskController;
use App\Models\projess_log;

class ProjectsController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Projects';

	public function getUrut()
	{
		$sql = "
SELECT
	max(id) AS urut
FROM
	0_projects a
LIMIT
	1;
		";
		
		$urut = DB::select(DB::raw($sql));
		
		return $urut[0]->urut + 1;
	}
	
	protected function getFollowup($step)
	{
		$sql = "
SELECT
	a.step_next AS followup, concat('LANJUT - ', b.step) AS followup_step
FROM
	0_workflow a
	JOIN 0_workflow b ON a.step_next = b.step_id
WHERE
	a.step_id = '$step'
UNION ALL 
SELECT
	a.step_next1 AS followup, concat('LANJUT - ', b.step) AS followup_step
FROM
	0_workflow a
	JOIN 0_workflow b ON a.step_next1 = b.step_id
WHERE
	a.step_id = '$step'
UNION ALL 
SELECT
	a.step_back AS followup, concat('RETURN - ', b.step) AS followup_step
FROM
	0_workflow a
	JOIN 0_workflow b ON a.step_back = b.step_id
WHERE
	a.step_id = '$step'
		";
		
		$followup = DB::select(DB::raw($sql));
		
		$return = array();
		foreach($followup as $item) {
			$return[$item->followup] = $item->followup_step;
		}
		
		// Insert opsi Drop
		if ($step != '299') {$return['299'] = 'DROP PROJECT...!!!';}
		
		// dd($return);
		return $return;
	}
	

    protected function diskusi(Content $content, $ID_RSO)
    {
		$user = Admin::user();
		$project = projects::findOrFail($ID_RSO);
		
		$data = DB::table('0_diskusi as a')
				->select('a.id', 'a.object_id', 'a.comment', 'a.reply_to', 'a.created_at', 'b.name', 'b.avatar', 'role.name as role')
				->join('admin_users as b', 'a.user_id', '=', 'b.id')
				->leftjoin('admin_roles as role', 'a.user_role', '=', 'role.id')
				->where('a.object_id', '=', $ID_RSO)
				->orderBy('a.created_at', 'desc')
				->get();
		
		// $box = new Box($project->Keterangan, 'Nilai Project : ' . number_format($project->Nilai_Project_Total, 0, ',', '.'));
		
		$form = new \OpenAdmin\Admin\Widgets\Form();
		$form->action('/' . config('admin.route.prefix') . '/diskusi/save');
		$form->hidden('user_id')->default($user->id);
		$form->hidden('user_role')->default($user->roles[0]['id']);
		$form->hidden('object_id')->default($ID_RSO);
		// $form->textarea('comment', 'Komentar')->rows(3);
		$form->ckeditor('comment', 'Komentar')->required();

        return $content
            ->title($project->Customer)
            ->description($project->Nama_Project)
			// ->row($box->render())
            ->row(view('projects.diskusi', 
				[
					'diskusi' => $data,
					'user' => $user,
					'form' => $form->render(),
					'host' => env('APP_URL'),
					'def_avatar' => config('admin.default_avatar'),
				])
				);
    }

	protected function whereBuild() 
	{
		// Segmen
		$whSegmen = is_null(Admin::user()->segmen) ? '1' : "segmen = '" . Admin::user()->segmen . "'";
		$whSegmen = Admin::user()->segmen == 'REPS' ? "segmen IN ('RES', 'RPS')" : $whSegmen;
		
		// Witel
		if (is_null(Admin::user()->witel) or Admin::user()->witel == 'Regional') {
			$whWitel = '1';
		} else {
			$whWitel = "Witel = '" . Admin::user()->witel . "'";
			$whWitel = Admin::user()->witel == 'Kalselteng' ? "Witel IN ('Kalsel', 'Kalteng', 'Kalselteng')" : $whWitel;
			$whWitel = Admin::user()->witel == 'Kaltimtara' ? "Witel IN ('Kaltim', 'Kaltara', 'Kaltimtara')" : $whWitel;			
		}
				
		return $whSegmen . " AND " . $whWitel;
	}

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new projects());

		if (request('noOBL') == '1') {
			$grid->model()
			->leftjoin('0_document', '0_projects.ID_RSO', '=', '0_document.id_rso')
			->where('status_project', '=', '120')
			->where('is_verified', '=', '1')
			->whereNull('0_document.id_rso');
		};
		
		// $grid->model()->whereRaw($this->whereBuild());
		
		// Check Roles and adjust data 
		if (Admin::user()->isRole('account_manager')) {
			// $grid->model()->where('created_by', '=', "'" . Admin::user()->id . "'");
			$grid->model()->where('AM', '=', Admin::user()->name);
		} else {
			$grid->model()->whereRaw($this->whereBuild());
		}

		$grid->model()->orderBy('0_projects.created_at', 'desc');

		// Column Grid
		$grid->column('ID_RSO', 'ID RSO')->style('font-size:small;')->display(function ($col) {
			$user = DB::table('admin_users')->select('name')->where('id', '=', $this->created_by)->get();
			$name = count($user) > 0 ? $user[0]->name : '';
			
			return "<a href='/" . config('admin.route.prefix') . "/projects/$col'>$col</a><br/><small>by : " . $name . "</small>";
		});
		$grid->column('Witel', 'Witel')->style('font-size:small;')->display(function ($witel) {
			return $witel . " - " . $this->segmen . "<br/>" . $this->AM;
		});
		$grid->column('Customer', 'Customer')->style('font-size:small;')->width(400)->display(function ($cust) {			
			return $cust . " | Tahun " . $this->Project_Tahun . "<br/><small data-toggle='tooltip' data-placement='top' title='$this->Nama_Project'>" . $this->Nama_Project . "</small>";
		});
		$grid->column('komentar', 'Last Komentar')->width(400)->style('font-size:small;')->display(function ($item) {
			$komen = DB::table('0_diskusi')
						->selectRaw('admin_users.name, comment, 0_diskusi.created_at')
						->join('admin_users', 'admin_users.id', '=', '0_diskusi.user_id')
						->where('object_id', '=', $this->ID_RSO)
						->orderBy('created_at', 'desc')
						->limit(1)
						->get();
			
			if(count($komen) == 0) {return; }
			$return = '<small>' . $komen[0]->name . ' | ' . $komen[0]->created_at . '</small><br/>';
			$return .= '<a href="'. env('APP_URL') . config('admin.route.prefix') .'/projects/'. $this->ID_RSO .'/diskusi"><small>' . $komen[0]->comment . '</small></a>';
			
			return $return;
		});
		$grid->column('input_komentar', 'Input Komentar')->style('font-size:small;')->display(function ($item) {
			return 'Input';
		})->modal('Input Komentar', function ($item) {
			$form = new \OpenAdmin\Admin\Widgets\Form();
			$form->action('/' . config('admin.route.prefix') . '/diskusi/save');
			$form->hidden('user_id')->default(Admin::user()->id);
			$form->hidden('user_role')->default(Admin::user()->roles[0]['id']);
			$form->hidden('object_id')->default($this->ID_RSO);
			$form->textarea('comment', 'Komentar')->rows(3)->required();
			// $form->ckeditor('comment', 'Komentar')->required();
			
			return $form->render();
		});
		$grid->column('pstatus.step', 'Status')->style('font-size:small;')->display(function ($item) {
			$colors = config('appConst.statusColors');
			
			$flag = $this->is_win == '1' ? '<span class="badge bg-success">SUDAH WIN</span>' : '<span class="badge bg-secondary">BELUM WIN</span>';
			$verified = $this->is_verified == '1' ? '<span class="badge bg-success">VERIFIED</span>' : '';
			$step = '<span class="badge bg-'. $colors[$this->status_project] .'">' . $item . '</span>';
			
			return $flag . ' ' . $verified . '<br/>' . $step;
		});
		$grid->column('obl', 'OBL List')->style('font-size:small;')->display(function ($item) {
			$docs = document::select('id_rso', 'id_obl', 'LAYANAN', 'status_doc', 'w.id', 'w.step')
					->where('id_rso', '=', $this->ID_RSO)
					->join('0_workflow as w', '0_document.status_doc', '=', 'w.step_id')
					->get();
			
			$result = '';
			foreach ($docs as $doc) {
				$result .= '<a href="/projess/document/' . $doc->id . '"><span class="badge bg-primary">' . $doc->LAYANAN . ' (' . $doc->step . ')</span></a><br/>';
			}
			
			return $result;
		});
		$grid->column('created_at', 'Tanggal Create')->style('font-size:small;')->sortable();
		$grid->column('updated_at', 'Tanggal Update')->style('font-size:small;')->sortable();

		// Customize Grid
		$grid->fixColumns(1, -1);
		// $grid->disableCreateButton();
		$grid->disableExport();
		$grid->disableRowSelector();
		$grid->disableColumnSelector();
		
		// Grid Header
		$grid->header(function ($query) {
			if(Admin::user()->can('projects_download')) {
			return '<div class="col-auto me-auto"><a href="/' . config('admin.route.prefix') . '/export/projects" target="_blank"><button type="button" class="btn btn-outline-dark"> Download Data Projects </button></a></div>';
			} else {
				return;
			}
		});	
	
		
		// Grid Action
		$grid->setActionClass(ContextMenuActions::class);
		$grid->actions(function (Grid\Displayers\Actions\Actions $actions) {
			// $actions->disableView();
			// $actions->disableEdit();
			if (!Admin::user()->can('project_manage')) { $actions->disableDelete(); }
			$actions->showLabels(true);
			$actions->add(new filesAction());
			$actions->add(new diskusiAction());
		});
		
		$grid->filter(function($filter){
			$filter->disableIdFilter();

			// Add a column filter
			$filter->column(1/2, function ($filter) {
				$filter->like('ID_RSO', 'ID RSO');
				$filter->like('Customer', 'Customer');
				$filter->like('AM', 'AM');
				$filter->equal('is_verified', 'VERIFIED ?')->radio(['1' => 'Sudah Terverifikasi']);
				// $filter->where(function ($query) {
					// $query->whereHas('doc', function ($query) {
						// $query->whereNull('id_rso');
					// });
				// }, 'Dok OBL ?')->radio(['0' => 'Belum Ada']);;
			});
			
			$filter->column(1/2, function ($filter) {
				$filter->equal('Witel')->select(config('appConst.witels'));
				$filter->equal('Segmen', 'Segmen')->select(config('appConst.segmens'));
				$filter->equal('status_project', 'Status')->select(config('appConst.pstatus'));
				$filter->equal('is_win', 'WIN ?')->radio(['0' => 'Belum WIN', '1' => 'Sudah WIN']);
			});

			// $filter->expand();
		});

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(projects::findOrFail($id));
		$model = $show->getModel();
		$user = Admin::user();
		
		$show->panel()
			->style('none')
			->title($model->Customer . ' - Tahun ' . $model->Project_Tahun)
			->view('projects.panel_project')
			->tools(function ($tools) {
				// $tools->disableEdit();
				// $tools->disableList();
				if (!Admin::user()->can('project_manage')) { $tools->disableDelete(); }
			});
			
		// Tab Diskusi
		$data = DB::table('0_diskusi as a')
				->select('a.id', 'a.object_id', 'a.comment', 'a.reply_to', 'a.created_at', 'b.name', 'b.avatar', 'role.name as role')
				->join('admin_users as b', 'a.user_id', '=', 'b.id')
				->leftjoin('admin_roles as role', 'a.user_role', '=', 'role.id')
				->where('a.object_id', '=', $model->ID_RSO)
				->orderBy('a.created_at', 'desc')
				->get();

		// Form diskusi
		$form = new \OpenAdmin\Admin\Widgets\Form();
		$form->action('/' . config('admin.route.prefix') . '/diskusi/save');
		$form->hidden('user_id')->default($user->id);
		$form->hidden('user_role')->default($user->roles[0]['id']);
		$form->hidden('object_id')->default($model->ID_RSO);
		// $form->textarea('comment', 'Komentar')->rows(3);
		$form->ckeditor('comment', 'Komentar')->required();
		
		// Form FollowUp
		$form_process = new \OpenAdmin\Admin\Widgets\Form();
		$form_process->action('/' . config('admin.route.prefix') . '/workflow/projects/followup');
		$form_process->title($model->Customer . ' - ' . $model->Nama_Project);
		$form_process->radio('followup')->options($this->getFollowup($model->status_project))->stacked()->required();
		$form_process->hidden('user_id')->default($user->id);
		$form_process->hidden('user_role')->default($user->roles[0]['id']);
		$form_process->hidden('object_id')->default($model->ID_RSO);
		$form_process->hidden('currstatus')->default($model->status_project);
		$form_process->textarea('comment', 'Komentar')->rows(3)->required();

		$show->panel()->data['diskusi'] = $data;
		$show->panel()->data['user'] = $user;
		$show->panel()->data['form'] = $form->render();
		$show->panel()->data['host'] = env('APP_URL');
		$show->panel()->data['def_avatar'] = config('admin.default_avatar');
		$show->panel()->data['form_process'] = $form_process->render();
		$show->panel()->data['progress_project'] = ceil($model->status_project/221*100);

		// Fields Show
		$show->field('pstatus.step', 'ID RSO | Status')->unescape()->as(function ($item) {
			$id = '<span class="badge bg-primary">' . $this->ID_RSO . '</span>';
			
			$colors = config('appConst.statusColors');
			$pstatus = '<span class="badge bg-'. $colors[$this->status_project] .'">' . $item . '</span>';
			
			return $id . ' ' . $pstatus;
		});
		$show->field('Witel', 'Witel | Segmen')->unescape()->as(function ($item) {
			$witel = '<span class="badge bg-warning">' . $item . '</span>';
			$segmen = '<span class="badge bg-warning">' . $this->segmen . '</span>';
			
			return $witel . ' ' . $segmen;
		});
		$show->field('Nama_Project', 'Nama Project')->unescape()->as(function ($item) {
			$flag = $this->is_win == '1' ? '<span class="badge bg-success">SUDAH WIN</span>' : '<span class="badge bg-secondary">BELUM WIN</span>';
			$verified = $this->is_verified == '1' ? '<span class="badge bg-success">VERIFIED</span>' : '<span class="badge bg-secondary">NOT VERIFIED</span>';
			$ngtma = $this->is_ngtma == '1' ? '<span class="badge bg-info">NGTMA</span>' : '';
			$ibl = $this->is_ibl == '1' ? '<span class="badge bg-info">Inbound Logistic</span>' : '';
			
			return $item . '<br/>' . $flag . ' ' . $verified . ' ' . $ngtma . ' ' . $ibl;
		});
		$show->field('p1_tanggal', 'Dokumen P1')->unescape()->as(function ($item) {
			$return = 'Nomor : '. $this->p1_nomor .' | '. $item .'<br/>';
			$return .= '<small>'. $this->p1_namaKontrak .'</small>';
			
			return is_null($item) ? '<i>[Dokumen P1 belum ada]</i>' : $return;
		});
		$show->field('AM', 'AM');
		$show->field('tanggal_rfs', 'Tanggal')->unescape()->as(function ($item) {
			$rfs = is_null($item) ? '#' : $item->format('d M Y');
			$layanan = is_null($this->tanggal_layanan) ? '#' : $this->tanggal_layanan->format('d M Y');
			
			return 'RFS : ' . $rfs . '<br/>' . 'Layanan : ' . $layanan;
		});
		$show->field('skema_pembayaran', 'Skema Bisnis')->unescape()->as(function ($item) {
			$top =  is_null($item) ? '###' : config('appConst.term_of_payment')[$item];
			$bisnis = is_null($this->skema_bisnis) ? '###' : config('appConst.skema_bisnis')[$this->skema_bisnis];
			
			return '<span class="badge bg-warning">' . $top . '</span> <span class="badge bg-warning">' . $bisnis . '</span>';
		});
		$show->field('tipe_projek', 'Tipe Projek')->unescape()->as(function ($item) {
			if (is_null($item) || $item === '') {
				return '<span class="badge bg-danger">Belum diisi</span>';
			}
			$tipeProject = config('appConst.tipe_project');
			$label = isset($tipeProject[$item]) ? $tipeProject[$item] : $item;
			return '<span class="badge bg-info">' . $label . '</span>';
		});
		$show->field('is_renewal_only', 'Is Renewal Only')->unescape()->as(function ($item) {
			$renewal = $item == '1' || $item === 1 || $item === true ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>';
			return $renewal;
		});

		$show->field('doc', 'List OBL')->unescape()->as(function($items) {
			$docs = document::select('0_document.id as id_doc', '0_document.id_rso', '0_document.id_obl', '0_document.status_doc', '0_document.LAYANAN', '0_document.NO_QUOTE', '0_document.NO_ORDER', 'w.id', 'w.step')
					->where('0_document.id_rso', '=', $this->ID_RSO)
					->join('0_workflow as w', '0_document.status_doc', '=', 'w.step_id')
					->get();			
					
			return view('projects.listObl', ['list' => $docs, 'prefix' => config('admin.route.prefix')]);
			// return view('projects.listObl', ['list' => $items, 'prefix' => config('admin.route.prefix')]);
		});
		
		$show->divider();

		$show->field('Nilai_Project_Total')->as(function ($content) {
			return is_numeric($content) ? number_format($content, 0, ',', '.') : $content;
		});
		$show->field('jangka_waktu', 'Jangka Waktu')->unescape()->as(function ($item) {
			$jw = is_null($item) ? '#' : $item;
			$start = is_null($this->start_kontrak) ? '#' : date_format($this->start_kontrak, 'Y/m/d');
			$end = is_null($this->end_kontrak) ? '#' : date_format($this->end_kontrak, 'Y/m/d');
			
			return $jw . ' bulan | ' . $start . ' - ' . $end;
		});
		$show->field('nilai_obl', 'Nilai OBL')->as(function ($content) {
			return is_numeric($content) ? number_format($content, 0, ',', '.') : $content;
		});
		$show->field('nilai_ibl', 'Nilai IBL')->as(function ($content) {
			return is_numeric($content) ? number_format($content, 0, ',', '.') : $content;
		});
		$show->field('profit', 'Profit')->as(function ($content) {
			return is_numeric($content) ? number_format($content, 0, ',', '.') : $content;
		});
		$show->field('cogs', 'COGS')->as(function ($content) {
			return is_numeric($content) ? number_format($content, 0, ',', '.') : $content;
		});
		$show->field('nilai_irr', 'Nilai IRR');
		$show->field('npv', 'NPV');
		$show->field('peb', 'Payback Period (bulan)');
		$show->field('is_njki', 'NJKI')->using(['0' => 'Tidak perlu NJKI', '1' => 'Perlu NJKI']);
		$show->field('nilai_boq', 'Nilai BOQ');
		$show->field('Keterangan', 'Deskripsi')->unescape()->as(function ($item) {
			return $item;
		});


		$show->divider();

		$show->field('files', 'Files')->unescape()->as(function () {
			$url = env('APP_URL') . config('admin.route.prefix') . '/media?path=%2Fdocs%2F' . $this->getKey() . '&fn=selectFile';
			return "<a href='$url' target='_blank'><button type='button' class='btn btn-outline-primary'>Lihat Files</button></a>";
		});
		// $show->field('diskusi', 'Diskusi')->unescape()->as(function () {
			// $url = env('APP_URL') . config('admin.route.prefix') . '/projects/' . $this->getKey() . '/diskusi';
			// return "<a href='$url'><button type='button' class='btn btn-outline-primary'>Lihat Diskusi</button></a>";
		// });
		
		$show->field('followup', 'FollowUp')->unescape()->as(function () {
			// Workflow links
			$url = env('APP_URL') . config('admin.route.prefix') . '/workflow/projects/' . $this->getKey() . '/' . $this->status_project;
			$next = "<a href='$url/process'><button type='button' class='btn btn-outline-primary'>Process</button></a>";
			$back = " <a href='$url/return'><button type='button' class='btn btn-outline-warning'>Return</button></a>";
			$drop = " <a href='$url/drop'><button type='button' class='btn btn-danger'>DROP Project</button></a>";
			$followup = '<button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#followupModal">FollowUp</button>';
			
			// Kickoff links
			$url_kf = env('APP_URL') . config('admin.route.prefix') . '/projects/' . $this->getKey() . '/kickoff';
			$kickoff = " <a href='$url_kf'><button type='button' class='btn btn-outline-success'>RUN KickOff</button></a>";
			
			// Input OBL links
			$url_obl = env('APP_URL') . config('admin.route.prefix') . '/projects/' . $this->getKey() . '/document';
			$in_obl = " <a href='$url_obl'><button type='button' class='btn btn-outline-success'>Input Layanan OBL</button></a>";

			
			// $return = (Admin::user()->can($this->pstatus['step_permission']) and !is_null($this->pstatus['step_next'])) ? $next : '';
			// $return .= (Admin::user()->can($this->pstatus['step_permission']) and !is_null($this->pstatus['step_back'])) ? $back : '';
			// $return .= (Admin::user()->can('project_manage') and $this->status_project != '299') ? $drop : '';
			$return = (Admin::user()->can($this->pstatus['step_permission']) and !is_null($this->pstatus['step_permission'])) ? $followup : '';
			$return .= (($this->pstatus['step_id'] >= 90) and ($this->pstatus['step_id'] < 230) and (Admin::user()->can('project_kickoff'))) ? $kickoff : '';
			$return .= (($this->pstatus['step_id'] == 120) and (Admin::user()->can('obl_input'))) ? $in_obl : '';
			
			return $return;
		});


		$show->field('pkickoff', 'Hasil KickOff')->unescape()->as(function($items) {
			return view('projects.listKickoff', ['list' => $items, 'prefix' => config('admin.route.prefix')]);
		});


        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new projects());

		$form->tab('Project Information', function ($form) {
			$form->text('ID_RSO', 'ID RSO')->readonly();
			$form->select('Witel', 'Witel')->options(config('appConst.witels'))->required();
			$form->select('segmen', 'Segmen')->options(config('appConst.segmens'))->required();
			// $form->text('AM', 'AM')->value(Admin::user()->name)->required();
			$form->text('AM', 'AM')->required();
			$form->select('Project_Tahun', 'Project Tahun')->options(config('appConst.tahuns'))->required();
			$form->text('Customer', 'Customer')->required();
			$form->textarea('Nama_Project', 'Nama Project')->rows(2)->required();
			$form->number('jangka_waktu', 'Jangka Waktu KB')
				->help('Jangka Waktu Kontrak (dalam bulan)');
			$form->dateRange('start_kontrak', 'end_kontrak', 'Start - End KB');
			$form->text('draft_kb', 'Link Draft KB');
			$form->ckeditor('Keterangan', 'Deskripsi')->required();
			$form->radio('is_win', 'Status WIN')->options([
				'0' => 'Belum WIN',
				'1' => 'Sudah WIN'
				])->stacked()->required();
			// Verifikasi project
			if (Admin::user()->can('project_manage')) {
				$form->switch('is_verified', 'Sudah Terverifikasi')->help('Verifikasi project oleh Solution Regional');
			}
			$form->isCreating() ? $form->hidden('status_project')->value('10') : $form->hidden('status_project');
			$form->isCreating() ? $form->hidden('created_by')->value(Admin::user()->id) : $form->hidden('created_by');
		})->tab('Layanan', function ($form) {
			$form->date('tanggal_rfs', 'Tanggal RFS');
			$form->date('tanggal_layanan', 'Tanggal Layanan');
			$form->radio('skema_pembayaran', 'Skema Pembayaran')->options(config('appConst.term_of_payment'))->stacked();
			$form->radio('skema_bisnis', 'Skema Bisnis')->options(config('appConst.skema_bisnis'))->stacked();
			// $form->switch('is_ngtma', 'NGTMA');
			// $form->switch('is_ibl', 'Inbound Logistic');
			$form->select('tipe_projek', 'Tipe Projek')
				->options(config('appConst.tipe_project'))
				->required()
				->help('Field ini wajib diisi untuk menentukan mapping task project. Tipe Projek digunakan untuk menentukan task yang sesuai dengan project ini.');
			$form->switch('is_renewal_only', 'Renewal Only');
		})->tab('Nilai Project', function ($form) {
			$form->currency('Nilai_Project_Total')->symbol('Rp ');
			$form->currency('nilai_obl', 'Nilai OBL')->symbol('Rp ');
			$form->currency('nilai_ibl', 'Nilai IBL')->symbol('Rp ');
			$form->currency('profit', 'Profit')->symbol('Rp ');	
			$form->currency('cogs', 'COGS')->symbol('Rp ');	
			$form->currency('nilai_irr', 'IRR')->symbol('#');
			$form->currency('npv', 'Net Present Value')->symbol('Rp');	
			$form->currency('peb', 'Payback Period (bulan)')->symbol('#');	
			$form->switch('is_njki', 'Perlu NJKI ?');
			$form->currency('nilai_boq', 'Nilai BOQ')->symbol('Rp ');			
		});
		//})->tab('Dokumen P1', function ($form) {
		//	$form->date('p1_tanggal', 'Tanggal P1')->help('Tanggal Dokumen P1');
		//	$form->text('p1_nomor', 'Nomor P1')->help('Nomor Dokumen P1');
		//	$form->textarea('p1_namaKontrak', 'Judul Dokumen P1')->rows(3)->help('Judul Dokumen P1');
		//});
		
		
		$form->tools(function (Form\Tools $tools) {
			// Disable `List` btn.
			$tools->disableList();

			// Disable `Delete` btn.
			$tools->disableDelete();

			// Disable `Veiw` btn.
			$tools->disableView();
		});
		
		$form->footer(function ($footer) {

			// disable `View` checkbox
			$footer->disableViewCheck();

			// disable `Continue editing` checkbox
			$footer->disableEditingCheck();

			// disable `Continue Creating` checkbox
			$footer->disableCreatingCheck();

		});

		// Before Saving callback
		$form->saving(function (Form $form) {
			// Validasi tipe_projek wajib diisi
			if (empty($form->tipe_projek) || $form->tipe_projek === null || $form->tipe_projek === '') {
				admin_toastr('Field "Tipe Projek" wajib diisi untuk menentukan mapping task project!', 'error', ['duration' => 5000]);
				throw new \Exception('Field "Tipe Projek" wajib diisi untuk menentukan mapping task project.');
			}

			try {

				$model = $form->model();
				try {
					ProjessTaskController::mapTask($model, false);
				} catch (\Exception $mapException) {
					\Log::warning('MapTask warning on project save: ' . $mapException->getMessage());
				}
				$hasIdRso = isset($model) && isset($model->ID_RSO) && !empty($model->ID_RSO);
				$idRso = $hasIdRso ? $model->ID_RSO : null;
				
				// New data 
				if(!$hasIdRso) {
					// Generate ID_RSO
					$form->ID_RSO = date('y') . $form->segmen . date('m') . $this->getUrut();
					
					// Post Komentar - hanya jika user ada
					if(Admin::user()) {
						$data['user_id'] = Admin::user()->id;
						$data['object_id'] = $form->ID_RSO;
						$data['comment'] = ($form->is_win == '1') ? "** Proposal WIN - Request for OBL **" : "** Request for Proposal **";
						
						diskusi::create($data);
					}
				}
				
				// Set to WIN - hanya untuk edit (model sudah ada)
				if($hasIdRso && Admin::user() && $form->is_win == '1' && isset($model->is_win) && $model->is_win == '0') {
					// Post Komentar
					$data['user_id'] = Admin::user()->id;
					$data['object_id'] = $idRso;
					$data['comment'] = "** Project Set to WIN **";
					
					diskusi::create($data);
				}
			} catch (\Exception $e) {
				// Jangan throw exception - biarkan form tetap bisa disimpan
				\Log::error("Error in projects saving callback: " . $e->getMessage());
			}
		});
		
		// Setelah save callback
		$form->saved(function ($form) {
			$project = $form->model();

			if ($project->wasRecentlyCreated) {
				if (empty($project->task)) {
					try {
						ProjessTaskController::mapTask($project, true);
						$project->refresh();
					} catch (\Exception $mapException) {
						\Log::warning('MapTask warning on project saved: ' . $mapException->getMessage());
					}
				}

				if (!empty($project->task)) {
					projess_log::create([
						'task_id' => $project->task,
						'trackable_type' => 'App\Models\projects',
						'trackable_id' => $project->ID_RSO,
						'model_type' => 'App\Models\projects',
						'model_id' => $project->id,
						'id_rso' => $project->ID_RSO,
						'action_type' => 'create_project',
						'notes' => '## Create Project ##',
						'user_id' => Admin::user()->id,
					]);
				} else {
					\Log::warning('Skip log create_project: task_id is null', [
						'project_id' => $project->id,
						'id_rso' => $project->ID_RSO,
					]);
				}
			} else {
				$taskChanged = $project->wasChanged('task');
				if ($taskChanged && !empty($project->task)) {
					try {
						ProjessTaskController::mapTask($project, true);
						$project->refresh();
					} catch (\Exception $mapException) {
						\Log::warning('MapTask warning on task change: ' . $mapException->getMessage(), [
							'project_id' => $project->id,
							'id_rso' => $project->ID_RSO,
						]);
					}
				}
			}

			admin_toastr('Data berhasil di-simpan.', 'success', ['duration' => 5000]);
			return back();
		});
		
		return $form;
    }
}
