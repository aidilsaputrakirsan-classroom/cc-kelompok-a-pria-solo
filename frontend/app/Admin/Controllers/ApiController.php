<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use \App\Models\projects;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use \App\Admin\Controllers\ProjectsController;
use Illuminate\Support\Facades\DB;


// class ApiController extends AdminController
class ApiController extends Controller
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = 'API controller';


/**
     * Store a newly created project from API.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $segmen='SME')
    {
        // Validasi input JSON
        $validator = Validator::make($request->all(), [
            'am' => 'required|string|max:250',
            'witel' => 'required|string|max:50',
            'tahun' => 'required|string|max:50',
            'customer' => 'required|string|max:250',
			'nama_project' => 'required|string|max:1024',
			'nilai_project' => 'required|numeric'
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // Insert data ke database
        try {
			// Generate ID_RSO
			$ID_RSO = date('y') . 'SME' . date('m') . (new ProjectsController)->getUrut();
				
            $project = projects::create([
                'ID_RSO' => $ID_RSO,
				'AM' => $request->am,
				'segmen' => $segmen,
                'Witel' => $request->witel,
                'Project_Tahun' => $request->tahun,
                'Customer' => $request->customer,
				'Nama_Project' => $request->nama_project,
				'Nilai_Project_Total' => $request->nilai_project,
				'created_by' => '96' //NUSAC BORNEO
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan',
                'data' => $ID_RSO
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

	
    /**
     * Mengembalikan list projects dalam segmen SME
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request, $segmen='SME'): JsonResponse
    {
        // try {
            // Query untuk mengambil projects dengan segmen SME
            // $projects = projects::where('segmen', $segmen)
                // ->select([
                    // '0_projects.ID_RSO', '0_projects.Witel', '0_projects.segmen', '0_projects.AM', '0_projects.Project_Tahun', '0_projects.Customer', '0_projects.Nama_Project', '0_projects.status_project', '0_workflow.step', '0_projects.is_win', '0_projects.is_verified', '0_projects.Keterangan', '0_projects.created_at', '0_projects.updated_at'
                // ])
				// ->join('0_workflow', '0_projects.status_project', '=', '0_workflow.step_id')
                // ->orderBy('0_projects.created_at', 'desc');

		try {
            // 1. Definisikan Sub-query untuk mendapatkan ID diskusi terbaru per project
            $latestDiscussionId = DB::table('0_diskusi AS t2')
                ->select(DB::raw('MAX(t2.id)'))
                ->whereRaw('t2.object_id = a.ID_RSO'); // Mengacu pada alias 'a' di query utama

            // 2. Query Utama dengan join dan selectRaw
            $projects = DB::table('0_projects AS a')
                ->select([
                    'a.ID_RSO', 'a.Witel', 'a.segmen', 'a.AM', 'a.Project_Tahun', 'a.Customer', 'a.Nama_Project',
                    'a.Nilai_Project_Total', 'a.is_win', 'a.is_verified', 'a.status_project',
                    DB::raw("w.step AS status_project_desc"),
                    DB::raw("REGEXP_REPLACE(a.Keterangan, '<[^>]*>', '') AS Keterangan"),
                    DB::raw("a.created_at AS project_created_at"),
                    DB::raw("LEFT(REGEXP_REPLACE(b.comment, '<[^>]*>', ''), 50) AS project_diskusi"),
                    DB::raw("b.created_at AS tgl_project_diskusi")
                ])
				
                // Filter berdasarkan segmen
                ->where('a.segmen', $segmen)
				
                // Join ke tabel 0_workflow
                ->join('0_workflow AS w', 'a.status_project', '=', 'w.step_id')
				
                // Join ke tabel 0_diskusi berdasarkan ID diskusi terbaru
                ->join('0_diskusi AS b', function($join) use ($latestDiscussionId) {
                    $join->on('a.ID_RSO', '=', 'b.object_id')
                         // Memastikan hanya mengambil ID diskusi yang terbaru
                         ->whereRaw('b.id = (' . $latestDiscussionId->toSql() . ')');
                })
                // Order by project creation date
                ->orderBy('a.created_at', 'desc');

				// Filter berdasarkan parameter request jika ada
            if ($request->has('witel')) {
                $projects->where('Witel', $request->witel);
            }

            if ($request->has('status_project')) {
                $projects->where('status_project', $request->status_project);
            }

            if ($request->has('customer')) {
                $projects->where('Customer', 'like', '%' . $request->customer . '%');
            }

            if ($request->has('am')) {
                $projects->where('AM', 'like', '%' . $request->am . '%');
            }

			// Filter berdasarkan search
            if ($request->has('search')) {
                $projects->where(function($query) use ($request) {
                    $query->where('0_projects.Nama_Project', 'like', '%' . $request->search . '%') 
                          ->orWhere('0_projects.Customer', 'like', '%' . $request->search . '%'); 
                });
            }

            // Pagination jika diperlukan
            $perPage = $request->get('per_page', 10);
            $projects = $projects->paginate($perPage);

            // Format response JSON
            return response()->json([
                'success' => true,
                'message' => 'SME Projects retrieved successfully',
                'data' => [
                    'projects' => $projects->items(),
                    'pagination' => [
                        'current_page' => $projects->currentPage(),
                        'per_page' => $projects->perPage(),
                        'total' => $projects->total(),
                        'last_page' => $projects->lastPage(),
                        'from' => $projects->firstItem(),
                        'to' => $projects->lastItem()
                    ]
                ]
            ], JsonResponse::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving SME projects',
                'error' => $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
	
	/**
     * Mengembalikan detail project SME berdasarkan ID_RSO
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            // $project = projects::where('segmen', 'SME')
                // ->findOrFail($id);

            $latestDiscussionId = DB::table('0_diskusi AS t2')
                ->select(DB::raw('MAX(t2.id)'))
                ->whereRaw('t2.object_id = a.ID_RSO'); // Mengacu pada alias 'a' di query utama

            $project = DB::table('0_projects AS a')
                ->select([
                    'a.*',
                    DB::raw("w.step AS status_project_desc"),
                    DB::raw("LEFT(REGEXP_REPLACE(b.comment, '<[^>]*>', ''), 50) AS project_diskusi"),
                    DB::raw("b.created_at AS tgl_project_diskusi")
                ])
				
                // Filter berdasarkan ID
                ->where('a.ID_RSO', $id)
				
                // Join ke tabel 0_workflow
                ->join('0_workflow AS w', 'a.status_project', '=', 'w.step_id')
				
                // Join ke tabel 0_diskusi berdasarkan ID diskusi terbaru
                ->join('0_diskusi AS b', function($join) use ($latestDiscussionId) {
                    $join->on('a.ID_RSO', '=', 'b.object_id')
                         // Memastikan hanya mengambil ID diskusi yang terbaru
                         ->whereRaw('b.id = (' . $latestDiscussionId->toSql() . ')');
                });

            return response()->json([
                'success' => true,
                'message' => 'SME Project detail retrieved successfully',
                'data' => $project->first()
            ], JsonResponse::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'SME Project not found'
            ], JsonResponse::HTTP_NOT_FOUND);
        }
    }
	
}
