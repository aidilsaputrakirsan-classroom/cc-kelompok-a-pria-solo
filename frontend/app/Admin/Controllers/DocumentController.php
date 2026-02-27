<?php

namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use OpenAdmin\Admin\Layout\Content;
use Illuminate\Support\Facades\DB;
use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use \App\Models\diskusi;
use \App\Models\projects;
use \App\Models\document;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DocumentController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Document OBL';

	protected function drafting($id, $p = 'p2')
	{
		$doc = document::findOrFail($id);
		// try {
			// $project = projects::findOrFail($doc->id_rso);
		// } catch(Exception $e) {
			// $project = new projects();
			// $project->ID_RSO = '24RGS13101';
		// }			
		
		// Dummy data project
		$project = new projects();
		$project->ID_RSO = '24RGS13101';
		$project->p1_namaKontrak = 'Tes Judul Dokumen P1';
		$project->p1_nomor = '12345/P1/12/2024';
		$project->p1_tanggal = $doc->created_at->format('d M Y');
		$project->Customer = 'POLDA KALTIM';

		$wording = config('appConst.tipe_spk_wording');
		$layanan = $wording[$doc->tipe_spk] . $doc->LAYANAN . ' untuk ' . $project->Customer;
		
		switch($p) {
		case 'p2':
			$template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('admin/templates/P2_template.docx'));

			$dibuat = explode('_', config('appConst.officer_obl')[$doc->p2_dibuat]);
			$diperiksa = explode('_', config('appConst.mgr_obl')[$doc->p2_diperiksa]);
			$disetujui = explode('_', config('appConst.sm_rso')[$doc->p2_disetujui]);

			// Process array calon mitra
			$list_calon_mitra = DB::table('0_mitra')->get()->pluck('nama', 'inisial');
			$calon_mitra = array();
			foreach($doc->p2_calon_mitra as $item) {
				$calon_mitra[] = array('calon_mitra' => $list_calon_mitra[$item]);
			}

			$template->setValues([
				'layanan' => $layanan,
				'p1_namaKontrak' => $project->p1_namaKontrak,
				'p1_nomor' => $project->p1_nomor,
				'p1_tanggal' => $doc->created_at->format('d M Y'),
				'p2_hari' => config('appConst.days')[$doc->p2_tanggal->format('l')],
				'p2_day' => $doc->p2_tanggal->day,
				'p2_bulan' => $doc->p2_tanggal->month,
				'p2_tahun' => $doc->p2_tanggal->year,
				'p2_tanggal' => $doc->p2_tanggal->format('d M Y'),
				'p2_dibuat_nama' => $dibuat[1],
				'p2_dibuat_nik' => $dibuat[0],
				'p2_dibuat_jabatan' => $dibuat[2],
				'p2_diperiksa_nama' => $diperiksa[1],
				'p2_diperiksa_nik' => $diperiksa[0],
				'p2_diperiksa_jabatan' => $diperiksa[2],
				'p2_disetujui_nama' => $disetujui[1],
				'p2_disetujui_nik' => $disetujui[0],
				'p2_disetujui_jabatan' => $disetujui[2],
			]);
			$template->cloneBlock('list_calon_mitra', 0, true, false, $calon_mitra);
			
			$pathToSave = storage_path('admin/docs/' . $doc->id_rso . '/OBL/' . $doc->id_obl . '/P2_' . $doc->id_obl . '.docx');
			$template->saveAs($pathToSave);
						
			// return response()->file($pathToSave);
			admin_toastr('Dokumen P2 berhasil di-buat.', 'success', ['duration' => 5000]);
			// $url = env('APP_URL') . config('admin.route.prefix') . '/media?path=%2Fdocs%2F' . $doc->id_rso . '%2FOBL%2F' . $doc->id_obl . '&fn=selectFile';
			$url = env('APP_URL') . config('admin.route.prefix') . '/document/' . $doc->id . '#files';

			return redirect($url);
					
			break;
			
		case 'p3':
			
			$list_mitra = DB::table('0_mitra')->get()->pluck('alamat', 'inisial');
			$oleh = explode('_', config('appConst.mgr_obl')[$doc->p3_dibuat]);
			
			foreach($doc->p2_calon_mitra as $mitra) {
				$tipes = array(
					'psb' => 'P3_template.docx',
					'amandemen' => 'P3_template_amandemen.docx',
					'amd_perpanjangan' => 'P3_template_amandemen.docx',
					'perpanjangan' => 'P3_template_perpanjangan.docx',
				);
				$tipe = $tipes[$doc->tipe_spk];
				
				$template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('admin/templates/' . $tipe));
				$template->setValues([
					'p3_tanggal' => $doc->p3_tanggal->format('d M Y'),
					'p3_nomor' => $doc->p3_nomor,
					'p3_alamat_mitra' => $list_mitra[$mitra],
					'p31_tanggal' => $doc->p3_tanggal->addDays(1)->format('d M Y'),
					'p31_tanggal_hari' => config('appConst.days')[$doc->p3_tanggal->addDays(1)->format('l')],
					'p34_tanggal' => $doc->p3_tanggal->addDays(4)->format('d M Y'),
					'p330_tanggal' => $doc->p3_tanggal->addDays(30)->format('d M Y'),
					'layanan' => $layanan,
					'mgr_obl' => $oleh[1],
				]);
			
				$pathToSave = storage_path('admin/docs/' . $doc->id_rso . '/OBL/' . $doc->id_obl . '/P3_' . $doc->id_obl . '_' . $mitra . '.docx');
				$template->saveAs($pathToSave);
				
				// sleep(1);
			}
			
			// return response()->file($pathToSave);

			admin_toastr('Dokumen P3 berhasil di-buat.', 'success', ['duration' => 5000]);
			// $url = env('APP_URL') . config('admin.route.prefix') . '/media?path=%2Fdocs%2F' . $doc->id_rso . '%2FOBL%2F' . $doc->id_obl . '&fn=selectFile';
			$url = env('APP_URL') . config('admin.route.prefix') . '/document/' . $doc->id . '#files';

			return redirect($url);

			break;
		
		case 'p4':
		
			$template = new \PhpOffice\PhpWord\TemplateProcessor(storage_path('admin/templates/P4_template.docx'));
			$template->setValues([
				'layanan' => $layanan,
				'p4_tanggal' => $doc->p4_tanggal->format('d M Y'),
				'p1_namaKontrak' => $project->p1_namaKontrak,
				'p1_nomor' => $project->p1_nomor,
				'p1_tanggal' => $project->p1_tanggal->format('d M Y'),
				'p4_skema_bisnis' => config('appConst.skema_bisnis')[$doc->p4_skema_bisnis],
				'p4_top' => $doc->p4_top,
				'pelanggan' => $project->Customer,
				'p4_lokasi_instalasi' => $doc->p4_lokasi_instalasi,
				'p4_tgl_delivery' => $doc->p4_tgl_delivery->format('d M Y'),
				'p4_jangka_waktu' => $doc->p4_jangka_waktu,
				'p4_slg' => $doc->p4_slg,
				'p4_tgl_sph' => $doc->p4_tgl_sph->format('d M Y'),
			]);


			// Process array list peserta
			$peserta = array();
			$peserta1 = array();
			foreach($doc->list_peserta as $item) {
				$peserta[] = array('peserta' => $item);
				$peserta1[] = array('peserta1' => $item);
			}

			$template->cloneBlock('list_peserta', 0, true, false, $peserta);
			$template->cloneBlock('list_peserta1', 0, true, false, $peserta1);
		
			$pathToSave = storage_path('admin/docs/' . $doc->id_rso . '/OBL/' . $doc->id_obl . '/P4_' . $doc->id_obl . '.docx');
			$template->saveAs($pathToSave);
			
			admin_toastr('Dokumen P4 berhasil di-buat.', 'success', ['duration' => 5000]);
			$url = env('APP_URL') . config('admin.route.prefix') . '/document/' . $doc->id . '#files';

			return redirect($url);

			break;
		}
		
		return;
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

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new document);
		
		$grid->model()->orderby('created_at', 'desc');
		
		// Customize Grid
		$grid->fixColumns(1, 0);
		$grid->disableCreateButton();
		$grid->disableExport();
		$grid->disableRowSelector();
		$grid->disableColumnSelector();
		$grid->disableActions();

		$grid->filter(function($filter){
			$filter->disableIdFilter();

			// Add a column filter
			$filter->column(1/2, function ($filter) {
				$filter->like('id_obl', 'ID OBL');
				$filter->like('MITRA', 'MITRA');
				$filter->equal('status_doc', 'STATUS')->select(config('appConst.dstatus'));
			});
			$filter->column(1/2, function ($filter) {
				$filter->where(function ($query) {
					$query->whereHas('projects', function ($query) {
						$query->where('Witel', '=', "{$this->input}");
					});
				}, 'WITEL')->select(config('appConst.witels'));

				$filter->where(function ($query) {
					$query->whereHas('projects', function ($query) {
						$query->where('Customer', 'like', "%{$this->input}%");
					});					
				}, 'CUSTOMER');

				$filter->where(function ($query) {
					$query->whereHas('projects', function ($query) {
						$query->where('Nama_Project', 'like', "%{$this->input}%");
					});					
				}, 'PROJECT');
			});
		});
		
		
		// Grid Column
        $grid->column('id_obl', 'ID OBL')->display(function ($item) {
			$user = DB::table('admin_users')->select('name')->where('id', '=', $this->created_by)->get();
			$name = count($user) > 0 ? $user[0]->name : '';

			// return "<a href='" . env('APP_URL') . config('admin.route.prefix') . "/projects/$this->id_rso/document/$this->id'> $item </a>";			
			return "<a href='" . env('APP_URL') . config('admin.route.prefix') . "/document/$this->id'> $item </a><br/><small>by : ". $name ."</small>";			
		});
        $grid->column('projects.Witel', 'WITEL')->display(function ($item) {
			$project = DB::table('0_projects')
						->select('Witel', 'segmen', 'AM', 'Project_Tahun')
						->where('ID_RSO', '=', $this->id_rso)
						->get();

			if (count($project) == 0) { return; }
			
			$return = $project[0]->Witel . ' - ' . $project[0]->segmen . '<br/>';
			$return .= $project[0]->AM;
			
			return $return;
		});
        $grid->column('NAMA_PELANGGAN', 'PELANGGAN')->display(function ($item) {
			return $item. "<br/><small>". $this->LAYANAN ."</small>";
		});
        // $grid->column('LAYANAN', 'LAYANAN');
		$grid->column('NO_QUOTE', 'No Quote')->text();
		$grid->column('NO_ORDER', 'No Order')->text();
        $grid->column('MITRA', 'MITRA');
        $grid->column('JANGKA_WAKTU', 'JW')->display(function ($item) {
			$jw = is_null($item) ? '#' : $item .' bulan';
			$start = is_null($this->start_kontrak) ? '#' : date_format($this->start_kontrak, 'Y/m/d');
			$end = is_null($this->end_kontrak) ? '#' : date_format($this->end_kontrak, 'Y/m/d');
			
			return $jw. '<br/><small>'. $start .' - '. $end .'</small>';
		});
		$grid->column('dstatus.step', 'STATUS')->label('success');

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed   $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(document::findOrFail($id));
		$model = $show->getModel();
		$user = Admin::user();
		
		try {
			$project = projects::findOrFail($model->id_rso);
		} catch (ModelNotFoundException $except) {
			$project = null;
		}
		
		// Cek & Create Directory Document
		$dirPath = storage_path('admin/docs/' . $model->id_rso . '/OBL/' . $model->id_obl);

		//check if the directory exists
		if(!File::isDirectory($dirPath)){
			//make the directory because it doesn't exists
			File::makeDirectory($dirPath, 0755, true, true);
		}

		$show->panel()
			->style('none')
            ->title(is_null($project) ? '[Project Dihapus]' : $project->Customer. ' - ' .$model->LAYANAN)
			->view('projects.panel_document')
			->tools(function ($tools) {
				// $tools->disableEdit();
				// $tools->disableList();
				if(!Admin::user()->can('obl_manage')) { $tools->disableDelete(); }
			});
			
		// Tab Diskusi
		$data = DB::table('0_diskusi as a')
				->select('a.id', 'a.object_id', 'a.comment', 'a.reply_to', 'a.created_at', 'b.name', 'b.avatar', 'role.name as role')
				->join('admin_users as b', 'a.user_id', '=', 'b.id')
				->leftjoin('admin_roles as role', 'a.user_role', '=', 'role.id')
				->where('a.object_id', '=', $model->id_obl)
				->orWhere('a.object_id', '=', $model->id)
				->orderBy('a.created_at', 'desc')
				->get();

		// Form diskusi
		$form = new \OpenAdmin\Admin\Widgets\Form();
		$form->action('/' . config('admin.route.prefix') . '/diskusi/save');
		$form->hidden('user_id')->default($user->id);
		$form->hidden('user_role')->default($user->roles[0]['id']);
		$form->hidden('object_id')->default($model->id_obl);
		// $form->textarea('comment', 'Komentar')->rows(3);
		$form->ckeditor('comment', 'Komentar')->required();

		// Form FollowUp
		$form_process = new \OpenAdmin\Admin\Widgets\Form();
		$form_process->action('/' . config('admin.route.prefix') . '/workflow/document/followup');
		$form_process->title(is_null($project) ? '[Project Dihapus]' : 	$project->Customer . ' - ' . $model->LAYANAN);
		$form_process->radio('followup')->options($this->getFollowup($model->status_doc))->stacked()->required();
		$form_process->hidden('user_id')->default($user->id);
		$form_process->hidden('user_role')->default($user->roles[0]['id']);
		$form_process->hidden('object_id')->default($model->id);
		// $form_process->hidden('object_id')->default($model->id_obl);
		$form_process->hidden('currstatus')->default($model->status_doc);
		$form_process->textarea('comment', 'Komentar')->rows(3)->required();

		// Panel Data 
		$show->panel()->data['diskusi'] = $data;
		$show->panel()->data['user'] = $user;
		$show->panel()->data['form'] = $form->render();
		$show->panel()->data['host'] = env('APP_URL');
		$show->panel()->data['def_avatar'] = config('admin.default_avatar');
		$show->panel()->data['form_process'] = $form_process->render();
		$show->panel()->data['progress'] = ceil($model->status_doc/228*100);
		$show->panel()->data['project'] = $project;
		$show->panel()->data['doc'] = $model;
		// $show->panel()->data['files'] = Storage::disk('admin')->files(storage_path('admin/docs/' . $model->id_rso . '/OBL/' . $model->id_obl . '/'));
		$show->panel()->data['files'] = \File::files(storage_path('admin/docs/' . $model->id_rso . '/OBL/' . $model->id_obl . '/'));
		$show->panel()->data['officer_obl'] = config('appConst.officer_obl');
		$show->panel()->data['mgr_obl'] = config('appConst.mgr_obl');
		$show->panel()->data['sm_rso'] = config('appConst.sm_rso');
		$show->panel()->data['list_mitra'] = DB::table('0_mitra')->get()->pluck('nama', 'inisial');
		// dd($show->panel()->data['files']);

		// Fields Show
		// $show->field('id_rso', 'ID RSO')->badge();
		$show->field('id_obl', 'ID OBL')->badge();
		$show->field('dstatus.step', 'Status')->label();
		$show->field('LAYANAN', 'Layanan');
		$show->field('JENIS_SPK', 'Jenis SPK')->using(config('appConst.jenis_spk'));
		$show->field('tipe_spk', 'Tipe SPK')->using(config('appConst.tipe_spk'));
		if ($model->tipe_spk != 'psb' and !is_null($model->prev_kl_tanggal)) {
			$show->field('kl_prev', 'KL Prev')->unescape()->as(function () {
				$no_kl = 'Nomor : ' . $this->prev_kl_no . ' | Tanggal : ' . $this->prev_kl_tanggal->format('d M Y') . '<br/>';
				$judul_kl = $this->prev_kl_judul;
				
				return $no_kl . $judul_kl;
			});
		}
		$show->field('MITRA', 'Mitra Pelaksana');
		$show->field('JANGKA_WAKTU', 'Jangka Waktu')->unescape()->as(function ($item) {
			$jw = is_null($item) ? '#' : $item;
			$start = is_null($this->start_kontrak) ? '#' : date_format($this->start_kontrak, 'Y/m/d');
			$end = is_null($this->end_kontrak) ? '#' : date_format($this->end_kontrak, 'Y/m/d');
			
			return $jw . ' bulan | ' . $start . ' - ' . $end;
		});
		$show->field('NILAI_KL')->as(function ($item) {
			return number_format($item, 0, ',', '.');
		});
		$show->field('NO_KFS_SPK');
		$show->field('NO_P8');
		$show->field('NO_KL_WO_SURAT_PESANAN');
		$show->field('STATUS_SM');
		$show->field('NO_QUOTE');
		$show->field('SID');
		$show->field('NO_ORDER');
		$show->field('KETERANGAN')->unescape()->as(function ($item) {
			return $item;
		});

		
		$show->field('files', 'Files')->unescape()->as(function () {
			$url = env('APP_URL') . config('admin.route.prefix') . '/media?path=%2Fdocs%2F' . $this->id_rso . '%2FOBL%2F' . $this->id_obl . '&fn=selectFile';
			
			return "<a href='$url'><button type='button' class='btn btn-outline-primary'>Lihat Files</button></a>";
		});

		$show->field('followup', 'FollowUp')->unescape()->as(function () {
			// Workflow links
			$url = env('APP_URL') . config('admin.route.prefix') . '/workflow/document/' . $this->getKey();
			$next = "<a href='$url/process'><button type='button' class='btn btn-outline-primary'>Process</button></a>";
			$back = " <a href='$url/return'><button type='button' class='btn btn-outline-danger'>Return</button></a>";
			$followup = '<button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#followupModal">FollowUp</button>';
						
			// $return = (Admin::user()->can($this->dstatus['step_permission']) and !is_null($this->dstatus['step_next'])) ? $next : '';
			// $return .= (Admin::user()->can($this->dstatus['step_permission']) and !is_null($this->dstatus['step_back'])) ? $back : '';
			$return = (Admin::user()->can($this->dstatus['step_permission'])) ? $followup : '';
			
			return $return;
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
        $form = new Form(new document);

		$form->tab('OBL Information', function ($form) {
			$form->text('id_rso', 'ID RSO')->readonly();
			$form->text('id_obl', 'ID OBL')->readonly();
			$form->select('JENIS_SPK', 'Jenis SPK')->options(config('appConst.jenis_spk'));
			$form->radio('tipe_spk', 'Tipe SPK')->options(config('appConst.tipe_spk'))
				->when('amandemen', function (Form $form) {
					$form->text('prev_kl_no', 'Nomor KL Prev');
					$form->date('prev_kl_tanggal', 'Tanggal KL Prev');
					$form->text('prev_kl_judul', 'Judul KL Prev');
				})->when('perpanjangan', function (Form $form) {
					$form->text('prev_kl_no', 'Nomor KL Prev');
					$form->date('prev_kl_tanggal', 'Tanggal KL Prev');
					$form->text('prev_kl_judul', 'Judul KL Prev');
				})->when('amd_perpanjangan', function (Form $form) {
					$form->text('prev_kl_no', 'Nomor KL Prev');
					$form->date('prev_kl_tanggal', 'Tanggal KL Prev');
					$form->text('prev_kl_judul', 'Judul KL Prev');
				});
			$form->switch('is_renewal', 'Is Renewal')
				->help('Otomatis di-set ke TRUE jika Tipe SPK adalah "perpanjangan" atau "amd_perpanjangan"')
				->default(false);
			$form->text('LAYANAN', 'Layanan');
			$form->text('MITRA', 'Mitra');
			$form->number('JANGKA_WAKTU', 'Jangka Waktu')
				->help('Jangka Waktu Kontrak (dalam bulan)');
			$form->dateRange('start_kontrak', 'end_kontrak', 'Start - End Kontrak');
			$form->currency('NILAI_KL')->symbol('Rp ');
			$form->text('NO_KFS_SPK');
			$form->text('NO_P8');
			$form->text('NO_KL_WO_SURAT_PESANAN');
			$form->text('STATUS_SM');
			$form->text('NO_QUOTE');
			$form->text('SID');
			$form->text('NO_ORDER');
			$form->ckeditor('KETERANGAN');
		})->tab('Doc P2-P4', function ($form) {
			$form->divider('Dokumen P2');
			$form->date('p2_tanggal', 'Tanggal Doc P2');
			$form->multipleSelect('p2_calon_mitra', 'Calon Mitra')->options(DB::table('0_mitra')->get()->pluck('nama','inisial'));
			$form->select('p2_dibuat', 'Dibuat oleh')->options(config('appConst.officer_obl'))->default('960235');
			$form->select('p2_diperiksa', 'Diperiksa oleh')->options(config('appConst.mgr_obl'))->default('850057');
			$form->select('p2_disetujui', 'Disetujui oleh')->options(config('appConst.sm_rso'))->default('800075');
			$form->divider('Dokumen P3');
			$form->text('p3_nomor', 'Nomor Doc P3');
			$form->date('p3_tanggal', 'Tanggal Doc P3');
			$form->select('p3_dibuat', 'Oleh')->options(config('appConst.mgr_obl'))->default('850057');
			$form->divider('Dokumen P4');
			$form->date('p4_tanggal', 'Tanggal Doc P4');
			$form->tags('p4_list_peserta', 'Daftar Peserta');
			$form->radio('p4_skema_bisnis', 'Skema Bisnis')->options(config('appConst.skema_bisnis'));
			$form->radio('p4_top', 'Term of Payment')->options(config('appConst.term_of_payment'));
			$form->text('p4_lokasi_instalasi', 'Lokasi Instalasi');
			$form->date('p4_tgl_delivery', 'Tanggal Delivery');
			$form->text('p4_jangka_waktu', 'Jangka Waktu');
			$form->rate('p4_slg', 'SLG');
			$form->date('p4_tgl_sph', 'Tanggal SPH');
		});

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

		// Before saving callback - Auto-set is_renewal berdasarkan tipe_spk
		$form->saving(function (Form $form) {
			// Auto-set is_renewal = true jika tipe_spk adalah 'perpanjangan' atau 'amd_perpanjangan'
			if (in_array($form->tipe_spk, ['perpanjangan', 'amd_perpanjangan'])) {
				$form->is_renewal = true;
			} elseif ($form->tipe_spk == 'psb' || $form->tipe_spk == 'amandemen') {
				// Set is_renewal = false untuk tipe_spk lainnya
				$form->is_renewal = false;
			}
		});

		// Setelah save callback
		$form->saved(function ($form) {
			// Save Edit - Redirect ke halaman sebelumnya
			admin_toastr('Data berhasil di-simpan.', 'success', ['duration' => 5000]);
			return back();
		});

        return $form;
    }

    protected function diskusi(Content $content, $id_rso, $id)
    {
		$user = Admin::user();
		$doc = document::findOrFail($id);
		$project = projects::findOrFail($doc->id_rso);
		
		$data = DB::table('0_diskusi as a')
				->select('a.id', 'a.object_id', 'a.comment', 'a.reply_to', 'a.created_at', 'b.name', 'b.avatar', 'role.name as role')
				->join('admin_users as b', 'a.user_id', '=', 'b.id')
				->leftjoin('admin_roles as role', 'a.user_role', '=', 'role.id')
				->where('a.object_id', '=', $doc->id_obl)
				->orderBy('a.created_at', 'desc')
				->get();
		
		$form = new \OpenAdmin\Admin\Widgets\Form();
		$form->action('/' . config('admin.route.prefix') . '/diskusi/save');
		$form->hidden('user_id')->default($user->id);
		$form->hidden('user_role')->default($user->roles[0]['id']);
		$form->hidden('object_id')->default($doc->id_obl);
		// $form->textarea('comment', 'Komentar')->rows(3);
		$form->ckeditor('comment', 'Komentar')->required();

        return $content
            ->title($project->Customer)
            ->description($project->Nama_Project)
            ->view('projects.diskusi', 
				[
					'diskusi' => $data,
					'user' => $user,
					'form' => $form->render(),
					'host' => env('APP_URL'),
					'def_avatar' => config('admin.default_avatar'),
				]);
    }

	protected function deleteDoc($id_rso, $id) 
	{
		document::destroy($id);
		
		admin_toastr('Data OBL berhasil di-hapus.', 'success', ['duration' => 5000]);

		return redirect(env('APP_URL') . config('admin.route.prefix') . "/document");
	}
	
	protected function editDoc(Content $content, $id_rso, $id) 
	{
		$project = projects::findOrFail($id_rso);
		$doc = document::findOrFail($id);
		
        $form = new \OpenAdmin\Admin\Widgets\Form($doc);
		$form->action('/' . config('admin.route.prefix') . '/projects/documents/save');

		$form->hidden('id');
		$form->hidden('id_rso', 'ID RSO')->readonly();
		$form->text('id_obl', 'ID OBL')->readonly();
		$form->text('JENIS_SPK');
		$form->text('LAYANAN');
		$form->text('MITRA');
		$form->text('JANGKA_WAKTU');
		$form->text('NILAI_KL');
		$form->text('NO_KFS_SPK');
		$form->text('NO_P8');
		$form->text('NO_KL_WO_SURAT_PESANAN');
		$form->text('STATUS_SM');
		$form->text('NO_QUOTE');
		$form->text('SID');
		$form->text('NO_ORDER');
		$form->textarea('KETERANGAN')->rows(5);

		return $content
            ->title($project->Customer)
            ->description('OBL - ' . $project->Nama_Project)
			->body($form);	
	}

	protected function view(Content $content, $id_rso, $id) 
	{
		try {
			$project = projects::findOrFail($id_rso);
		} catch (ModelNotFoundException $except) {
			$project = null;
		}
		
		$doc = document::findOrFail($id);
		
		// Cek & Create Directory Document
		$dirPath = storage_path('admin/docs/' . $id_rso . '/OBL/' . $doc->id_obl);

		//check if the directory exists
		if(!File::isDirectory($dirPath)){
			//make the directory because it doesn't exists
			File::makeDirectory($dirPath, 0755, true, true);
		}

		return $content
            ->title(is_null($project) ? '[Project Dihapus]' : $project->Customer)
            ->description(is_null($project) ? '[Project telah dihapus]' : $project->Nama_Project)
			->body($this->detail($id));				
	}
	
	protected function input(Content $content, $id_rso) {
		$user = Admin::user();
		$project = projects::findOrFail($id_rso);
				
		$form = new \OpenAdmin\Admin\Widgets\Form();
		$form->action('/' . config('admin.route.prefix') . '/projects/documents/create');
		$form->hidden('created_by')->default($user->id);
		$form->hidden('id_rso')->default($id_rso);
		$form->tags('obl_simple', 'OBL Simple');
		$form->tags('obl_multi', 'OBL Multi');

		// Auto Drafting button - creates/gets draft record then redirects to form
		$adminPrefix = config('admin.route.prefix');
		$autoDraftUrl = url("/{$adminPrefix}/rso/{$id_rso}/autodraft/init");
		$autoDraftButton = '<div class="mb-3"><a href="' . $autoDraftUrl . '" class="btn btn-primary"><i class="icon-file-text"></i> Auto Drafting</a></div>';

        return $content
            ->title($project->Customer)
            ->description($project->Nama_Project)
			->body($autoDraftButton . $form->render());
	}

	protected function saveDoc(Request $req)
	{
		// Get Form data (document class)
		$data = $req->all();
		// $data['updated_at'] = now();

		$doc = document::findOrFail($data['id']);
		$doc->fill($data);
		$doc->save(); 
		
		admin_toastr('Documents OBL berhasil di-simpan.', 'success', ['duration' => 5000]);

		// return redirect(env('APP_URL') . config('admin.route.prefix') . "/projects/" . $data['id_rso'] . "/document/" . $data['id']);
		return back();
	}

	protected function createDoc(Request $req)
	{
		$data = $req->all();
		$project = projects::findOrFail($data['id_rso']);

		// Create Document OBL ID
		$count = DB::table('0_document')
					->selectRaw('count(1)+1 as qty')
					->where('id_rso', '=', $data['id_rso'])
					->get()[0];
		$urut = intval($count->qty);

		if(isset($data['obl_simple'][0])) {
			$obl_simple = explode(',', $data['obl_simple'][0]);
			foreach($obl_simple as $item) {
				$doc['id_rso'] = $data['id_rso'];
				$doc['id_obl'] = $data['id_rso'] . '_OBL-' . $urut++;
				$doc['status_doc'] = '130';
				$doc['NAMA_PELANGGAN'] = $project->Customer;
				$doc['LAYANAN'] = $item;
				$doc['created_by'] = Admin::user()->id;
				document::create($doc);
			}
		};
		
		if(isset($data['obl_multi'][0])) {
			$obl_multi = explode(',', $data['obl_multi'][0]);
			foreach($obl_multi as $item) {
				$doc['id_rso'] = $data['id_rso'];
				$doc['id_obl'] = $data['id_rso'] . '_OBL-' . $urut++;
				$doc['status_doc'] = '140';
				$doc['NAMA_PELANGGAN'] = $project->Customer;
				$doc['LAYANAN'] = $item;
				$doc['created_by'] = Admin::user()->id;
				document::create($doc);
			}
		}
				
		// Post Komentar
		$diskusi['user_id'] = Admin::user()->id;
		$diskusi['object_id'] = $data['id_rso'];
		$diskusi['comment'] = "** OBL Documents Started **";	
		diskusi::create($diskusi);

		admin_toastr('Documents OBL berhasil di-trigger.', 'success', ['duration' => 5000]);

		return redirect(env('APP_URL') . config('admin.route.prefix') . "/projects/" . $data['id_rso'] ."/diskusi");
	}

}
