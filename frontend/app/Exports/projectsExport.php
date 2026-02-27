<?php

namespace App\Exports;

use \App\Models\projects;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;

class projectsExport implements FromCollection, WithHeadings
{
    public function collection()
    {
		// return projects::all();
		
        // return DB::table('0_projects')
				// ->select("ID_RSO","Witel","segmen","AM","Project_Tahun","Customer","Nama_Project","Nilai_Project_Total","nilai_obl","nilai_ibl","profit","cogs","jangka_waktu","is_njki","nilai_boq","nilai_irr","npv","peb","start_kontrak","end_kontrak","is_win","is_verified","Keterangan","0_workflow.step","0_projects.created_at")
				// ->join('0_workflow', 'status_project', '=', 'step_id')
				// ->get();

		return DB::table('0_projects as a')
			// Join dengan tabel workflow untuk mendapatkan status project
			->join('0_workflow as w', 'a.status_project', '=', 'w.step_id')
			// Join dengan tabel diskusi untuk mendapatkan komentar terakhir
			->join('0_diskusi as b', 'a.ID_RSO', '=', 'b.object_id')
			// Kondisi WHERE untuk memastikan kita hanya mengambil baris diskusi (b)
			// yang merupakan entri terbaru untuk setiap project (a).
			// Ini adalah terjemahan langsung dari subquery di query asli Anda.
			->whereRaw('b.id = (
				SELECT t2.id
				FROM 0_diskusi t2
				WHERE t2.object_id = a.ID_RSO
				ORDER BY t2.created_at DESC
				LIMIT 1
			)')
			->select(
				'a.ID_RSO',
				'a.Witel',
				'a.segmen',
				'a.AM',
				'a.Project_Tahun',
				'a.Customer',
				'a.Nama_Project',
				'a.Nilai_Project_Total',
				'a.nilai_obl',
				'a.nilai_ibl',
				'a.profit',
				'a.cogs',
				'a.jangka_waktu',
				'a.is_njki',
				'a.nilai_boq',
				'a.nilai_irr',
				'a.npv',
				'a.peb',
				'a.start_kontrak',
				'a.end_kontrak',
				'a.is_win',
				'a.is_verified',
				'w.step as status_project',
				'a.created_at as project_created_at',
				'b.created_at as tgl_project_diskusi'
			)
			->selectRaw("LEFT(REGEXP_REPLACE(b.comment, '<[^>]*>', ''), 50) AS project_diskusi")
			->selectRaw("REGEXP_REPLACE(a.Keterangan, '<[^>]*>', '') AS Keterangan")
			// Menjalankan query dan mengambil hasilnya
			->get();
    }
	
	public function headings(): array
    {
        return ["ID_RSO","Witel","Segmen","AM","Project_Tahun","Customer","Nama_Project","Nilai_Project_Total","nilai_obl","nilai_ibl","profit","cogs","jangka_waktu","is_njki","nilai_boq","nilai_irr","npv","peb","start_kontrak","end_kontrak","is_win","is_verified","status_project","project_created_at","Tanggal Diskusi Project","Diskusi Project","Keterangan"];
    }
}