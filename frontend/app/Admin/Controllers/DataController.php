<?php

namespace App\Admin\Controllers;

use App\Http\Controllers\Controller;
use OpenAdmin\Admin\Admin;
use OpenAdmin\Admin\Controllers\Dashboard;
use OpenAdmin\Admin\Layout\Column;
use OpenAdmin\Admin\Layout\Content;
use OpenAdmin\Admin\Layout\Row;
use Revolution\Google\Sheets\Facades\Sheets;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use \App\Exports\projectsExport;
use \App\Models\projects;
use \App\Models\lop;
use \App\Models\obl;

class DataController extends Controller
{
	private $sheetID = '1kPBftbfS1sgWl6R9PdgoZzEsDtFtGs_-IodME3NWvms';
	
	// update tabel OBL from google sheet
	public function updateObl()
	{
		// Read GSheet data
		$getrange = 'A:AE';
		$getsheet = 'BMBS';
		
		$key =[
"NO"  ,"PROSES"  ,"TANGGAL_SUBMIT"  ,"TANGGAL_UPDATE"  ,"SEGMEN"  ,"FOLDER"  ,"FOLDER_OBL"  ,"WITEL"  ,"TAHUN"  ,"JENIS_SPK"  ,"NAMA_PELANGGAN"  ,"LAYANAN"  ,"NAMA_VENDOR"  ,"JANGKA_WAKTU"  ,"NILAI_KL"  ,"NO_KFS_SPK"  ,"NO_P8"  ,"NO_KL_WO_SURAT_PESANAN"  ,"PIC_MITRA"  ,"STATUS"  ,"STATUS_SM"  ,"KETERANGAN"  ,"ORDER_PROSES"  ,"ID_RSO"  ,"NO_QUOTE"  ,"SID"  ,"NO_ORDER"  ,"UMUR_ORDER"  ,"STATUS_OBL_DR"  ,"STATUS_KL_DR"  ,"ID_OBL"  ,	
	];
		
		$values = Sheets::spreadsheet($this->sheetID)->sheet($getsheet)->range($getrange)->get();
		$key1 = $values->pull(0);
		$data = Sheets::collection($key, $values);
				
		// Insert data into table
		obl::truncate();
		$rows = $data->toArray();
		
		foreach($rows as $row) {
			obl::insert($row);			
		}
		
		return sizeof($data) . ' rows data processed';
	}	

	// update tabel projects from google sheet
	public function updateProjects()
	{
		// Read GSheet data
		$getrange = 'A:N';
		$getsheet = 'Solution';
		
		$key =[
"ID_RSO","Witel","AM","Project_Tahun","Customer","Nama_Project","Tipe_KL","Flag_KL","Nilai_Project_Total","Nilai_OBL","Profit","share_profit","Posisi_Berkas","Keterangan"
	];
		
		$values = Sheets::spreadsheet($this->sheetID)->sheet($getsheet)->range($getrange)->get();
		$key1 = $values->pull(0);
		$data = Sheets::collection($key, $values);
				
		// Insert data into table
		lop::truncate();
		$rows = $data->toArray();
		
		foreach($rows as $row) {
			if($row['ID_RSO'] != '') {
				lop::insert($row);
			}
		}
		
		return sizeof($data) . ' rows data processed';
	}	

	// retrieve the data from google sheet
	public function getGoogleSheetValues()
	{
		$getrange = 'A1:C10';
		$values = Sheets::spreadsheet($this->sheetID)->sheet('Solution')->range($getrange)->all();
		
		return $values;
	}	

	// Tes download excel
	protected function exportProjects()
	{
		if(Admin::user()->can('projects_download')) {
			return Excel::download(new projectsExport, 'projects.xlsx', \Maatwebsite\Excel\Excel::XLSX);
		} else {
			return "Anda Tidak memiliki previledge untuk men-Download...";
		}
	}
}
