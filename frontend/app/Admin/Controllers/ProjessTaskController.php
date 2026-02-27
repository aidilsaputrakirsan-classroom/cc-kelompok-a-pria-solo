<?php

namespace App\Admin\Controllers;

use App\Models\projess_task;
use App\Models\projects;
use App\Models\document;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;
use OpenAdmin\Admin\Layout\Content;
use OpenAdmin\Admin\Admin;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Model;

class ProjessTaskController extends AdminController
{
    /**
     * Judul untuk halaman CRUD ini.
     *
     * @var string
     */
    protected $title = 'Projess Task';

    /**
     * Membuat tampilan Grid (daftar data).
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new projess_task());

        // Menambahkan kolom-kolom utama untuk ditampilkan di grid
        $grid->column('id', __('ID'))->sortable();
        $grid->column('task_order', __('Task Order'))->sortable();
        $grid->column('task_parent', __('Task Parent'))->sortable();
        $grid->column('task_name', __('Task Name'))->limit(50)->display(function($item) {
            $url = url(URL::current() . '/' . $this->getKey());
            return "<a href='{$url}'>{$item}</a>";
        });
        $grid->column('task_description', __('Task Description'))->limit(50);
        $grid->column('task_roles', __('Task Roles'));
        $grid->column('is_optional', __('Is Optional'))->display(function ($value) {
            return $value ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>';
        })->sortable();
        $grid->column('created_at', __('Created At'))->sortable();
        $grid->column('updated_at', __('Updated At'))->sortable();

        // Menambahkan filter untuk pencarian
        $grid->filter(function($filter){
            // Menonaktifkan filter ID default
            $filter->disableIdFilter();

            // Filter berdasarkan field-field utama
            $filter->like('task_name', 'Task Name');
            $filter->like('task_description', 'Task Description');
            $filter->like('task_roles', 'Task Roles');
            $filter->equal('task_parent', 'Task Parent');
            $filter->equal('is_optional', 'Is Optional')->radio([
                '0' => 'No',
                '1' => 'Yes',
            ]);
        });

        // Menambahkan tombol Tree View di header grid
        $grid->header(function ($query) {
            $treeViewUrl = url(config('admin.route.prefix') . '/projess-tasks/tree-view');
            return '<div class="col-auto me-auto">
                <a href="' . $treeViewUrl . '" class="btn btn-info">
                    <i class="icon-tree"></i> Tree View
                </a>
            </div>';
        });

        return $grid;
    }

    /**
     * Membuat tampilan Detail (melihat satu data).
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(projess_task::findOrFail($id));

        // Menampilkan semua field dari tabel
        $show->field('id', __('ID'));
        $show->field('task_order', __('Task Order'));
        $show->field('task_parent', __('Task Parent'));
        $show->field('task_name', __('Task Name'));
        $show->field('task_description', __('Task Description'));
        $show->field('task_roles', __('Task Roles'));
        $show->field('is_optional', __('Is Optional'))->unescape()->as(function ($value) {
            return $value ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>';
        });
        $show->field('created_at', __('Created At'));
        $show->field('updated_at', __('Updated At'));

        return $show;
    }

    /**
     * Membuat Form untuk tambah dan edit data.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new projess_task());

        // Membuat field input untuk setiap kolom di tabel
        $form->number('task_order', __('Task Order'));
        $form->number('task_parent', __('Task Parent'));
        $form->text('task_name', __('Task Name'))->required();
        $form->textarea('task_description', __('Task Description'));
        $form->text('task_roles', __('Task Roles'));
        $form->switch('is_optional', __('Is Optional'));

        return $form;
    }

    /**
     * Menampilkan tasks dalam format tree berdasarkan task_parent dan task_order
     *
     * @param Content $content
     * @return Content
     */
    public function treeView(Content $content)
    {
        // Ambil semua tasks dan urutkan berdasarkan task_order
        $allTasks = projess_task::orderBy('task_order', 'asc')->get();

        // Pisahkan root tasks (task_parent = 0 atau null) dan urutkan berdasarkan task_order
        $rootTasks = $allTasks->filter(function ($task) {
            return $task->task_parent == 0 || $task->task_parent === null;
        })->sortBy('task_order');

        return $content
            ->title('Task Tree View')
            ->description('Visualisasi hierarki task berdasarkan relasi parent-child')
            ->body(view('admin.projess-tasks.tree-view', [
                'rootTasks' => $rootTasks,
                'allTasks' => $allTasks,
            ]));
    }

    /**
     * Fungsi mapping untuk mengisi field task di model berdasarkan kriteria tertentu
     * dan mengisi field sub_task dengan task dengan task_order paling kecil dari task_parent yang ditentukan
     * 
     * @param Model $model Instance dari model yang akan di-mapping
     * @return bool|Model Mengembalikan model yang sudah di-update atau false jika gagal
     */
    public static function mapTask(Model $model, $save = true)
    {
        // Validasi bahwa model memiliki field 'task'
        if (!in_array('task', $model->getFillable()) && !$model->isFillable('task')) {
            throw new \InvalidArgumentException("Model " . get_class($model) . " tidak memiliki field 'task' yang dapat diisi.");
        }

        // Validasi bahwa model memiliki field 'sub_task'
        if (!in_array('sub_task', $model->getFillable()) && !$model->isFillable('sub_task')) {
            throw new \InvalidArgumentException("Model " . get_class($model) . " tidak memiliki field 'sub_task' yang dapat diisi.");
        }

        $taskId = null;

        // Mapping berdasarkan class model
        if ($model instanceof projects) {
            // Mapping untuk model projects berdasarkan tipe_projek
            switch ($model->tipe_projek) {
                case 'GTMA':
                    $taskId = 1; // Pre Sales OBL
                    break;
                case 'OwnChannel':
                    $taskId = 52; // Pre Sales IBL PSB
                    break;
                default:
                    // Jika tidak ada mapping, return false atau null
                    return false;
            }
        } elseif ($model instanceof document) {
            // Mapping untuk model document berdasarkan is_renewal
            if ($model->is_renewal === false || $model->is_renewal === 0 || $model->is_renewal === '0') {
                $taskId = 2; // Proses OBL PSB
            } elseif ($model->is_renewal === true || $model->is_renewal === 1 || $model->is_renewal === '1') {
                $taskId = 35; // Proses OBL Renewal
            } else {
                // Jika is_renewal null atau tidak terdefinisi, return false
                return false;
            }
        } else {
            // Untuk model lain, bisa ditambahkan mapping di sini
            // Contoh:
            // if ($model instanceof OtherModel) {
            //     $taskId = ...;
            // }
            return false;
        }

        // Jika taskId ditemukan, update field task
        if ($taskId !== null) {
            // Validasi bahwa task dengan ID tersebut memiliki task_parent = 0
            $task = projess_task::where('id', $taskId)
                ->where(function($query) {
                    $query->where('task_parent', 0)
                        ->orWhereNull('task_parent');
                })
                ->first();

            if (!$task) {
                throw new \InvalidArgumentException("Task dengan ID {$taskId} tidak ditemukan atau bukan task utama (task_parent harus 0 atau null).");
            }

            // Update field task
            $model->task = $taskId;

            // Cari sub-task dengan task_order paling kecil dari task_parent yang ditentukan
            $subTask = projess_task::where('task_parent', $taskId)
                ->orderBy('task_order', 'asc')
                ->first();

            // Update field sub_task jika sub-task ditemukan
            if ($subTask) {
                $model->sub_task = $subTask->id;
            } else {
                // Jika tidak ada sub-task, set sub_task menjadi null
                $model->sub_task = null;
            }
            
            // Simpan jika model sudah ada di database dan $save = true, atau hanya set attribute jika belum
            if ($model->exists && $save) {
                $model->save();
            }

            return $model;
        }

        return false;
    }

    /**
     * Fungsi mapping untuk multiple models (batch mapping)
     * 
     * @param \Illuminate\Database\Eloquent\Collection|array $models Collection atau array dari model instances
     * @return array Array berisi hasil mapping ['success' => [], 'failed' => []]
     */
    public static function mapTaskBatch($models)
    {
        $results = [
            'success' => [],
            'failed' => []
        ];

        foreach ($models as $model) {
            try {
                $mapped = self::mapTask($model);
                if ($mapped) {
                    $results['success'][] = $model;
                } else {
                    $results['failed'][] = [
                        'model' => $model,
                        'reason' => 'Tidak ada mapping yang sesuai'
                    ];
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'model' => $model,
                    'reason' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}
