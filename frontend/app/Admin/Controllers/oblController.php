<?php

namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;
use App\Http\Controllers\Controller;
use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\Layout\Column;
use OpenAdmin\Admin\Layout\Content;
use OpenAdmin\Admin\Layout\Row;
use Illuminate\Support\Facades\DB;
use \App\Models\obl;

class oblController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'Outbond Logistic';

    protected function listObl(Content $content)
    {
        return $content
            ->title('OBL List')
            ->description('List of Outbond Logistic')
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {

					$grid = new Grid(new obl());
					
					$grid->disableCreateButton();
					$grid->disableExport();
					// $grid->disableRowSelector();
					$grid->disableColumnSelector();
					
					
					$grid->filter(function($filter){
						$filter->disableIdFilter();

						// Add a column filter
						// $filter->column(1/2, function ($filter) {
							// $filter->like('ID_LOP', 'ID LOP');
							// $filter->like('Customer', 'Customer');
							// $filter->like('AM', 'AM');
						// });
						
						$filter->column(1/2, function ($filter) {
							$filter->equal('Witel')->select(['Balikpapan'=>'Balikpapan', 'Kalteng'=>'Kalteng', 'Kalsel'=>'Kalsel', 'Kaltara'=>'Kaltara', 'Kalbar'=>'Kalbar', 'Samarinda'=>'Samarinda']);
							$filter->equal('SEGMEN', 'Segmen')->select(['DES'=>'DES', 'DGS'=>'DGS', 'DBS'=>'DBS']);
						});

						$filter->expand();
					});

					// $grid->column('NO', __('NO'));
					// $grid->column('PROSES', __('PROSES'));
					$grid->column('WITEL', __('WITEL'));
					$grid->column('SEGMEN', __('SEGMEN'));
					$grid->column('NAMA_PELANGGAN', __('NAMA PELANGGAN'));
					$grid->column('LAYANAN', __('LAYANAN'));
					$grid->column('NAMA_VENDOR', __('NAMA VENDOR'));
					// $grid->column('TANGGAL_SUBMIT', __('TANGGAL SUBMIT'));
					// $grid->column('TANGGAL_UPDATE', __('TANGGAL UPDATE'));
					// $grid->column('FOLDER', __('FOLDER'));
					// $grid->column('FOLDER_OBL', __('FOLDER OBL'));
					$grid->column('TAHUN', __('TAHUN'));
					$grid->column('JENIS_SPK', __('JENIS SPK'));
					$grid->column('JANGKA_WAKTU', __('JANGKA WAKTU'));
					// $grid->column('NILAI_KL', __('NILAI KL'));
					// $grid->column('NO_KFS_SPK', __('NO KFS SPK'));
					// $grid->column('NO_P8', __('NO P8'));
					// $grid->column('NO_KL_WO_SURAT_PESANAN', __('NO KL WO SURAT PESANAN'));
					// $grid->column('PIC_MITRA', __('PIC MITRA'));
					$grid->column('STATUS', __('STATUS'));
					// $grid->column('STATUS_SM', __('STATUS SM'));
					// $grid->column('KETERANGAN', __('KETERANGAN'));
					// $grid->column('ORDER_PROSES', __('ORDER PROSES'));
					// $grid->column('ID_RSO', __('ID RSO'));
					// $grid->column('NO_QUOTE', __('NO QUOTE'));
					// $grid->column('SID', __('SID'));
					// $grid->column('NO_ORDER', __('NO ORDER'));
					// $grid->column('UMUR_ORDER', __('UMUR ORDER'));
					// $grid->column('STATUS_OBL_DR', __('STATUS OBL DR'));
					// $grid->column('STATUS_KL_DR', __('STATUS KL DR'));
					// $grid->column('ID_OBL', __('ID OBL'));
					// $grid->column('created_at', __('Created at'));
					// $grid->column('updated_at', __('Updated at'));

                    $column->append($grid);
                });
			});
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
		// dd(Admin::user()->roles[0]['id']);
        $grid = new Grid(new obl());

        $grid->column('NO', __('NO'));
        $grid->column('PROSES', __('PROSES'))->display(function ($item) {
			$komen = DB::table('0_diskusi')
						->selectRaw('admin_users.name, comment, 0_diskusi.created_at')
						->join('admin_users', 'admin_users.id', '=', '0_diskusi.user_id')
						->where('object_id', '=', '24SME11265')
						->orderBy('created_at', 'desc')
						->limit(1)
						->get();
			
			$return = '<small>' . $komen[0]->name . ' | ' . $komen[0]->created_at . '</small><br/>';
			$return .= '<small>' . $komen[0]->comment . '</small>';
			
			return $return;
		});
		$grid->column('TANGGAL_SUBMIT', __('TANGGAL SUBMIT'))->label();
        $grid->column('TANGGAL_UPDATE', __('TANGGAL UPDATE'))->display(function ($item) {
			return 'Input Komentar';
		})->modal('Input Komentar', function($item) {
			$form = new \OpenAdmin\Admin\Widgets\Form();
			$form->action('/' . config('admin.route.prefix') . '/diskusi/save');
			$form->hidden('user_id')->default(Admin::user()->id);
			$form->hidden('object_id')->default($this->FOLDER_OBL);
			$form->textarea('comment', 'Komentar')->rows(3);

			return $form->render();
		});
        // $grid->column('TANGGAL_UPDATE', __('TANGGAL UPDATE'));
        $grid->column('SEGMEN', __('SEGMEN'));
        $grid->column('FOLDER', __('FOLDER'));
        $grid->column('FOLDER_OBL', __('FOLDER OBL'));
        $grid->column('WITEL', __('WITEL'));
        $grid->column('TAHUN', __('TAHUN'));
        $grid->column('JENIS_SPK', __('JENIS SPK'));
        $grid->column('NAMA_PELANGGAN', __('NAMA PELANGGAN'));
        $grid->column('LAYANAN', __('LAYANAN'));
        $grid->column('NAMA_VENDOR', __('NAMA VENDOR'));
        $grid->column('JANGKA_WAKTU', __('JANGKA WAKTU'));
        $grid->column('NILAI_KL', __('NILAI KL'));
        $grid->column('NO_KFS_SPK', __('NO KFS SPK'));
        $grid->column('NO_P8', __('NO P8'));
        $grid->column('NO_KL_WO_SURAT_PESANAN', __('NO KL WO SURAT PESANAN'));
        $grid->column('PIC_MITRA', __('PIC MITRA'));
        $grid->column('STATUS', __('STATUS'));
        $grid->column('STATUS_SM', __('STATUS SM'));
        $grid->column('KETERANGAN', __('KETERANGAN'));
        $grid->column('ORDER_PROSES', __('ORDER PROSES'));
        $grid->column('ID_RSO', __('ID RSO'));
        $grid->column('NO_QUOTE', __('NO QUOTE'));
        $grid->column('SID', __('SID'));
        $grid->column('NO_ORDER', __('NO ORDER'));
        $grid->column('UMUR_ORDER', __('UMUR ORDER'));
        $grid->column('STATUS_OBL_DR', __('STATUS OBL DR'));
        $grid->column('STATUS_KL_DR', __('STATUS KL DR'));
        $grid->column('ID_OBL', __('ID OBL'));
        $grid->column('created_at', __('Created at'));
        $grid->column('updated_at', __('Updated at'));

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
        $show = new Show(obl::findOrFail($id));
		$model = $show->getModel();
		
		$show->panel()
			->title($model->NAMA_PELANGGAN . ' - ' . $model->LAYANAN)
			->style('none')
			->view('projects.panel_project');
		

		// Tab Diskusi
		$user = Admin::user();
		$data = DB::table('0_diskusi as a')
				->join('admin_users as b', 'a.user_id', '=', 'b.id')
				->select('a.id', 'a.object_id', 'a.comment', 'a.reply_to', 'a.created_at', 'b.name', 'b.avatar')
				->where('a.object_id', '=', $model->FOLDER_OBL)
				->orderBy('a.created_at', 'desc')
				->get();

		// Form Diskusi/Komentar
		$form_diskusi = new \OpenAdmin\Admin\Widgets\Form();
		$form_diskusi->action('/' . config('admin.route.prefix') . '/diskusi/save');
		$form_diskusi->hidden('user_id')->default($user->id);
		$form_diskusi->hidden('object_id')->default($model->FOLDER_OBL);
		$form_diskusi->textarea('comment', 'Komentar')->rows(3);
		
		// Form FollowUp
		$form_process = new \OpenAdmin\Admin\Widgets\Form();
		$form_process->action('/' . config('admin.route.prefix') . '/workflow/projects/followup');
		$form_process->title($model->NAMA_PELANGGAN . ' - ' . $model->LAYANAN);
		$form_process->radio('followup')->options(['lanjut' => 'Process Lanjut', 'return' => 'Return', 'drop' => 'Drop Project'])->default('lanjut');
		$form_process->hidden('user_id')->default($user->id);
		$form_process->hidden('object_id')->default($model->FOLDER_OBL);
		$form_process->textarea('comment', 'Komentar')->rows(3);

		// Add variables to Data View
		$show->panel()->data['diskusi'] = $data;
		$show->panel()->data['user'] = $user;
		$show->panel()->data['form'] = $form_diskusi->render();
		$show->panel()->data['host'] = env('APP_URL');
		$show->panel()->data['def_avatar'] = config('admin.default_avatar');
		$show->panel()->data['form_process'] = $form_process->render();
		
        $show->field('NO', __('NO'));
        $show->field('PROSES', __('PROSES'));
        $show->field('TANGGAL_SUBMIT', __('TANGGAL SUBMIT'));
        $show->field('TANGGAL_UPDATE', __('TANGGAL UPDATE'));
        $show->field('SEGMEN', __('SEGMEN'));
        $show->field('FOLDER', __('FOLDER'))->unescape()->as(function ($item) {
			// $return = "<button type='button' class='btn btn-outline-primary' data-toggle='modal' data-target='#process'>Process $item</button>";
			$return = '<button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#followupModal">FollowUp</button>';
			
			return $return;
		});
        $show->field('FOLDER_OBL', __('FOLDER OBL'));
        $show->field('WITEL', __('WITEL'));
        $show->field('TAHUN', __('TAHUN'));
        $show->field('JENIS_SPK', __('JENIS SPK'));
        $show->field('NAMA_PELANGGAN', __('NAMA PELANGGAN'));
        $show->field('LAYANAN', __('LAYANAN'));
        $show->field('NAMA_VENDOR', __('NAMA VENDOR'));
        $show->field('JANGKA_WAKTU', __('JANGKA WAKTU'));
        $show->field('NILAI_KL', __('NILAI KL'));
        $show->field('NO_KFS_SPK', __('NO KFS SPK'));
        $show->field('NO_P8', __('NO P8'));
        $show->field('NO_KL_WO_SURAT_PESANAN', __('NO KL WO SURAT PESANAN'));
        $show->field('PIC_MITRA', __('PIC MITRA'));
        $show->field('STATUS', __('STATUS'));
        $show->field('STATUS_SM', __('STATUS SM'));
        $show->field('KETERANGAN', __('KETERANGAN'));
        $show->field('ORDER_PROSES', __('ORDER PROSES'));
        $show->field('ID_RSO', __('ID RSO'));
        $show->field('NO_QUOTE', __('NO QUOTE'));
        $show->field('SID', __('SID'));
        $show->field('NO_ORDER', __('NO ORDER'));
        $show->field('UMUR_ORDER', __('UMUR ORDER'));
        $show->field('STATUS_OBL_DR', __('STATUS OBL DR'));
        $show->field('STATUS_KL_DR', __('STATUS KL DR'));
        $show->field('ID_OBL', __('ID OBL'));
        $show->field('created_at', __('Created at'));
        $show->field('updated_at', __('Updated at'));

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new obl());

        $form->number('NO', __('NO'));
        $form->text('PROSES', __('PROSES'));
        $form->text('TANGGAL_SUBMIT', __('TANGGAL SUBMIT'));
        $form->text('TANGGAL_UPDATE', __('TANGGAL UPDATE'));
        $form->text('SEGMEN', __('SEGMEN'));
        $form->text('FOLDER', __('FOLDER'));
        $form->text('FOLDER_OBL', __('FOLDER OBL'));
        $form->text('WITEL', __('WITEL'));
        $form->number('TAHUN', __('TAHUN'));
        $form->text('JENIS_SPK', __('JENIS SPK'));
        $form->text('NAMA_PELANGGAN', __('NAMA PELANGGAN'));
        $form->text('LAYANAN', __('LAYANAN'));
        $form->text('NAMA_VENDOR', __('NAMA VENDOR'));
        $form->switch('JANGKA_WAKTU', __('JANGKA WAKTU'));
        $form->number('NILAI_KL', __('NILAI KL'));
        $form->text('NO_KFS_SPK', __('NO KFS SPK'));
        $form->text('NO_P8', __('NO P8'));
        $form->text('NO_KL_WO_SURAT_PESANAN', __('NO KL WO SURAT PESANAN'));
        $form->text('PIC_MITRA', __('PIC MITRA'));
        $form->text('STATUS', __('STATUS'));
        $form->text('STATUS_SM', __('STATUS SM'));
        $form->text('KETERANGAN', __('KETERANGAN'));
        $form->text('ORDER_PROSES', __('ORDER PROSES'));
        $form->text('ID_RSO', __('ID RSO'));
        $form->text('NO_QUOTE', __('NO QUOTE'));
        $form->text('SID', __('SID'));
        $form->text('NO_ORDER', __('NO ORDER'));
        $form->text('UMUR_ORDER', __('UMUR ORDER'));
        $form->text('STATUS_OBL_DR', __('STATUS OBL DR'));
        $form->text('STATUS_KL_DR', __('STATUS KL DR'));
        $form->text('ID_OBL', __('ID OBL'));

        return $form;
    }
}
