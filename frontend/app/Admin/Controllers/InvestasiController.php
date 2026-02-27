<?php

// namespace App\Http\Controllers;
namespace App\Admin\Controllers;

use OpenAdmin\Admin\Controllers\AdminController;
use OpenAdmin\Admin\Layout\Column;
use OpenAdmin\Admin\Layout\Content;
use OpenAdmin\Admin\Layout\Row;
use Illuminate\Http\Request;

class InvestasiController extends AdminController
{
    public function save(Request $request)
    {

        // Bersihkan angka dari titik dan koma
        $angkaFields = [
            'biaya_investasi',
            'pendapatan_per_bulan',
            'revenue_otc',
            'cost_obl_bulanan',
            'cost_obl_otc'
        ];
		
        foreach ($angkaFields as $field) {
            $request->merge([
                $field => str_replace(['.', ','], '', $request->$field),
            ]);
        }

        $request->validate([
            'nama_project' => 'required',
            'biaya_investasi' => 'required|numeric',
            'periode_bulan' => 'required|numeric',
            'pendapatan_per_bulan' => 'required|numeric',
            'revenue_otc' => 'required|numeric',
            'cost_obl_bulanan' => 'required|numeric',
            'cost_obl_otc' => 'required|numeric',
            'biaya_operasional' => 'required|numeric',
            'biaya_marketing' => 'required|numeric',
            'tingkat_diskonto' => 'required|numeric',
            'bad_debt' => 'required|numeric',
            'taxes' => 'required|numeric',
        ]);

        $request->merge([
            'biaya_investasi' => str_replace(['.', ','], '', $request->biaya_investasi),
            'pendapatan_per_bulan' => str_replace(['.', ','], '', $request->pendapatan_per_bulan),
            'revenue_otc' => str_replace(['.', ','], '', $request->revenue_otc),
            'cost_obl_bulanan' => str_replace(['.', ','], '', $request->cost_obl_bulanan),
            'cost_obl_otc' => str_replace(['.', ','], '', $request->cost_obl_otc),
        ]);

        $periode_bulan = $request->periode_bulan;
        $periode_tahun = $periode_bulan / 12;

        // Revenue dan cost
        $revenue_bulanan_total = $request->pendapatan_per_bulan * $periode_bulan;
        $revenue_total_bruto = $request->revenue_otc + $revenue_bulanan_total;

        $revenue_otc_akhir = $request->revenue_otc - $request->cost_obl_otc;
        $revenue_bulanan_akhir = $request->pendapatan_per_bulan - $request->cost_obl_bulanan;
        $revenue_total = ($revenue_bulanan_akhir * $periode_bulan) + $revenue_otc_akhir;

        $bad_debt = ($request->bad_debt / 100) * $revenue_total;

        $biaya_operasional_total = ($request->biaya_operasional / 100) * $request->biaya_investasi * $periode_tahun;
        $base_marketing = $revenue_bulanan_akhir * $periode_bulan;
        $biaya_marketing_total = ($request->biaya_marketing / 100) * $base_marketing;
        $biaya_opex_total = $biaya_operasional_total + $biaya_marketing_total;

        $ebitda = $revenue_total - $bad_debt - $biaya_opex_total;

        $bop_lakwas = 0.007 * $request->biaya_investasi;
        $biaya_capex = $request->biaya_investasi + $bop_lakwas;
        $depresiasi = $biaya_capex;
        $ebit = $ebitda - $depresiasi;
        $pajak = ($request->taxes / 100) * $ebit;
        $net_income = $ebit - $pajak;

        $bad_debt_otc = ($request->bad_debt / 100) * $revenue_otc_akhir;
        $opex_otc = ($request->biaya_marketing / 100) * $request->revenue_otc; // tidak ada biaya operasional di tahun ke-0

        $net_income_per_tahun = $net_income / ceil($periode_tahun);
        $depresiasi_per_tahun = $depresiasi / ceil($periode_tahun);


        // IRR & NPV
        $npv = $net_income + $depresiasi - $biaya_capex;


        // Buat list tahun dimulai dari tahun sekarang
        $tahun_awal = (int) now()->format('Y');
        $tahun_list = [];
        for ($i = 0; $i <= ceil($periode_tahun); $i++) {
            $tahun_list[] = "Tahun ke-{$i}";
        }

        // Inisialisasi array per tahun
        $revenue_list = [];
        $bad_debt_list = [];
        $opex_list = [];
        $ebitda_list = [];
        $depresiasi_list = [];
        $ebit_list = [];
        $pajak_list = [];
        $net_income_list = [];

        // Perhitungan tahun ke-0
        $revenue_list[0] = $revenue_otc_akhir;
        $bad_debt_list[0] = ($request->bad_debt / 100) * $revenue_list[0];
        $opex_list[0] = 0;
        $ebitda_list[0] = $revenue_list[0] - $bad_debt_list[0];
        $depresiasi_list[0] = 0;
        $ebit_list[0] = $ebitda_list[0];
        $pajak_list[0] = ($request->taxes / 100) * $ebit_list[0];
        $net_income_list[0] = $ebit_list[0] - $pajak_list[0];

        // EBITDA Tahun ke-0 = Revenue Tahun ke-0 - Bad Debt Tahun ke-0 - OPEX Tahun ke-0
        $ebitda_t0 = $revenue_list[0] - $bad_debt_list[0] - $opex_list[0];

        // EBIT Tahun ke-0 = EBITDA - 0 (belum ada depresiasi)
        $ebit_t0 = $ebitda_t0; // atau kalau kamu belum define $ebitda_t0, ganti dengan nilai yang sudah ada

        // Pajak Tahun ke-0 = % dari EBIT
        $pajak_t0 = ($request->taxes / 100) * $ebit_t0;

        // Net Income Tahun ke-0
        $net_income_t0 = $ebit_t0 - $pajak_t0;

        // Perhitungan tahun ke-1 sampai n
        for ($i = 1; $i <= ceil($periode_tahun); $i++) {
            $revenue_list[$i] = $revenue_bulanan_akhir * 12;
            $bad_debt_list[$i] = ($request->bad_debt / 100) * $revenue_list[$i];
            $opex_list[$i] = ($biaya_operasional_total + $biaya_marketing_total) / ceil($periode_tahun);
            $ebitda_list[$i] = $revenue_list[$i] - $bad_debt_list[$i] - $opex_list[$i];
            $depresiasi_list[$i] = $depresiasi / ceil($periode_tahun);
            $ebit_list[$i] = $ebitda_list[$i] - $depresiasi_list[$i];
            $pajak_list[$i] = ($request->taxes / 100) * $ebit_list[$i];
            $net_income_list[$i] = $ebit_list[$i] - $pajak_list[$i];
        }

        // Tambahkan bagian ini di bawahnya:
        $revenue_total_list = array_sum($revenue_list);
        $bad_debt_total = array_sum($bad_debt_list);
        $opex_total = array_sum($opex_list);
        $ebitda_total = array_sum($ebitda_list);
        $depresiasi_total = array_sum($depresiasi_list);
        $ebit_total = array_sum($ebit_list);
        $pajak_total = array_sum($pajak_list);
        $net_income_total = array_sum($net_income_list);

        // Proyeksi Profit Loss
        $proyeksi_profit_loss = [
            ['label' => 'Revenue', 'data' => $revenue_list],
            ['label' => 'Bad Debt', 'data' => $bad_debt_list],
            ['label' => 'OPEX', 'data' => $opex_list],
            ['label' => 'EBITDA', 'data' => $ebitda_list],
            ['label' => 'Depresiasi', 'data' => $depresiasi_list],
            ['label' => 'EBIT', 'data' => $ebit_list],
            ['label' => 'Pajak', 'data' => $pajak_list],
            ['label' => 'Net Income', 'data' => $net_income_list],
        ];

        // Proyeksi Cash Flow untuk tabel (per tahun)
        $total_cash_inflow_list = [$net_income_t0];
        $capex_list = [$biaya_capex];
        $net_cash_flow_list = [$net_income_t0 - $biaya_capex];
        $cum_cash_flow_list = [];
        $cum_cash_flow_list[0] = $net_income_list[0] - $biaya_capex;

        for ($i = 1; $i <= ceil($periode_tahun); $i++) {
            $total_cash_inflow_list[] = $net_income_list[$i] + $depresiasi_per_tahun;
            $capex_list[] = 0;
            $net_cash_flow_list[] = $net_income_list[$i] + $depresiasi_per_tahun;
            $cum_cash_flow_list[] = $cum_cash_flow_list[$i - 1] + $net_cash_flow_list[$i];
        }

        $net_income_bulanan = $net_cash_flow_list[$i-1]/12;

        // Kolom Jumlah (sum semua tahun)
        $cashflow_projection = [
            [
                'label' => 'Net Income',
                'data' => $net_income_list,
                'total' => array_sum($net_income_list)
            ],
            [
                'label' => 'Add Back Depresiasi',
                'data' => $depresiasi_list,
                'total' => array_sum($depresiasi_list)
            ],
            [
                'label' => 'TOTAL CASH INFLOW',
                'data' => $total_cash_inflow_list,
                'total' => array_sum($total_cash_inflow_list)
            ],
            [
                'label' => 'CAPEX',
                'data' => $capex_list,
                'total' => array_sum($capex_list)
            ],
            [
                'label' => 'Net Cash Flow',
                'data' => $net_cash_flow_list,
                'total' => array_sum($net_cash_flow_list)
            ],
            [
                'label' => 'Cum Cash Flow',
                'data' => $cum_cash_flow_list,
                'total' => array_sum($cum_cash_flow_list)
            ]
        ];

         // BET Period
        $cum_income = $net_income_t0-$biaya_capex;
        $bet_bulan=null;
        for ($bulan = 1; $bulan <= $periode_bulan; $bulan++) {
            $cum_income += $net_income_bulanan;
            if ($cum_income >= 0) {
                $bet_bulan = $bulan;
                break;
            }
        }
        if ($bulan>$periode_bulan){
            $bet_output = "Kontrak Kurang Panjang";
        }else{
            $bet_output = floor($bet_bulan / 12) . ' tahun ' . ($bet_bulan % 12) . ' bulan';
        }

        // Payback Period
        $cum_income = $net_income_t0-$biaya_capex;
        $pbb_bulan = null;

        for ($bulan = 1; $bulan <= $periode_bulan; $bulan++) {
            $cum_income += $net_income_bulanan;
            //echo $cum_income."<br>";
            if ($cum_income >= $biaya_capex) {
                $pbb_bulan = $bulan;
                break;
            }
        }

        $pbb_output2 = null;
        
        if ($bulan>$periode_bulan){
            $pbb_output = "Kontrak Kurang Panjang";
            $pbb_output2 = "Disarankan ".floor(($bet_bulan*2) / 12) . " tahun " . (($bet_bulan*2) % 12) . " bulan ( ".($bet_bulan*2)." bulan )";
        }else{
            $pbb_output = floor($pbb_bulan / 12) . ' tahun ' . ($pbb_bulan % 12) . ' bulan';
        }

        $payback_text = $pbb_output;
        $bet_text = $bet_output;
        $irr = $this->hitungIRR($net_cash_flow_list);

        session(['form_input' => $request->all()]);

		
		$content = new Content();

		return $content
			->title('Estimasi Hasil Analisa Kelayakan Investasi')
			->body(view('investasi.hasil',
			compact(
            'periode_bulan',
            'periode_tahun',
            'revenue_bulanan_total',
            'revenue_total_bruto',
            'revenue_otc_akhir',
            'revenue_bulanan_akhir',
            'revenue_total',
            'bad_debt',
            'biaya_operasional_total',
            'biaya_marketing_total',
            'biaya_opex_total',
            'ebitda',
            'depresiasi',
            'ebit',
            'pajak',
            'net_income',
            'tahun_list',
            'biaya_capex',
            'bop_lakwas',
            'npv',
            'irr',
            'payback_text',
            'bet_text',
            'proyeksi_profit_loss',
            'revenue_list',
            'bad_debt_list',
            'opex_list',
            'ebitda_list',
            'depresiasi_list',
            'ebit_list',
            'pajak_list',
            'net_income_list',
            'revenue_total',
            'bad_debt_total',
            'opex_total',
            'ebitda_total',
            'depresiasi_total',
            'ebit_total',
            'pbb_output2',
            'pajak_total',
            'net_income_total',
            'cashflow_projection',
            'cum_cash_flow_list'

        ))->with([
            'nama_project' => $request->nama_project,
            'biaya_investasi' => $request->biaya_investasi,
            'pendapatan_per_bulan' => $request->pendapatan_per_bulan,
            'revenue_otc' => $request->revenue_otc,
            'cost_obl_bulanan' => $request->cost_obl_bulanan,
            'cost_obl_otc' => $request->cost_obl_otc,
            'biaya_operasional' => $request->biaya_operasional,
            'biaya_marketing' => $request->biaya_marketing,
            'bad_debt_input' => $request->bad_debt,
            'taxes' => $request->taxes,
            'tingkat_diskonto' => $request->tingkat_diskonto,
            'status_npv' => $npv > 0 ? 'Layak' : 'Tidak Layak',
            'status_irr' => $irr >= 16 ? 'Layak' : 'Tidak Layak',
        ])
		);
    }


    // Tambahkan semua method hitungIRR, hitungNPV, hitungPayback, hitungBET, formatTahunBulan di bawah ini

    private function hitungIRR(array $cashFlows, float $guess = 0.1): ?float
    {
        $maxIterations = 1000;
        $precision = 1e-6;

        for ($i = 0; $i < $maxIterations; $i++) {
            $npv = 0;
            $derivative = 0;

            foreach ($cashFlows as $t => $cf) {
                $npv += $cf / pow(1 + $guess, $t);
                if ($t > 0) {
                    $derivative -= $t * $cf / pow(1 + $guess, $t + 1);
                }
            }

            // Cek jika NPV sudah cukup dekat dengan nol
            if (abs($npv) < $precision) {
                return round($guess * 100, 2); // dalam persen
            }

            // Hindari pembagian dengan nol
            if ($derivative == 0) {
                // return null;
				return round($guess * 100, 2);
            }

            // Newton-Raphson iteration
            $guess = $guess - ($npv / $derivative);
        }

        return null; // Jika tidak konvergen
    }

    public function form(Request $request)
    {
        $data = session('form_input', []);
        session()->forget('form_input');

		$content = new Content();

		return $content
			->title('Form Input Analisa Kelayakan Investasi')
			->body(view('investasi.form',
				['request' => (object) $data]
				));
    }
}