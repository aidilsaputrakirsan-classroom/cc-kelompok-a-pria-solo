<?php

namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Layout\Content;
use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\Form;
use App\Models\projects;
use App\Models\hasilRapat;
use App\Models\projess_log;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HasilRapatController extends AdminController
{
    /**
     * Show input form for hasil rapat linked to a project.
     */
    public function create($id)
    {
        try {
            $project = projects::findOrFail($id);
        } catch (\Exception $e) {
            admin_toastr('Project tidak ditemukan: ' . $e->getMessage(), 'error');
            return redirect(admin_url('pre-sales/list'));
        }

        return Admin::content(function (Content $content) use ($project, $id) {
            $content->title('Input Hasil Rapat')
                    ->description($project->ID_RSO . ' - ' . $project->Customer);

            $backMenu = '<div style="margin-bottom: 20px;">
                <div class="btn-group" role="group">
                    <a href="' . url(config('admin.route.prefix') . '/pre-sales') . '" class="btn btn-default">
                        <i class="fa fa-bar-chart"></i> Summary
                    </a>
                    <a href="' . url(config('admin.route.prefix') . '/pre-sales/list') . '" class="btn btn-default">
                        <i class="fa fa-list"></i> List Projects
                    </a>
                    <a href="' . url(config('admin.route.prefix') . '/pre-sales/detail/' . $id) . '" class="btn btn-default">
                        <i class="fa fa-file-text"></i> Detail Project
                    </a>
                    <button type="button" class="btn btn-info" disabled>
                        <i class="fa fa-file-text-o"></i> Hasil Rapat
                    </button>
                </div>
            </div>';

            $content->body($backMenu);

            $form = new Form(new hasilRapat());
            $form->hidden('object')->default(projects::class);
            $form->hidden('object_id')->default($project->id);
            $form->hidden('id_rso')->default($project->ID_RSO);
            $form->hidden('created_by')->default(Admin::user()->id);

            $form->select('tipe', 'Tipe Rapat')
                 ->options(hasilRapat::tipeOptions())
                 ->required()
                 ->rules('required');

            $form->ckeditor('note', 'Catatan / Hasil Rapat')
                 ->required()
                 ->rules('required');

            $form->tools(function (Form\Tools $tools) {
                $tools->disableView();
            });

            $form->saved(function (Form $form) use ($id, $project) {
                $hasilRapat = $form->model();
                projess_log::create([
                    'task_id' => $project->task,
                    'trackable_type' => 'App\Models\projects',
                    'trackable_id' => $project->ID_RSO,
                    'model_type' => hasilRapat::class,
                    'model_id' => $hasilRapat->id,
                    'id_rso' => $project->ID_RSO,
                    'action_type' => 'hasil_rapat',
                    'notes' => $hasilRapat->note,
                    'user_id' => Admin::user()->id,
                ]);

                admin_toastr('Hasil Rapat berhasil disimpan', 'success');
                return redirect(admin_url('pre-sales/detail/' . $id));
            });

            $content->body($form);
        });
    }

    /**
     * Update existing hasil rapat entry.
     */
    public function update(Request $request, $id)
    {
        $rapat = hasilRapat::findOrFail($id);

        $validated = $request->validate([
            'tipe' => 'required|string',
            'note' => 'required|string',
            'project_id' => 'required|integer',
            'id_rso' => 'required|string',
        ]);

        $rapat->update([
            'tipe' => $validated['tipe'],
            'note' => $validated['note'],
        ]);

        $project = projects::find($validated['project_id']);

        projess_log::create([
            'task_id' => optional($project)->task,
            'trackable_type' => 'App\Models\projects',
            'trackable_id' => $validated['id_rso'],
            'model_type' => hasilRapat::class,
            'model_id' => $rapat->id,
            'id_rso' => $validated['id_rso'],
            'action_type' => 'update_hasil_rapat',
            'notes' => 'Catatan diperbarui: ' . Str::limit($rapat->note, 250),
            'user_id' => Admin::user()->id,
        ]);

        admin_toastr('Hasil Rapat diperbarui', 'success');
        return redirect(admin_url('pre-sales/detail/' . $validated['project_id']));
    }
}
