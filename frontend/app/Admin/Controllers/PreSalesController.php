<?php

namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Layout\Content;
use OpenAdmin\Admin\Layout\Row;
use OpenAdmin\Admin\Layout\Column;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Grid\Displayers\Actions\ContextMenuActions;
use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\Widgets\Box;
use OpenAdmin\Admin\Widgets\Tab;
use OpenAdmin\Admin\Widgets\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\projects;
use App\Models\document;
use App\Models\diskusi;
use App\Models\files;
use App\Models\projess_log;
use App\Models\projess_task;
use App\Models\hasilRapat;
use App\Admin\Actions\filesAction;
use App\Admin\Actions\diskusiAction;
use App\Admin\Controllers\ProjessTaskController;
use Illuminate\Http\Request;

class PreSalesController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'DASHBOARD';

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        // Get summary data
        $data = $this->getSummaryData();
        $filterHtml = $this->renderFilter();

        // Create navigation menu
        $menuHtml = $this->renderMenu();

        return $content
            ->title($this->title())
            ->description('Summary Projects berdasarkan Task dan Sub Task')
            ->body($menuHtml)
            ->body(view('pre-sales.index', [
                'data' => $data,
                'filterHtml' => $filterHtml
            ]));
    }

    /**
     * Get summary data from database
     *
     * @return array
     */
    protected function getSummaryData()
    {
        $query = DB::table('0_projects as p')
            ->leftJoin('1_tasks as t', 'p.task', '=', 't.id')
            ->leftJoin('1_tasks as st', 'p.sub_task', '=', 'st.id')
            ->select(
                'p.task',
                't.task_name as task_name',
                'p.sub_task',
                'st.task_name as sub_task_name',
                DB::raw('COUNT(p.ID_RSO) as jumlah_project'),
                DB::raw('SUM(CASE WHEN p.is_win = 1 THEN 1 ELSE 0 END) as jumlah_win'),
                DB::raw('SUM(CASE WHEN p.is_verified = 1 THEN 1 ELSE 0 END) as jumlah_verified'),
                DB::raw('ROUND(SUM(p.Nilai_Project_Total) / 1000000, 2) as total_nilai_juta')
            )
            ->whereNotNull('p.task');

        // Apply filters if any
        if (request('Witel')) {
            $query->where('p.Witel', request('Witel'));
        }
        if (request('segmen')) {
            $query->where('p.segmen', request('segmen'));
        }
        if (request('is_renewal_only') !== null && request('is_renewal_only') !== '') {
            $is_renewal = request('is_renewal_only') == '1' ? 1 : 0;
            $query->where('p.is_renewal_only', $is_renewal);
        }

        $results = $query
            ->groupBy('p.task', 't.task_name', 'p.sub_task', 'st.task_name')
            ->orderBy('t.task_order')
            ->orderBy('st.task_order')
            ->get();

        // Add URL links to each row
        $baseUrl = url(config('admin.route.prefix') . '/pre-sales/list');
        
        return $results->map(function($item) use ($baseUrl) {
            $item = (array) $item;
            
            // Add task URL
            if (!empty($item['task'])) {
                $item['task_url'] = $baseUrl . '?task=' . $item['task'];
            }
            
            // Add sub_task URL
            if (!empty($item['sub_task'])) {
                $item['sub_task_url'] = $baseUrl . '?sub_task=' . $item['sub_task'];
            }
            
            return $item;
        })->toArray();
    }


    /**
     * Render navigation menu
     *
     * @return string
     */
    protected function getCreateProjectUrl()
    {
        return url(config('admin.route.prefix') . '/projects/create');
    }

    protected function getProjectEditUrl($projectId)
    {
        return url(config('admin.route.prefix') . '/projects/' . $projectId . '/edit');
    }

    protected function renderMenu()
    {
        $currentRoute = request()->path();
        $isIndex = strpos($currentRoute, 'pre-sales/list') === false;
        $createUrl = $this->getCreateProjectUrl();
        
        $html = '<div style="margin-bottom: 20px;">
            <div class="btn-group" role="group">
                <a href="' . url(config('admin.route.prefix') . '/pre-sales') . '" class="btn btn-' . ($isIndex ? 'primary' : 'default') . '">
                    <i class="fa fa-bar-chart"></i> Summary
                </a>
                <a href="' . url(config('admin.route.prefix') . '/pre-sales/list') . '" class="btn btn-' . (!$isIndex ? 'primary' : 'default') . '">
                    <i class="fa fa-list"></i> List Projects
                </a>
                <a href="' . $createUrl . '" class="btn btn-success">
                    <i class="fa fa-plus"></i> Create Project
                </a>
            </div>
        </div>';
        
        return $html;
    }

    /**
     * Render menu with Process Task button (for detail page)
     */
    protected function renderMenuWithProcessTask($processButtonHtml, $projectId)
    {
        $html = '<div style="margin-bottom: 20px; display: flex; gap: 15px; align-items: center;">
            <div class="btn-group" role="group">
                <a href="' . url(config('admin.route.prefix') . '/pre-sales') . '" class="btn btn-default">
                    <i class="fa fa-bar-chart"></i> Summary
                </a>
                <a href="' . url(config('admin.route.prefix') . '/pre-sales/list') . '" class="btn btn-default">
                    <i class="fa fa-list"></i> List Projects
                </a>
                <button type="button" class="btn btn-primary" disabled>
                    <i class="fa fa-file-text"></i> Detail Project
                </button>
                <a href="' . $this->getProjectEditUrl($projectId) . '" class="btn btn-warning">
                    <i class="fa fa-pencil"></i> Edit Project
                </a>
                <a href="' . $this->getCreateProjectUrl() . '" class="btn btn-success">
                    <i class="fa fa-plus"></i> Create Project
                </a>
            </div>
            <div style="border-left: 2px solid #ddd; height: 34px;"></div>
            <div class="btn-group" role="group" style="align-items: center;">
                ' . $processButtonHtml . '
                <span style="width: 2px; background-color: #ddd; margin: 0 10px; display: inline-block; height: 28px;"></span>
                <a href="' . url(config('admin.route.prefix') . '/pre-sales/hasil-rapat/' . $projectId) . '" class="btn btn-info">
                    <i class="fa fa-file-text-o"></i> Hasil Rapat
                </a>
            </div>
        </div>';
        
        return $html;
    }

    /**
     * Render filter form
     *
     * @return string
     */
    protected function renderFilter()
    {
        $witel = request('Witel', '');
        $segmen = request('segmen', '');
        $is_renewal_only = request('is_renewal_only', '');
        
        $witels = config('appConst.witels', []);
        $segmens = config('appConst.segmens', []);
        
        $html = '<div class="box box-primary" style="margin-bottom: 20px;">
            <div class="box-header with-border">
                <h3 class="box-title">Filter</h3>
            </div>
            <div class="box-body">
                <form method="GET" action="' . url(config('admin.route.prefix') . '/pre-sales') . '">
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Witel</label>
                                <select name="Witel" class="form-control">
                                    <option value="">Semua</option>';
        foreach ($witels as $key => $value) {
            $selected = $witel == $key ? 'selected' : '';
            $html .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
        }
        $html .= '</select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Segmen</label>
                                <select name="segmen" class="form-control">
                                    <option value="">Semua</option>';
        foreach ($segmens as $key => $value) {
            $selected = $segmen == $key ? 'selected' : '';
            $html .= '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
        }
        $html .= '</select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Is Renewal Only</label>
                                <select name="is_renewal_only" class="form-control">
                                    <option value="">Semua</option>
                                    <option value="1" ' . ($is_renewal_only == '1' ? 'selected' : '') . '>Ya</option>
                                    <option value="0" ' . ($is_renewal_only == '0' ? 'selected' : '') . '>Tidak</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label><br>
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="' . url(config('admin.route.prefix') . '/pre-sales') . '" class="btn btn-default">Reset</a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>';
        
        return $html;
    }

    /**
     * List interface - Display projects list with Grid
     *
     * @param Content $content
     * @return Content
     */
    public function list(Content $content)
    {
        // Create navigation menu
        $menuHtml = $this->renderMenu();

        return $content
            ->title('Pre Sales Projects List')
            ->description('Daftar Projects Pre Sales')
            ->body($menuHtml)
            ->body($this->grid());
    }

    /**
     * Detail interface - Display project detail with tabs
     *
     * @param Content $content
     * @param string $id
     * @return Content
     */
    public function detail(Content $content, $id)
    {
        //\Log::info('PreSalesController detail() called with ID: ' . $id);
        
        try {
            $project = projects::with(['task', 'subTask', 'pstatus', 'doc', 'diskusi', 'files', 'logs'])
                ->findOrFail($id);
            \Log::info('Project found: ' . $project->ID_RSO);
        } catch (\Exception $e) {
            \Log::error('Project not found: ' . $e->getMessage());
            admin_toastr('Project tidak ditemukan: ' . $e->getMessage(), 'error');
            return redirect(admin_url('pre-sales/list'));
        }

        if (is_null($project->task)) {
            try {
                ProjessTaskController::mapTask($project, true);
                $project->refresh();
            } catch (\Exception $mapException) {
                \Log::warning('MapTask warning while rendering detail: ' . $mapException->getMessage(), [
                    'ID_RSO' => $project->ID_RSO,
                ]);
            }
        }

        // Get available tasks for process
        $availableTasks = $this->getAvailableTasks($project);
        //\Log::info('Available tasks:', [
        //    'next' => $availableTasks['next']->count(),
        //    'previous' => $availableTasks['previous']->count(),
        //    'current' => $availableTasks['current'] ? $availableTasks['current']->task_name : null
        //]);
        
        $processButtonHtml = $this->renderProcessTaskButtonForMenu($project, $availableTasks);
        $processModal = $this->renderProcessTaskModal($project, $availableTasks);
        
        //\Log::info('Process button HTML length: ' . strlen($processButtonHtml));

        // Create navigation menu with Process Task button
        $menuHtml = $this->renderMenuWithProcessTask($processButtonHtml, $id);
        
        // Add JavaScript using Admin::script() - proper way in OpenAdmin
        Admin::script('
        window.openProcessTaskModal = function() {
            console.log("Button clicked, attempting to open modal");
            
            try {
                var modal = document.getElementById("processTaskModal");
                if (!modal) {
                    console.error("Modal element not found!");
                    return;
                }
                
                console.log("Modal element found");
                
                // Remove fade class first to avoid animation issues
                modal.classList.remove("fade");
                
                // Set styles explicitly
                modal.style.display = "block";
                modal.style.paddingRight = "17px";
                modal.style.zIndex = "9999";
                modal.classList.add("in");
                modal.setAttribute("aria-hidden", "false");
                
                // Add backdrop
                var existingBackdrop = document.getElementById("processTaskModalBackdrop");
                if (existingBackdrop) {
                    existingBackdrop.remove();
                }
                
                var backdrop = document.createElement("div");
                backdrop.className = "modal-backdrop in";
                backdrop.id = "processTaskModalBackdrop";
                backdrop.style.zIndex = "9998";
                backdrop.onclick = function(e) {
                    e.preventDefault();
                    closeProcessTaskModal();
                };
                document.body.appendChild(backdrop);
                document.body.classList.add("modal-open");
                
                console.log("Modal opened successfully");
            } catch(e) {
                console.error("Error opening modal:", e);
            }
        };
        
        window.closeProcessTaskModal = function() {
            try {
                var modal = document.getElementById("processTaskModal");
                if (modal) {
                    modal.classList.remove("in");
                    modal.style.display = "none";
                    modal.setAttribute("aria-hidden", "true");
                }
                
                var backdrop = document.getElementById("processTaskModalBackdrop");
                if (backdrop) {
                    backdrop.remove();
                }
                
                document.body.classList.remove("modal-open");
                document.body.style.paddingRight = "";
                
                console.log("Modal closed successfully");
            } catch(e) {
                console.error("Error closing modal:", e);
            }
        };
        
        // ESC key handler
        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape" || e.keyCode === 27) {
                var modal = document.getElementById("processTaskModal");
                if (modal && (modal.style.display === "block" || modal.classList.contains("in"))) {
                    closeProcessTaskModal();
                }
            }
        });
        
        // Initialize when DOM is ready
        console.log("Process Task Modal script loaded");
        ');
        
        // CSS Styles
        $scriptsAndStyles = '
        <style>
        /* Modern Modal Styling */
        #processTaskModal {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: 9999;
            overflow-x: hidden;
            overflow-y: auto;
            outline: 0;
        }
        
        #processTaskModal.in {
            display: block !important;
        }
        
        #processTaskModal .modal-dialog {
            margin: 50px auto;
            max-width: 700px;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        #processTaskModal .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        #processTaskModal .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 25px;
            border-bottom: none;
            position: relative;
        }
        
        #processTaskModal .modal-header .close {
            color: white;
            opacity: 0.9;
            font-size: 20px;
            font-weight: 300;
            text-shadow: none;
            position: absolute;
            right: 20px;
            top: 18px;
            line-height: 1;
            padding: 0;
            width: 20px;
            height: 20px;
        }
        
        #processTaskModal .modal-header .close:hover {
            opacity: 1;
        }
        
        #processTaskModal .modal-title {
            font-size: 22px;
            font-weight: 600;
            margin: 0;
            padding-right: 30px;
        }
        
        #processTaskModal .modal-title i {
            margin-right: 10px;
        }
        
        #processTaskModal .modal-body {
            padding: 30px;
            background-color: #f8f9fa;
        }
        
        #processTaskModal .form-group {
            margin-bottom: 25px;
        }
        
        #processTaskModal .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
            display: block;
        }
        
        #processTaskModal .form-group label span {
            color: #e74c3c;
        }
        
        #processTaskModal .form-control {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: white;
        }
        
        #processTaskModal .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }
        
        #processTaskModal .form-control[readonly] {
            background-color: #f5f5f5;
            color: #666;
            cursor: not-allowed;
        }
        
        #processTaskModal select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 12 12\'%3E%3Cpath fill=\'%23333\' d=\'M10.293 3.293L6 7.586 1.707 3.293A1 1 0 00.293 4.707l5 5a1 1 0 001.414 0l5-5a1 1 0 10-1.414-1.414z\'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }
        
        #processTaskModal select.form-control optgroup {
            font-weight: 600;
            color: #667eea;
            padding: 10px;
        }
        
        #processTaskModal select.form-control option {
            padding: 10px;
        }
        
        #processTaskModal textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        #processTaskModal .form-group small {
            display: block;
            margin-top: 8px;
            color: #999;
            font-size: 12px;
            line-height: 1.5;
        }
        
        #processTaskModal .form-group small i {
            margin-right: 5px;
        }
        
        /* Info Box Styling */
        #processTaskModal .info-box {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.08) 0%, rgba(118, 75, 162, 0.08) 100%);
            border-left: 4px solid #667eea;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        #processTaskModal .info-box-content {
            display: flex;
            align-items: center;
        }
        
        #processTaskModal .info-box-icon {
            font-size: 24px;
            color: #667eea;
            margin-right: 12px;
        }
        
        #processTaskModal .info-box-title {
            color: #333;
            font-size: 14px;
            font-weight: 600;
        }
        
        #processTaskModal .info-box-value {
            color: #667eea;
            font-size: 16px;
            font-weight: 600;
            margin-top: 3px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            #processTaskModal .modal-dialog {
                margin: 20px;
                max-width: calc(100% - 40px);
            }
            
            #processTaskModal .modal-body {
                padding: 20px;
            }
            
            #processTaskModal .modal-footer {
                padding: 15px 20px;
                flex-direction: column;
            }
            
            #processTaskModal .modal-footer .btn {
                width: 100%;
                margin-bottom: 10px;
            }
            
            #processTaskModal .modal-footer .btn:last-child {
                margin-bottom: 0;
            }
        }
        
        #processTaskModal .modal-footer {
            padding: 20px 30px;
            background-color: white;
            border-top: 1px solid #e0e0e0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        #processTaskModal .modal-footer .btn {
            padding: 10px 25px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: none;
        }
        
        #processTaskModal .modal-footer .btn-default {
            background-color: #e0e0e0;
            color: #666;
        }
        
        #processTaskModal .modal-footer .btn-default:hover {
            background-color: #d0d0d0;
            transform: translateY(-1px);
        }
        
        #processTaskModal .modal-footer .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        #processTaskModal .modal-footer .btn-primary:hover {
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
            transform: translateY(-2px);
        }
        
        #processTaskModal .modal-footer .btn-primary:active {
            transform: translateY(0);
        }
        
        #processTaskModal .modal-footer .btn i {
            margin-right: 8px;
        }
        
        #processTaskModalBackdrop {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            z-index: 9998;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(3px);
            animation: fadeIn 0.3s ease-out;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        .modal-open {
            overflow: hidden;
        }
        </style>';

        // Create tabs
        $tab = new Tab();

        // Tab 1: Informasi Umum
        $tab->add('Informasi', $this->detailInfoUmum($project));

        // Tab 2: Status Timeline
        $tab->add('Status', $this->detailStatusTimeline($project));

        // Tab 3: Financial
        $tab->add('Financial', $this->detailFinancial($project));

        // Tab 4: OBL
        $tab->add('OBL', $this->detailDokumen($project));

        // Tab 5: Rapat
        $tab->add('Rapat', $this->detailHasilRapat($project));

        // Tab 6: Diskusi
        $tab->add('Diskusi', $this->detailDiskusi($project));

        // Tab 7: Files
        $tab->add('Files', $this->detailFiles($project));

        // Tab 8: History
        $tab->add('History', $this->detailHistory($project));

        \Log::info('Rendering content for project: ' . $project->ID_RSO);
        
        return $content
            ->title('Detail Project')
            ->description($project->ID_RSO . ' - ' . $project->Customer)
            ->body($scriptsAndStyles)
            ->body($menuHtml)
            ->body($tab->render())
            ->body($processModal);
    }

    /**
     * Get available tasks (next/previous) based on current sub_task
     */
    protected function getAvailableTasks($project)
    {
        if (!$project->sub_task) {
            \Log::warning('Project has no sub_task', [
                'ID_RSO' => $project->ID_RSO,
                'task' => $project->task,
                'sub_task' => $project->sub_task
            ]);
            return ['next' => collect([]), 'previous' => collect([]), 'current' => null];
        }

        $currentSubTask = projess_task::find($project->sub_task);
        if (!$currentSubTask) {
            \Log::error('Sub task not found in database', [
                'ID_RSO' => $project->ID_RSO,
                'sub_task_id' => $project->sub_task
            ]);
            return ['next' => collect([]), 'previous' => collect([]), 'current' => null];
        }

        $taskParent = $currentSubTask->task_parent;
        $currentOrder = $currentSubTask->task_order;

        \Log::info('Getting available tasks', [
            'ID_RSO' => $project->ID_RSO,
            'current_task' => $currentSubTask->task_name,
            'task_parent' => $taskParent,
            'current_order' => $currentOrder
        ]);

        // Get next tasks (task_order > current, same parent)
        $nextTasks = projess_task::where('task_parent', $taskParent)
            ->where('task_order', '>', $currentOrder)
            ->orderBy('task_order', 'asc')
            ->get();

        // Get previous tasks (task_order < current, same parent)
        $previousTasks = projess_task::where('task_parent', $taskParent)
            ->where('task_order', '<', $currentOrder)
            ->orderBy('task_order', 'desc')
            ->get();

        \Log::info('Available tasks found', [
            'next_count' => $nextTasks->count(),
            'previous_count' => $previousTasks->count()
        ]);

        return [
            'next' => $nextTasks,
            'previous' => $previousTasks,
            'current' => $currentSubTask
        ];
    }

    /**
     * Render Process Task Button (Inline, for header)
     */
    protected function renderProcessTaskButtonInline($project, $availableTasks)
    {
        $hasNextOrPrevious = !$availableTasks['next']->isEmpty() || !$availableTasks['previous']->isEmpty();
        
        $currentTask = $availableTasks['current'] ?? null;
        $currentTaskName = $currentTask ? $currentTask->task_name : 'N/A';

        // Always render button, but disable if no tasks available
        $buttonClass = $hasNextOrPrevious ? 'btn-primary' : 'btn-default';
        $disabled = $hasNextOrPrevious ? '' : ' disabled';
        $onclick = $hasNextOrPrevious ? 'onclick="openProcessTaskModal()"' : '';

        return '
        <div style="text-align: right;">
            <div style="margin-bottom: 5px; font-size: 12px; color: #666;">
                <strong>Current Sub Task:</strong> ' . $currentTaskName . '
            </div>
            <button type="button" class="btn ' . $buttonClass . '" id="processTaskBtn" ' . $onclick . $disabled . ' title="' . ($hasNextOrPrevious ? 'Click to process task' : 'No tasks available to process') . '">
                <i class="fa fa-tasks"></i> Process Task
            </button>
        </div>';
    }

    /**
     * Render Process Task Button for Menu (btn-group)
     */
    protected function renderProcessTaskButtonForMenu($project, $availableTasks)
    {
        $hasNextOrPrevious = !$availableTasks['next']->isEmpty() || !$availableTasks['previous']->isEmpty();
        
        $currentTask = $availableTasks['current'] ?? null;
        $currentTaskName = $currentTask ? $currentTask->task_name : 'N/A';

        // Always render button, but disable if no tasks available
        $buttonClass = $hasNextOrPrevious ? 'btn-danger' : 'btn-default';
        $disabled = $hasNextOrPrevious ? '' : ' disabled';
        $onclick = $hasNextOrPrevious ? 'onclick="openProcessTaskModal()"' : '';

        return '<button type="button" class="btn ' . $buttonClass . '" id="processTaskBtn" ' . $onclick . $disabled . ' title="Current: ' . $currentTaskName . ($hasNextOrPrevious ? '' : ' - No tasks available') . '">
                <i class="fa fa-tasks"></i> Process Task
            </button>';
    }

    /**
     * Render Process Task Modal
     */
    protected function renderProcessTaskModal($project, $availableTasks)
    {
        $hasNextOrPrevious = !$availableTasks['next']->isEmpty() || !$availableTasks['previous']->isEmpty();
        
        $currentTask = $availableTasks['current'] ?? null;
        $currentTaskName = $currentTask ? $currentTask->task_name : 'N/A';

        // Modal
        $html = '<div class="modal" id="processTaskModal" tabindex="-1" role="dialog" style="display:none;overflow-y:auto;" onclick="if(event.target.id===\'processTaskModal\')closeProcessTaskModal()">
            <div class="modal-dialog modal-lg" role="document" style="margin-top:50px;">
                <div class="modal-content">
                    <form method="POST" action="' . url(config('admin.route.prefix') . '/pre-sales/process-task/' . $project->ID_RSO) . '">
                        ' . csrf_field() . '
                        <div class="modal-header">
                            <button type="button" class="close" onclick="closeProcessTaskModal()">&times;</button>
                            <h4 class="modal-title"><i class="fa fa-tasks"></i> Process Task - ' . $project->ID_RSO . '</h4>
                        </div>
                        <div class="modal-body">
                            <!-- Info Box -->
                            <div style="background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%); border-left: 4px solid #667eea; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                                <div style="display: flex; align-items: center;">
                                    <i class="fa fa-info-circle" style="font-size: 24px; color: #667eea; margin-right: 12px;"></i>
                                    <div>
                                        <strong style="color: #333; font-size: 14px;">Current Task</strong>
                                        <div style="color: #667eea; font-size: 16px; font-weight: 600; margin-top: 3px;">' . $currentTaskName . '</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fa fa-list-alt" style="margin-right: 5px;"></i> Pilih Sub Task Tujuan <span>*</span></label>';
        
        // Check if has available tasks
        if (!$hasNextOrPrevious) {
            $html .= '
                                <div class="alert alert-warning" style="margin-top: 10px;">
                                    <i class="fa fa-exclamation-triangle"></i>
                                    <strong>Tidak ada task yang tersedia.</strong><br>
                                    Project ini sudah mencapai task terakhir atau belum memiliki task yang dikonfigurasi.
                                </div>';
        } else {
            $html .= '
                                <select name="target_task_id" class="form-control" required>
                                    <option value="">-- Pilih Sub Task --</option>';
            
            // Next tasks
            if (!$availableTasks['next']->isEmpty()) {
                $html .= '<optgroup label="📈 NEXT TASKS (Lanjutkan Proses)">';
                foreach ($availableTasks['next'] as $task) {
                    $html .= '<option value="' . $task->id . '">→ ' . $task->task_name . '</option>';
                }
                $html .= '</optgroup>';
            }
            
            // Previous tasks
            if (!$availableTasks['previous']->isEmpty()) {
                $html .= '<optgroup label="📉 PREVIOUS TASKS (Kembalikan Proses)">';
                foreach ($availableTasks['previous'] as $task) {
                    $html .= '<option value="' . $task->id . '">← ' . $task->task_name . '</option>';
                }
                $html .= '</optgroup>';
            }
            
            $html .= '      </select>
                                <small style="display: block; margin-top: 8px; color: #999; font-size: 12px;">
                                    <i class="fa fa-lightbulb-o"></i> Pilih task selanjutnya untuk melanjutkan proses, atau task sebelumnya untuk mengembalikan.
                                </small>';
        }
        
        $html .= '
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fa fa-comment" style="margin-right: 5px;"></i> Catatan / Komentar <span>*</span></label>
                                <textarea name="notes" class="form-control" rows="5" placeholder="Masukkan catatan atau alasan perubahan task..." ' . (!$hasNextOrPrevious ? 'disabled' : 'required') . '></textarea>
                                <small style="display: block; margin-top: 8px; color: #999; font-size: 12px;">
                                    <i class="fa fa-info-circle"></i> Catatan ini akan tercatat di history log project.
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" onclick="closeProcessTaskModal()">
                                <i class="fa fa-times"></i> ' . ($hasNextOrPrevious ? 'Batal' : 'Tutup') . '
                            </button>
                            ' . ($hasNextOrPrevious ? '<button type="submit" class="btn btn-primary">
                                <i class="fa fa-paper-plane"></i> Process & Update Task
                            </button>' : '') . '
                        </div>
                    </form>
                </div>
            </div>
        </div>';

        return $html;
    }

    /**
     * Process Task - Handle form submission
     */
    public function processTask(Request $request, $id)
    {
        $project = projects::findOrFail($id);
        
        $request->validate([
            'target_task_id' => 'required|exists:1_tasks,id',
            'notes' => 'required|string'
        ]);

        $targetTaskId = $request->input('target_task_id');
        $notes = $request->input('notes');

        // Get current and target task details
        $currentSubTask = $project->sub_task ? projess_task::find($project->sub_task) : null;
        $targetTask = projess_task::find($targetTaskId);

        // Determine action type (proceed or return)
        $actionType = 'proceed';
        if ($currentSubTask && $targetTask) {
            if ($targetTask->task_order < $currentSubTask->task_order) {
                $actionType = 'return';
            }
        }

        // Update project sub_task
        $project->sub_task = $targetTaskId;
        $project->save();

        // Insert log
        projess_log::create([
            'task_id' => $project->task,
            'trackable_type' => 'App\Models\projects',
            'trackable_id' => $project->ID_RSO,
            'model_type' => 'App\Models\projects',
            'model_id' => $project->id,
            'id_rso' => $project->ID_RSO,
            'action_type' => $actionType,
            'from_task_id' => $currentSubTask ? $currentSubTask->id : null,
            'from_task_order' => $currentSubTask ? $currentSubTask->task_order : null,
            'from_task_parent' => $currentSubTask ? $currentSubTask->task_parent : null,
            'to_task_id' => $targetTask->id,
            'to_task_order' => $targetTask->task_order,
            'to_task_parent' => $targetTask->task_parent,
            'notes' => $notes,
            'user_id' => Admin::user()->id,
        ]);

        admin_toastr('Task berhasil diproses: ' . $targetTask->task_name, 'success');
        
        return redirect()->back();
    }

    /**
     * Helper: Render custom table with consistent column width
     * First column: 20%, other columns: auto
     */
    protected function renderCustomTable($headers, $rows, $firstColWidth = '20%')
    {
        $html = '<table class="table table-bordered table-striped" style="width: 100%;">';
        
        // Render headers if provided
        if (!empty($headers)) {
            $html .= '<thead><tr>';
            foreach ($headers as $index => $header) {
                $width = $index === 0 ? $firstColWidth : 'auto';
                $html .= '<th style="width: ' . $width . ';">' . $header . '</th>';
            }
            $html .= '</tr></thead>';
        }
        
        // Render body
        $html .= '<tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $index => $cell) {
                $width = $index === 0 ? $firstColWidth : 'auto';
                $html .= '<td style="width: ' . $width . ';">' . ($cell ?? '-') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        
        $html .= '</table>';
        
        return $html;
    }

    /**
     * Render Tab: Informasi Umum
     */
    protected function detailInfoUmum($project)
    {
        $data = [
            'ID RSO' => $project->ID_RSO,
            'Customer' => $project->Customer,
            'Nama Project' => $project->Nama_Project,
            'Witel' => $project->Witel,
            'Segmen' => $project->segmen,
            'Account Manager' => $project->AM,
            'Tahun Project' => $project->Project_Tahun,
            // 'Tipe KL' => $project->Tipe_KL,  // Hidden
            // 'Flag KL' => $project->Flag_KL,  // Hidden
            'Tipe Project' => $project->tipe_projek,
            'Is Renewal Only' => $project->is_renewal_only ? 'Ya' : 'Tidak',
            'Keterangan' => $project->Keterangan,
            'Created By' => DB::table('admin_users')->where('id', $project->created_by)->value('name') ?? '-',
            'Created At' => $project->created_at,
            'Updated At' => $project->updated_at,
        ];

        $rows = [];
        foreach ($data as $key => $value) {
            $rows[] = [$key, $value ?? '-'];
        }

        return $this->renderCustomTable([], $rows);
    }

    /**
     * Render Tab: Status & Timeline
     */
    protected function detailStatusTimeline($project)
    {
        $statusColors = config('appConst.statusColors', []);
        $statusColor = $statusColors[$project->status_project] ?? 'secondary';
        
        // Get task name
        $taskName = '-';
        if ($project->task) {
            // Try to get from relation first
            try {
                if ($project->relationLoaded('task') && is_object($project->task)) {
                    $taskName = $project->task->task_name ?? '-';
                } else {
                    // Load relation if not loaded
                    $taskRelation = projess_task::find($project->task);
                    $taskName = $taskRelation ? $taskRelation->task_name : '-';
                }
            } catch (\Exception $e) {
                \Log::error('Error getting task name: ' . $e->getMessage());
                $taskName = '-';
            }
        }
        
        // Get sub task name
        $subTaskName = '-';
        if ($project->sub_task) {
            try {
                if ($project->relationLoaded('subTask') && is_object($project->subTask)) {
                    $subTaskName = $project->subTask->task_name ?? '-';
                } else {
                    // Load relation if not loaded
                    $subTaskRelation = projess_task::find($project->sub_task);
                    $subTaskName = $subTaskRelation ? $subTaskRelation->task_name : '-';
                }
            } catch (\Exception $e) {
                \Log::error('Error getting sub task name: ' . $e->getMessage());
                $subTaskName = '-';
            }
        }
        
        $statusStep = optional($project->pstatus)->step ?? '-';
        
        // Format dates to show only date without time
        $formatDate = function($date) {
            if (!$date) return '-';
            if ($date instanceof \Carbon\Carbon) {
                return $date->format('Y-m-d');
            }
            // If string, try to parse and format
            try {
                return \Carbon\Carbon::parse($date)->format('Y-m-d');
            } catch (\Exception $e) {
                return $date;
            }
        };
        
        // Calculate progress percentage
        $progressBadge = '';
        $progressPercentage = 0;
        
        if ($project->sub_task) {
            try {
                $currentSubTask = projess_task::find($project->sub_task);
                
                if ($currentSubTask && $currentSubTask->task_parent) {
                    // Get all sub tasks with the same parent, ordered by task_order
                    $allSubTasks = projess_task::where('task_parent', $currentSubTask->task_parent)
                        ->orderBy('task_order', 'asc')
                        ->get();
                    
                    if ($allSubTasks->count() > 0) {
                        // Find current position
                        $currentPosition = 0;
                        $totalTasks = $allSubTasks->count();
                        
                        foreach ($allSubTasks as $index => $task) {
                            if ($task->id == $project->sub_task) {
                                $currentPosition = $index + 1; // Position starts from 1
                                break;
                            }
                        }
                        
                        // Calculate percentage
                        $progressPercentage = ($currentPosition / $totalTasks) * 100;
                        
                        // Determine badge color
                        if ($progressPercentage < 50) {
                            $progressColor = 'background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);'; // Red
                            $progressIcon = 'fa-exclamation-triangle';
                        } elseif ($progressPercentage >= 50 && $progressPercentage <= 90) {
                            $progressColor = 'background: linear-gradient(135deg, #ffd93d 0%, #f6c23e 100%);'; // Yellow
                            $progressIcon = 'fa-clock-o';
                        } else {
                            $progressColor = 'background: linear-gradient(135deg, #51cf66 0%, #37b24d 100%);'; // Green
                            $progressIcon = 'fa-check-circle';
                        }
                        
                        $progressBadge = '<span class="badge" style="' . $progressColor . ' color: white; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; margin-left: 8px;"><i class="fa ' . $progressIcon . '" style="margin-right: 5px;"></i>' . round($progressPercentage, 1) . '% (' . $currentPosition . '/' . $totalTasks . ')</span>';
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error calculating progress: ' . $e->getMessage());
            }
        }
        
        // Create badge for task and sub task
        $taskBadge = $taskName !== '-' 
            ? '<span class="badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;"><i class="fa fa-layer-group" style="margin-right: 5px;"></i>' . $taskName . '</span>' . $progressBadge
            : '<span class="badge bg-secondary">-</span>';
            
        $subTaskBadge = $subTaskName !== '-'
            ? '<span class="badge" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600;"><i class="fa fa-tasks" style="margin-right: 5px;"></i>' . $subTaskName . '</span>'
            : '<span class="badge bg-secondary">-</span>';
        
        // Prepare data as array
        $rows = [
            ['Status Project', '<span class="badge bg-' . $statusColor . '">' . $statusStep . '</span>'],
            ['Task', $taskBadge],
            ['Sub Task', $subTaskBadge],
            ['Is WIN', $project->is_win ? '<span class="badge bg-success">SUDAH WIN</span>' : '<span class="badge bg-secondary">BELUM WIN</span>'],
            ['Is Verified', $project->is_verified ? '<span class="badge bg-success">VERIFIED</span>' : '<span class="badge bg-secondary">BELUM</span>'],
            //['Is NGTMA', $project->is_ngtma ? 'Ya' : 'Tidak'],
            //['Is IBL', $project->is_ibl ? 'Ya' : 'Tidak'],
            ['Jangka Waktu', ($project->jangka_waktu ?? 0) . ' bulan'],
            ['Start Kontrak', $formatDate($project->start_kontrak)],
            ['End Kontrak', $formatDate($project->end_kontrak)],
            ['Tanggal RFS', $formatDate($project->tanggal_rfs)],
            ['Tanggal Layanan', $formatDate($project->tanggal_layanan)],
            ['P1 Tanggal', $formatDate($project->p1_tanggal)],
            ['P1 Nomor', $project->p1_nomor ?? '-'],
            ['P1 Nama Kontrak', $project->p1_namaKontrak ?? '-'],
        ];

        return $this->renderCustomTable([], $rows);
    }

    /**
     * Render Tab: Financial
     */
    protected function detailFinancial($project)
    {
        $data = [
            'Nilai Project Total' => 'Rp ' . number_format($project->Nilai_Project_Total, 0, ',', '.'),
            'Nilai OBL' => 'Rp ' . number_format($project->nilai_obl, 0, ',', '.'),
            'Nilai IBL' => 'Rp ' . number_format($project->nilai_ibl, 0, ',', '.'),
            'Profit' => 'Rp ' . number_format($project->profit, 0, ',', '.'),
            'COGS' => 'Rp ' . number_format($project->cogs, 0, ',', '.'),
            'Share Profit' => 'Rp ' . number_format($project->share_profit, 0, ',', '.'),
            'Nilai BOQ' => 'Rp ' . number_format($project->nilai_boq, 0, ',', '.'),
            'Nilai IRR' => $project->nilai_irr ? $project->nilai_irr . '%' : '-',
            'NPV' => $project->npv ? 'Rp ' . number_format($project->npv, 0, ',', '.') : '-',
            'PEB' => $project->peb ? 'Rp ' . number_format($project->peb, 0, ',', '.') : '-',
            'Skema Pembayaran' => $project->skema_pembayaran ?? '-',
            'Skema Bisnis' => $project->skema_bisnis ?? '-',
            'Is NJKI' => $project->is_njki ? 'Ya' : 'Tidak',
            'Draft KB' => $project->draft_kb ?? '-',
        ];

        $rows = [];
        foreach ($data as $key => $value) {
            $rows[] = [$key, $value ?? '-'];
        }

        return $this->renderCustomTable([], $rows);
    }

    /**
     * Render Tab: Dokumen OBL
     */
    protected function detailDokumen($project)
    {
        $docs = document::where('id_rso', $project->ID_RSO)
            ->leftJoin('0_workflow as w', '0_document.status_doc', '=', 'w.step_id')
            ->select('0_document.*', 'w.step')
            ->get();

        $headers = ['ID OBL', 'Layanan', 'Status', 'Action'];

        if ($docs->isEmpty()) {
            // Return empty table with message
            return $this->renderCustomTable(
                $headers,
                [['<em style="color: #999;">Tidak ada dokumen OBL</em>', '', '', '']]
            );
        }

        $rows = [];
        foreach ($docs as $doc) {
            $rows[] = [
                $doc->id_obl ?? '-',
                $doc->LAYANAN ?? '-',
                '<span class="badge bg-primary">' . ($doc->step ?? 'N/A') . '</span>',
                '<a href="' . url(config('admin.route.prefix') . '/document/' . $doc->id) . '" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> Lihat</a>',
            ];
        }

        return $this->renderCustomTable($headers, $rows);
    }

    /**
     * Render Tab: Diskusi
     */
    protected function detailDiskusi($project)
    {
        $diskusi = diskusi::where('object_id', $project->ID_RSO)
            ->join('admin_users', '0_diskusi.user_id', '=', 'admin_users.id')
            ->leftJoin('admin_roles', '0_diskusi.user_role', '=', 'admin_roles.id')
            ->select('0_diskusi.*', 'admin_users.name as user_name', 'admin_roles.name as role_name')
            ->orderBy('0_diskusi.created_at', 'desc')
            ->limit(10)
            ->get();

        $headers = ['Diskusi (10 Terakhir)'];

        if ($diskusi->isEmpty()) {
            // Return empty table with message and button
            $emptyMessage = '<em style="color: #999;">Belum ada diskusi</em><br><br>' .
                '<a href="' . url(config('admin.route.prefix') . '/projects/' . $project->ID_RSO . '/diskusi') . '" class="btn btn-primary">' .
                '<i class="fa fa-comments"></i> Mulai Diskusi' .
                '</a>';
            
            return $this->renderCustomTable($headers, [[$emptyMessage]]);
        }

        // Build rows for table
        $rows = [];
        foreach ($diskusi as $item) {
            $commentHtml = '<div style="padding: 10px 0; border-bottom: 1px solid #eee;">' .
                '<div style="margin-bottom: 5px;">' .
                '<strong>' . $item->user_name . '</strong> ' .
                '<small class="text-muted">(' . ($item->role_name ?? 'User') . ')</small> ' .
                '<small class="text-muted pull-right">' . $item->created_at . '</small>' .
                '</div>' .
                '<div style="margin-left: 20px;">' . substr(strip_tags($item->comment), 0, 200) . (strlen(strip_tags($item->comment)) > 200 ? '...' : '') . '</div>' .
                '</div>';
            
            $rows[] = [$commentHtml];
        }
        
        // Add button row
        $buttonRow = '<div style="margin-top: 15px; text-align: center;">' .
            '<a href="' . url(config('admin.route.prefix') . '/projects/' . $project->ID_RSO . '/diskusi') . '" class="btn btn-primary">' .
            '<i class="fa fa-comments"></i> Lihat Semua & Tambah Komentar' .
            '</a>' .
            '</div>';
        $rows[] = [$buttonRow];

        return $this->renderCustomTable($headers, $rows);
    }

    /**
     * Render Tab: Files
     */
    protected function detailFiles($project)
    {
        $files = files::where('ID_RSO', $project->ID_RSO)
            ->orderBy('created_at', 'desc')
            ->get();

        $headers = ['Filename', 'Type', 'Size', 'Upload Date', 'Action'];

        if ($files->isEmpty()) {
            // Return empty table with message
            return $this->renderCustomTable(
                $headers,
                [['<em style="color: #999;">Belum ada file yang diupload</em>', '', '', '', '']]
            );
        }

        $rows = [];
        foreach ($files as $file) {
            $rows[] = [
                $file->filename ?? '-',
                $file->filetype ?? '-',
                $file->filesize ? number_format($file->filesize / 1024, 2) . ' KB' : '-',
                $file->created_at ?? '-',
                '<a href="' . asset('storage/' . $file->filepath) . '" target="_blank" class="btn btn-sm btn-success"><i class="fa fa-download"></i> Download</a>',
            ];
        }

        return $this->renderCustomTable($headers, $rows);
    }

    /**
     * Render Tab: History/Logs
     */
    protected function detailHistory($project)
    {
        $logs = projess_log::where('id_rso', $project->ID_RSO)
            ->join('admin_users', '1_logs.user_id', '=', 'admin_users.id')
            ->leftJoin('1_tasks as from_task', '1_logs.from_task_id', '=', 'from_task.id')
            ->leftJoin('1_tasks as to_task', '1_logs.to_task_id', '=', 'to_task.id')
            ->select(
                '1_logs.*', 
                'admin_users.name as user_name',
                'from_task.task_name as from_task_name',
                'to_task.task_name as to_task_name'
            )
            ->orderBy('1_logs.created_at', 'desc')
            ->limit(50)
            ->get();

        $headers = ['Tanggal', 'User', 'Action', 'Changes'];

        if ($logs->isEmpty()) {
            return $this->renderCustomTable(
                $headers,
                [['<em style="color: #999;">Belum ada history perubahan</em>', '', '', '']]
            );
        }

        $rows = [];
        foreach ($logs as $log) {
            // Build change description based on action_type
            $changeText = '';
            
            switch ($log->action_type) {
                case 'proceed':
                case 'move':
                    $changeText = 'Task: <strong>' . ($log->from_task_name ?? '-') . '</strong> → <strong>' . ($log->to_task_name ?? '-') . '</strong>';
                    break;
                    
                case 'return':
                    $changeText = 'Return ke: <strong>' . ($log->to_task_name ?? '-') . '</strong>';
                    break;
                    
                case 'status_change':
                    $changeText = 'Status: <strong>' . ($log->from_status ?? '-') . '</strong> → <strong>' . ($log->to_status ?? '-') . '</strong>';
                    break;
                    
                case 'create':
                case 'update':
                case 'delete':
                    // Display changed fields if available
                    if ($log->changed_fields && is_array($log->changed_fields)) {
                        foreach ($log->changed_fields as $field => $values) {
                            if (is_array($values) && isset($values['old'], $values['new'])) {
                                $changeText .= '<strong>' . $field . '</strong>: ' . ($values['old'] ?? '-') . ' → ' . ($values['new'] ?? '-') . '<br>';
                            }
                        }
                    }
                    break;
            }
            
            // Add notes if available
            if ($log->notes) {
                $changeText .= '<br><small><em>' . $log->notes . '</em></small>';
            }
            
            $rows[] = [
                $log->created_at->format('Y-m-d H:i:s'),
                $log->user_name,
                '<span class="badge bg-info">' . strtoupper($log->action_type) . '</span>',
                $changeText ?: '-',
            ];
        }

        return $this->renderCustomTable($headers, $rows);
    }

    /**
     * Render Tab: Hasil Rapat
     */
    protected function detailHasilRapat($project)
    {
        $hasilRapatList = hasilRapat::where('object', projects::class)
            ->where('object_id', $project->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $headers = ['Tanggal', 'Tipe', 'Catatan', 'Dibuat Oleh', 'Aksi'];

        if ($hasilRapatList->isEmpty()) {
            return $this->renderCustomTable(
                $headers,
                [['<em style="color: #999;">Belum ada hasil rapat</em>', '', '', '', '']]
            );
        }

        $tableRows = [];
        foreach ($hasilRapatList as $rapat) {
            $notePreview = $rapat->note ? Str::limit(strip_tags($rapat->note), 160) : '-';
            $createdBy = DB::table('admin_users')->where('id', $rapat->created_by)->value('name') ?? '-';
            $tableRows[] = '<tr>
                <td>' . ($rapat->created_at ? $rapat->created_at->format('Y-m-d H:i') : '-') . '</td>
                <td>' . e($rapat->tipe ?? '-') . '</td>
                <td><div style="max-height: 110px; overflow:auto; line-height:1.4;">' . nl2br(e($notePreview)) . '</div></td>
                <td>' . e($createdBy) . '</td>
                <td>
                    <button type="button"
                        class="btn btn-xs btn-primary edit-rapat-btn"
                        data-id="' . $rapat->id . '"
                        data-tipe="' . e($rapat->tipe) . '"
                        data-note="' . e(base64_encode($rapat->note ?? '')) . '"
                        data-date="' . ($rapat->created_at ? $rapat->created_at->format('Y-m-d H:i') : '-') . '">
                        <i class="fa fa-edit"></i> Lihat & Edit
                    </button>
                </td>
            </tr>';
        }

        $tableHtml = '<div class="table-responsive">
            <table class="table table-bordered table-striped table-condensed">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Catatan</th>
                        <th>Dibuat Oleh</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>' . implode('', $tableRows) . '</tbody>
            </table>
        </div>';

        $tableHtml .= $this->renderHasilRapatEditModal($project);
        $tableHtml .= $this->renderHasilRapatModalScript();

        return $tableHtml;
    }

    /**
     * Render the modal markup for editing hasil rapat.
     */
    protected function renderHasilRapatEditModal($project)
    {
        $options = '';
        foreach (hasilRapat::tipeOptions() as $option) {
            $options .= '<option value="' . e($option) . '">' . e($option) . '</option>';
        }

        return '
        <div class="modal fade" id="hasilRapatEditModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form id="hasilRapatEditForm" method="POST" action="#">
                        ' . csrf_field() . '
                        <input type="hidden" name="project_id" value="' . $project->id . '">
                        <input type="hidden" name="id_rso" value="' . e($project->ID_RSO) . '">
                        <div class="modal-header">
                            <h4 class="modal-title"><i class="fa fa-comments"></i> Edit Hasil Rapat</h4>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label>Tipe Rapat</label>
                                <select class="form-control" id="hasilRapatTypeModal" name="tipe" required>
                                    ' . $options . '
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Catatan</label>
                                <textarea class="form-control" id="hasilRapatNoteModal" name="note" rows="6" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>';
    }

    /**
     * Render javascript to hook edit buttons to the modal.
     */
    protected function renderHasilRapatModalScript()
    {
        $baseUrl = url(config('admin.route.prefix') . '/pre-sales/hasil-rapat/');

        return '
        <script>
        (function($){
            var baseEditUrl = "' . $baseUrl . '";

            $(document).on("click", ".edit-rapat-btn", function(e) {
                e.preventDefault();
                var button = $(this);
                var rapatId = button.data("id");
                var tipe = button.data("tipe");
                var noteEncoded = button.data("note");
                var note = noteEncoded ? atob(noteEncoded) : "";

                $("#hasilRapatEditForm").attr("action", baseEditUrl + rapatId + "/update");
                $("#hasilRapatTypeModal").val(tipe);
                $("#hasilRapatNoteModal").val(note);
                $("#hasilRapatEditModal").modal("show");
            });
        })(jQuery);
        </script>';
    }

    /**
     * Build where clause based on user role
     *
     * @return string
     */
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
     * Make a grid builder for projects list
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
        
        // Check Roles and adjust data 
        if (Admin::user()->isRole('account_manager')) {
            $grid->model()->where('AM', '=', Admin::user()->name);
        } else {
            $grid->model()->whereRaw($this->whereBuild());
        }

        $grid->model()->orderBy('0_projects.created_at', 'desc');

        // Column Grid
        $grid->column('ID_RSO', 'ID RSO')->style('font-size:small;')->display(function ($col) {
            $user = DB::table('admin_users')->select('name')->where('id', '=', $this->created_by)->get();
            $name = count($user) > 0 ? $user[0]->name : '';
            
            return "<a href='/" . config('admin.route.prefix') . "/pre-sales/detail/$col'>$col</a><br/><small>by : " . $name . "</small>";
        });
        $grid->column('Witel', 'Witel')->style('font-size:small;')->display(function ($witel) {
            return $witel . " - " . $this->segmen . "<br/>" . $this->AM;
        });
        $grid->column('Customer', 'Customer')->style('font-size:small;')->width(400)->display(function ($cust) {			
            return $cust . " | Tahun " . $this->Project_Tahun . "<br/><small data-toggle='tooltip' data-placement='top' title='$this->Nama_Project'>" . $this->Nama_Project . "</small>";
        });
        
        // Task & Sub Task with Progress
        $grid->column('task_progress', 'Task & Progress')->style('font-size:small;')->width(300)->display(function () {
            // Get task name
            $taskName = '-';
            $subTaskName = '-';
            
            if ($this->task) {
                $taskRelation = projess_task::find($this->task);
                $taskName = $taskRelation ? $taskRelation->task_name : '-';
            }
            
            if ($this->sub_task) {
                $subTaskRelation = projess_task::find($this->sub_task);
                $subTaskName = $subTaskRelation ? $subTaskRelation->task_name : '-';
            }
            
            // Calculate progress
            $progressHtml = '';
            $progressPercentage = 0;
            
            if ($this->sub_task) {
                try {
                    $currentSubTask = projess_task::find($this->sub_task);
                    
                    if ($currentSubTask && $currentSubTask->task_parent) {
                        // Get all sub tasks with the same parent
                        $allSubTasks = projess_task::where('task_parent', $currentSubTask->task_parent)
                            ->orderBy('task_order', 'asc')
                            ->get();
                        
                        if ($allSubTasks->count() > 0) {
                            // Find current position
                            $currentPosition = 0;
                            $totalTasks = $allSubTasks->count();
                            
                            foreach ($allSubTasks as $index => $task) {
                                if ($task->id == $this->sub_task) {
                                    $currentPosition = $index + 1;
                                    break;
                                }
                            }
                            
                            // Calculate percentage
                            $progressPercentage = round(($currentPosition / $totalTasks) * 100, 1);
                            
                            // Determine badge color and icon
                            if ($progressPercentage < 50) {
                                $progressColor = 'background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);';
                                $progressIcon = 'fa-exclamation-triangle';
                            } elseif ($progressPercentage >= 50 && $progressPercentage <= 90) {
                                $progressColor = 'background: linear-gradient(135deg, #ffd93d 0%, #f6c23e 100%);';
                                $progressIcon = 'fa-clock-o';
                            } else {
                                $progressColor = 'background: linear-gradient(135deg, #51cf66 0%, #37b24d 100%);';
                                $progressIcon = 'fa-check-circle';
                            }
                            
                            $progressHtml = '<span class="badge" style="' . $progressColor . ' color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; margin-top: 3px; display: inline-block;"><i class="fa ' . $progressIcon . '" style="margin-right: 4px;"></i>' . $progressPercentage . '% (' . $currentPosition . '/' . $totalTasks . ')</span>';
                        }
                    }
                } catch (\Exception $e) {
                    // Silent error
                }
            }
            
            // Build task badge
            $taskBadge = $taskName !== '-' 
                ? '<span class="badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; display: inline-block;"><i class="fa fa-layer-group" style="margin-right: 4px;"></i>' . $taskName . '</span>'
                : '<span class="badge bg-secondary" style="font-size: 11px;">N/A</span>';
            
            // Build sub task badge
            $subTaskBadge = $subTaskName !== '-'
                ? '<span class="badge" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; display: inline-block;"><i class="fa fa-tasks" style="margin-right: 4px;"></i>' . $subTaskName . '</span>'
                : '<span class="badge bg-secondary" style="font-size: 11px;">N/A</span>';
            
            return $taskBadge . '<br/>' . $subTaskBadge . '<br/>' . $progressHtml;
        });
        
        //$grid->column('komentar', 'Last Komentar')->width(400)->style('font-size:small;')->display(function ($item) {
        //    $komen = DB::table('0_diskusi')
        //                ->selectRaw('admin_users.name, comment, 0_diskusi.created_at')
        //                ->join('admin_users', 'admin_users.id', '=', '0_diskusi.user_id')
        //                ->where('object_id', '=', $this->ID_RSO)
        //                ->orderBy('created_at', 'desc')
        //                ->limit(1)
        //                ->get();
        //    
        //    if(count($komen) == 0) {return; }
        //    $return = '<small>' . $komen[0]->name . ' | ' . $komen[0]->created_at . '</small><br/>';
        //    $return .= '<a href="'. env('APP_URL') . config('admin.route.prefix') .'/projects/'. $this->ID_RSO .'/diskusi"><small>' . $komen[0]->comment . '</small></a>';
        //    
        //    return $return;
        //});
        //$grid->column('input_komentar', 'Input Komentar')->style('font-size:small;')->display(function ($item) {
        //    return 'Input';
        //})->modal('Input Komentar', function ($item) {
        //    $form = new \OpenAdmin\Admin\Widgets\Form();
        //    $form->action('/' . config('admin.route.prefix') . '/diskusi/save');
        //    $form->hidden('user_id')->default(Admin::user()->id);
        //    $form->hidden('user_role')->default(Admin::user()->roles[0]['id']);
        //    $form->hidden('object_id')->default($this->ID_RSO);
        //    $form->textarea('comment', 'Komentar')->rows(3)->required();
        //    
        //    return $form->render();
        //});
        $grid->column('pstatus.step', 'Status')->style('font-size:small;')->display(function ($item) {
            $colors = config('appConst.statusColors');
            
            $flag = $this->is_win == '1' ? '<span class="badge bg-success">SUDAH WIN</span>' : '<span class="badge bg-secondary">BELUM WIN</span>';
            $verified = $this->is_verified == '1' ? '<span class="badge bg-success">VERIFIED</span>' : '';
            $step = '<span class="badge bg-'. $colors[$this->status_project] .'">' . $item . '</span>';
            
            return $flag . ' ' . $verified . '<br/>' . $step;
        });
        //$grid->column('obl', 'OBL List')->style('font-size:small;')->display(function ($item) {
        //    $docs = document::select('id_rso', 'id_obl', 'LAYANAN', 'status_doc', 'w.id', 'w.step')
        //            ->where('id_rso', '=', $this->ID_RSO)
        //            ->join('0_workflow as w', '0_document.status_doc', '=', 'w.step_id')
        //            ->get();
        //    
        //    $result = '';
        //    foreach ($docs as $doc) {
        //        $result .= '<a href="/projess/document/' . $doc->id . '"><span class="badge bg-primary">' . $doc->LAYANAN . ' (' . $doc->step . ')</span></a><br/>';
        //    }
        //    
        //    return $result;
        //});
        //$grid->column('created_at', 'Tanggal Create')->style('font-size:small;')->sortable();
        //$grid->column('updated_at', 'Tanggal Update')->style('font-size:small;')->sortable();

        // Customize Grid
        $grid->fixColumns(1, -1);
        $grid->disableExport();
        $grid->disableRowSelector();
        $grid->disableColumnSelector();
        $grid->disableCreateButton();
        
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
                $filter->equal('is_win', 'WIN ?')->radio(['0' => 'Belum WIN', '1' => 'Sudah WIN']);
            });
            
            $filter->column(1/2, function ($filter) {
                // Get tasks list from projess_task model (parent tasks only)
                $tasks = projess_task::where('task_parent', 0)
                    ->orderBy('task_order', 'asc')
                    ->pluck('task_name', 'id')
                    ->toArray();
                
                // Get sub tasks list (tasks with parent)
                $subTasks = projess_task::where('task_parent', '>', 0)
                    ->orderBy('task_order', 'asc')
                    ->pluck('task_name', 'id')
                    ->toArray();
                
                $filter->equal('task', 'Task')->select($tasks);
                $filter->equal('sub_task', 'Sub Task')->select($subTasks);
                $filter->equal('Witel')->select(config('appConst.witels'));
                $filter->equal('Segmen', 'Segmen')->select(config('appConst.segmens'));
                $filter->equal('status_project', 'Status')->select(config('appConst.pstatus'));
            });
        });

        return $grid;
    }
}
