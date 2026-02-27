<?php

namespace App\Admin\Controllers;

use App\Models\projectsMgmt;
use \App\Models\document;
use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Form;
use OpenAdmin\Admin\Grid;
use OpenAdmin\Admin\Show;
use OpenAdmin\Admin\Admin;
use Illuminate\Support\Facades\URL;

class ProjectsMgmtController extends AdminController
{
    /**
     * Judul untuk halaman CRUD ini.
     *
     * @var string
     */
    protected $title = 'Master Proyek';

    /**
     * Membuat tampilan Grid (daftar data).
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new projectsMgmt());

        // Menambahkan kolom-kolom utama untuk ditampilkan di grid
        // $grid->column('id', __('ID'))->sortable();
        $grid->column('wbs', __('WBS'))->sortable();
        $grid->column('tipe', __('Tipe'));
        $grid->column('id_rso', __('ID RSO'))->sortable();
        $grid->column('nama_pesanan', __('Nama Pesanan'))->limit(50)->display(function($item) {
			$url = url(URL::current() . '/' . $this->getKey());
			return "<a href='{$url}'>{$item}</a>";
		});
        $grid->column('witel', __('Witel'))->sortable();
        $grid->column('mitra_penyedia', __('Mitra'));
        $grid->column('status_delivery', __('Status Delivery'))->label([
            'Completed' => 'success',
            'Inprogress' => 'warning',
            'Issue' => 'danger',
        ])->sortable();
        $grid->column('tgl_rfs', __('Tgl RFS'))->sortable();

		// Grid Action
		$grid->disableActions();
		$grid->disableExport();
		$grid->disableRowSelector();
		$grid->disableColumnSelector();
		
        // Menambahkan filter untuk pencarian
        $grid->filter(function($filter){
            // Menonaktifkan filter ID default
            $filter->disableIdFilter();

            // Filter berdasarkan WBS, Nama Pesanan, dan Witel
            $filter->like('id_rso', 'ID OBL');
            $filter->like('nama_pesanan', 'Nama Pesanan');
            $filter->like('witel', 'Witel');
            $filter->equal('status_delivery', 'Status Delivery')->select([
                'Completed' => 'Completed',
                'Inprogress' => 'In Progress',
                'Issue' => 'Issue'
            ]);
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
        $show = new Show(projectsMgmt::findOrFail($id));

        // Menampilkan semua field dari tabel
        // $show->field('id', __('ID'));
        $show->field('wbs', __('WBS'));
        $show->field('nama_pesanan', __('Nama Pesanan'));
        $show->field('sow_singkat', __('SOW Singkat'));
        $show->field('id_rso', __('ID RSO'))->label('info');
        // $show->field('keterangan_konteks', __('Keterangan Konteks'));
        $show->field('skema', __('Skema'));
        $show->field('witel', __('Witel'));
        $show->field('po', __('PO'));
        $show->field('tipe', __('Tipe'));
        $show->field('mitra_penyedia', __('Mitra Penyedia'));
        $show->field('note_kick_off', __('Note Kick Off'));
        $show->field('tgl_kick_off', __('Tgl Kick Off'));
        $show->field('tgl_rfs', __('Tgl RFS'));
        $show->field('durasi_delivery', __('Durasi Delivery'));
        $show->field('tgl_selesai_layanan', __('Tgl Selesai Layanan'));
        $show->field('durasi_layanan', __('Durasi Layanan'));
        $show->field('status_dok_perikatan', __('Status Dok Perikatan'));
        $show->field('status_delivery', __('Status Delivery'))->unescape()->as(function ($status) { 
            // Tentukan warna label berdasarkan nilai status
            switch ($status) {
                case 'Completed':
                    $style = 'success'; // Hijau
                    break;
                case 'Inprogress':
                    $style = 'warning'; // Kuning
                    break;
                case 'Issue':
                    $style = 'danger';  // Merah
                    break;
                default:
                    $style = 'info';    // Biru (default)
            }

            // Kembalikan HTML untuk label tersebut
            return "<span class='badge bg-{$style}'>{$status}</span>";
        });
        $show->field('update_delivery', __('Update Delivery'));
        $show->field('no_kl_wo_sp', __('No KL/WO/SP'));
        $show->field('jenis_dok_delivery', __('Jenis Dok Delivery'));
        $show->field('kebutuhan_evidence', __('Kebutuhan Evidence'));
        $show->field('status_dok_delivery', __('Status Dok Delivery'));

        return $show;
    }

    /**
     * Membuat Form untuk tambah dan edit data.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new projectsMgmt());

        // Membuat field input untuk setiap kolom di tabel
        $form->text('wbs', __('WBS'));
        $form->textarea('nama_pesanan', __('Nama Pesanan'));
        $form->textarea('sow_singkat', __('SOW Singkat'));
        $form->select('id_rso', __('ID RSO'))->options(document::all()->pluck('full_doc', 'id_obl'));
        // $form->text('keterangan_konteks', __('Keterangan Konteks'));
        $form->text('skema', __('Skema'));
        $form->text('witel', __('Witel'));
        $form->text('po', __('PO'));
        $form->text('tipe', __('Tipe'));
        $form->text('mitra_penyedia', __('Mitra Penyedia'));
        $form->textarea('note_kick_off', __('Note Kick Off'));
        $form->date('tgl_kick_off', __('Tgl Kick Off'))->format('YYYY-MM-DD');
        $form->date('tgl_rfs', __('Tgl RFS'))->format('YYYY-MM-DD');
        $form->number('durasi_delivery', __('Durasi Delivery'));
        $form->date('tgl_selesai_layanan', __('Tgl Selesai Layanan'))->format('YYYY-MM-DD');
        $form->number('durasi_layanan', __('Durasi Layanan'));
        $form->textarea('status_dok_perikatan', __('Status Dok Perikatan'));
        
        $form->select('status_delivery', __('Status Delivery'))->options([
            'Completed' => 'Completed',
            'Inprogress' => 'In Progress',
            'Issue' => 'Issue',
        ]);
        
        $form->textarea('update_delivery', __('Update Delivery'));
        $form->text('no_kl_wo_sp', __('No KL/WO/SP'));
        $form->text('jenis_dok_delivery', __('Jenis Dok Delivery'));
        $form->textarea('kebutuhan_evidence', __('Kebutuhan Evidence'));
        $form->textarea('status_dok_delivery', __('Status Dok Delivery'));

        return $form;
    }
}
