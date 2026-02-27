<?php

namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;
use \App\Models\OwnChanel;
use \App\Models\projects;
use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\Layout\Column;
use OpenAdmin\Admin\Layout\Content;
use OpenAdmin\Admin\Layout\Row;
use OpenAdmin\Admin\Widgets\InfoBox;
use OpenAdmin\Admin\Widgets\Table;
use Illuminate\Support\Facades\DB;

class OwnchanelController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
		$content = new Content();

		$grid = new Grid(new projects());

		// Customize query
		$grid->model()
			->whereIn('status_project', ['110', '240', '242', '244', '299'])
			->where('tipe_projek', '=', 'OwnChannel')
			->orderBy('0_projects.created_at', 'desc');

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
		$grid->column('pstatus.step', 'Status')->style('font-size:small;')->display(function ($item) {
			$colors = config('appConst.statusColors');
			
			$flag = $this->is_win == '1' ? '<span class="badge bg-success">SUDAH WIN</span>' : '<span class="badge bg-secondary">BELUM WIN</span>';
			$verified = $this->is_verified == '1' ? '<span class="badge bg-success">VERIFIED</span>' : '';
			$step = '<span class="badge bg-'. $colors[$this->status_project] .'">' . $item . '</span>';
			
			return $flag . ' ' . $verified . '<br/>' . $step;
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
		$grid->column('created_at', 'Tanggal Create')->style('font-size:small;')->sortable();
		$grid->column('updated_at', 'Tanggal Update')->style('font-size:small;')->sortable();

		// Customize Grid
		$grid->fixColumns(1, -1);
		$grid->disableCreateButton();
		$grid->disableExport();
		$grid->disableRowSelector();
		$grid->disableColumnSelector();
		
		// Grid Action
		$grid->setActionClass(ContextMenuActions::class);
		$grid->actions(function (Grid\Displayers\Actions\Actions $actions) {
			// $actions->disableView();
			// $actions->disableEdit();
			if (!Admin::user()->can('project_manage')) { $actions->disableDelete(); }
			$actions->showLabels(true);
			// $actions->add(new filesAction());
			// $actions->add(new diskusiAction());
		});


		return $content
			->title('List Project')
			->description('Service Operation - OwnChanel')
			->body($grid);
    }

    /**
     * Make a show builder.
     *
     * @param mixed   $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(ExampleModel::findOrFail($id));

        $show->field('id', __('ID'));
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
        $form = new Form(new ExampleModel);

        $form->display('id', __('ID'));
        $form->display('created_at', __('Created At'));
        $form->display('updated_at', __('Updated At'));

        return $form;
    }
}
