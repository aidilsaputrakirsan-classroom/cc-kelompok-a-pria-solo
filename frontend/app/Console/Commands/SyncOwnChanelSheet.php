<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\OwnChanel;
use Revolution\Google\Sheets\Facades\Sheets;
use Google\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncOwnChanelSheet extends Command
{
    protected $signature = 'sync:own-chanel';
    protected $description = 'Sync data from Google Sheet ALL DATA to 0_OwnChanel table';

    // Mapping Index Sheet -> Nama Kolom Database
    protected $columnMapping = [
        0  => 'order_status',
        1  => 'region_show',
        2  => 'cust_div',
        3  => 'cust_divisi',
        4  => 'li_milestone',
        5  => 'li_product_name',
        6  => 'li_sid',
        7  => 'li_status',
        8  => 'order_created_date',
        9  => 'order_subtype',
        10 => 'price_type_cd',
        11 => 'range_duration',
        12 => 'range_umur_status',
        13 => 'segment',
        14 => 'vendor_name',
        15 => 'x_cx_termin_flg',
        16 => 'cust_segmen',
        17 => 'li_id',
        18 => 'order_id',
        19 => 'accountnas',
        20 => 'action_cd',
        21 => 'agree_createdby_name',
        22 => 'agree_end_date',
        23 => 'agree_itemnum',
        24 => 'agree_name',
        25 => 'agree_start_date',
        26 => 'agree_type',
        27 => 'agreenum',
        28 => 'agreenum_parent',
        29 => 'agrrenum_master',
        30 => 'asset_integ_id',
        31 => 'bc_date',
        32 => 'bill_profile_id',
        33 => 'bill_region',
        34 => 'bill_witel',
        35 => 'billaccntname',
        36 => 'billaccntnum',
        37 => 'billing_type_cd',
        38 => 'currency',
        39 => 'cust_region',
        40 => 'cust_witel',
        41 => 'custaccntname',
        42 => 'custaccntnum',
        43 => 'disc_amt_rc',
        44 => 'discnt_amt',
        45 => 'li_bandwidth',
        46 => 'li_billcomdate',
        47 => 'li_billdate',
        48 => 'li_billing_start_date',
        49 => 'li_created_date',
        50 => 'li_milestone_date',
        51 => 'li_monthly_price',
        52 => 'li_otc_price',
        53 => 'li_payment_term',
        54 => 'li_status_date',
        55 => 'login',
        56 => 'nipnas',
        57 => 'number_of_records',
        58 => 'order_createdby_name',
        59 => 'order_id_copy',
        60 => 'order_status_date',
        61 => 'par_order_item_id',
        62 => 'prevorder',
        63 => 'product_activation_date',
        64 => 'quote_row_id',
        65 => 'rev_num',
        66 => 'segmen_data',
        67 => 'servaccntname',
        68 => 'servaccntnum',
        69 => 'service_region',
        70 => 'service_witel',
        71 => 'tanggal_proses',
        72 => 'tgl_bilcom_alt',
        73 => 'total_price',
        74 => 'total_price_copy',
        75 => 'umur_order',
        76 => 'umur_status_order',
        77 => 'updated_date_sheet',
        78 => 'kategori_product',
        79 => 'kategori_revenue',
        80 => 'nama_cust',
        81 => 'nama_project',
        82 => 'total_price_check', // INI YANG ERROR
        83 => 'no_kb',
        84 => 'segmen_check',
        85 => 'product',
        86 => 'order_type',
        87 => 'witel',
        88 => 'umur_order_check',
        89 => 'order_id_check',
        90 => 'bima_nossa',
        91 => 'uic',
        92 => 'close_wfm_bima',
        93 => 'indikasi_order',
        94 => 'keterangan',
        95 => 'keterangan_detail',
        96 => 'status_order_desc',
        97 => 'kategori_status',
        98 => 'umur_order_2',
        99 => 'komitmen',
        100 => 'ket_ebis',
        101 => 'kelompok_uic',
        102 => 'date_check_col',
        103 => 'status_vlookup',
        104 => 'milestone_vlookup',
        105 => 'cek',
    ];

    // Daftar kolom TANGGAL
    protected $dateColumns = [
        'order_created_date', 'agree_end_date', 'agree_start_date', 'bc_date',
        'li_billcomdate', 'li_billdate', 'li_billing_start_date', 'li_created_date',
        'li_milestone_date', 'li_status_date', 'order_status_date', 'product_activation_date',
        'tanggal_proses', 'tgl_bilcom_alt', 'updated_date_sheet'
    ];

    // --- BARU: Daftar kolom UANG/CURRENCY ---
    protected $currencyColumns = [
        'disc_amt_rc', 'discnt_amt', 'li_monthly_price', 'li_otc_price', 
        'total_price', 'total_price_copy', 'total_price_check'
    ];

    public function handle()
    {
        $this->info('Starting SyncOwnChanel...');
        
        try {
            // Setup Google Client (Manual Auth untuk mengatasi error permission)
            $serviceAccountPath = storage_path('projess-key-436012.json');
            if (!file_exists($serviceAccountPath)) {
                $this->error("File credentials.json tidak ditemukan.");
                return;
            }

            $client = new Client();
            $client->setAuthConfig($serviceAccountPath);
            $client->setScopes([\Google\Service\Sheets::SPREADSHEETS]);
            $service = new \Google\Service\Sheets($client);
            Sheets::setService($service);

            // Ambil Data
            $sheetId = '1LTo98ygnA9yAZstmBfLZc30IK9R6wT93XH2ITjlwoAs'; 
            $sheetName = 'ALL DATA';
            
            $rows = Sheets::spreadsheet($sheetId)->sheet($sheetName)->get();
            $header = $rows->pull(0); // Buang header

            $bar = $this->output->createProgressBar($rows->count());
            $bar->start();

            foreach ($rows as $row) {
                // Skip baris kosong / tanpa li_id
                if (empty($row) || !isset($row[17])) { 
                     $bar->advance(); continue;
                }

                $dataToSave = [];

                foreach ($this->columnMapping as $index => $dbColumn) {
                    $value = $row[$index] ?? null;

                    // 1. Handle Tanggal (NULL if empty)
                    if (in_array($dbColumn, $this->dateColumns)) {
                        $value = $this->parseDate($value);
                    }
                    
                    // 2. Handle Currency / Harga (Clean "Rp" & Format)
                    elseif (in_array($dbColumn, $this->currencyColumns)) {
                        $value = $this->parseCurrency($value);
                    }
                    
                    // 3. Handle String Kosong biasa -> Null (Optional, agar rapi)
                    elseif ($value === '') {
                        $value = null;
                    }

                    $dataToSave[$dbColumn] = $value;
                }

                OwnChanel::updateOrCreate(
                    [
                        'li_id'    => $dataToSave['li_id'], 
                        'order_id' => $dataToSave['order_id']
                    ], 
                    $dataToSave
                );

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info("Sync Completed!");

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error("OwnChanel Sync Error: " . $e->getMessage());
        }
    }

    /**
     * Convert " Rp 500.000,00 " -> 500000.00
     */
    private function parseCurrency($value)
    {
        if (is_null($value) || trim($value) === '') {
            return 0; // Atau return null, tergantung struktur DB (not nullable = 0)
        }

        // Jika sudah numeric murni (dari excel raw), langsung return
        if (is_numeric($value)) {
            return $value;
        }

        // 1. Hapus "Rp", Titik (ribuan), dan Spasi. Sisakan Angka, Koma, dan Minus
        $clean = preg_replace('/[^0-9,\-]/', '', $value);

        // 2. Ganti Koma (desimal Indo) menjadi Titik (desimal DB)
        $clean = str_replace(',', '.', $clean);

        return (float) $clean;
    }

    private function parseDate($dateString)
    {
        if (is_null($dateString) || trim($dateString) === '') {
            return null;
        }
        try {
            if (is_numeric($dateString)) {
                 return Carbon::create(1899, 12, 30)->addDays((float)$dateString)->toDateTimeString();
            }
            return Carbon::parse($dateString)->toDateTimeString();
        } catch (\Exception $e) {
            return null;
        }
    }
}