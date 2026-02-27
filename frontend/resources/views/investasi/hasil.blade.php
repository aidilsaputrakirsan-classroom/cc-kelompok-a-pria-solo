@extends('layouts.app')

@section('content')

@if ($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<table class="table table-bordered">
    <tr>
        <th>Nama Proyek</th>
        <td>{{ $nama_project }}</td>
    </tr>
    <tr>
        <th>Biaya Investasi (BoQ)</th>
        <td>Rp {{ number_format($biaya_investasi, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <th>Pendapatan per Bulan</th>
        <td>Rp {{ number_format($pendapatan_per_bulan, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <th>Periode</th>
        <td>{{ $periode_bulan }} bulan</td>
    </tr>
</table>

<h5 class="mt-4">Informasi Umum</h5>
<table class="table table-sm table-bordered">
    <tr class="table-light">
        <th class="fw-bold">Revenue (Bruto)</th>
        <td>
            Total: <strong>Rp {{ number_format($revenue_total_bruto, 0, ',', '.') }}</strong><br>
            OTC: Rp {{ number_format($revenue_otc, 0, ',', '.') }}<br>
            Bulanan: Rp {{ number_format($pendapatan_per_bulan, 0, ',', '.') }} x {{ $periode_bulan }} bulan =
            Rp {{ number_format($pendapatan_per_bulan * $periode_bulan, 0, ',', '.') }}
        </td>
    </tr>

    <tr class="table-light">
        <th class="fw-bold">Cost IBL</th>
        <td>
            Total: <strong>Rp {{ number_format($revenue_total, 0, ',', '.') }}</strong><br>
            OTC Akhir: Rp {{ number_format($revenue_otc_akhir, 0, ',', '.') }}<br>
            Bulanan Akhir: Rp {{ number_format($revenue_bulanan_akhir, 0, ',', '.') }} x {{ $periode_bulan }} =
            Rp {{ number_format($revenue_bulanan_akhir * $periode_bulan, 0, ',', '.') }}
        </td>
    </tr>

    <tr class="table-light">
        <th class="fw-bold">Cost OBL</th>
        <td>
            Total: <strong>Rp {{ number_format(($cost_obl_bulanan * $periode_bulan) + $cost_obl_otc, 0, ',', '.') }}</strong><br>
            OTC: Rp {{ number_format($cost_obl_otc, 0, ',', '.') }}<br>
            Bulanan: Rp {{ number_format($cost_obl_bulanan, 0, ',', '.') }} x {{ $periode_bulan }} =
            Rp {{ number_format($cost_obl_bulanan * $periode_bulan, 0, ',', '.') }}
        </td>
    </tr>

    <tr>
        <th>OPEX (Operasional + Marketing)</th>
        <td>
            {{ $biaya_operasional + $biaya_marketing }}% || (Rp {{ number_format($biaya_opex_total, 0, ',', '.') }})<br>
            <small class="text-muted">
                Biaya Marketing: {{ $biaya_marketing }}% (Rp {{ number_format($biaya_marketing_total, 0, ',', '.') }})<br>
                Biaya Operasional: {{ $biaya_operasional }}% (Rp {{ number_format($biaya_operasional_total, 0, ',', '.') }})
            </small>
        </td>
    </tr>
</table>

<h5 class="mt-4">Tabel Proyeksi Profit & Loss (Terformat)</h5>
<table class="table table-bordered mt-4">
    <thead>
        <tr>
            <th>Label</th>
            <th>Jumlah</th> <!-- Tambahan kolom -->
            @foreach ($tahun_list as $tahun)
                <th>Tahun ke-{{ $loop->index }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        <tr>
            <th>Revenue</th>
            <td>{{ number_format($revenue_total, 0, ',', '.') }}</td>
            @foreach ($revenue_list as $val)
                <td>{{ number_format($val, 0, ',', '.') }}</td>
            @endforeach
        </tr>
        <tr>
            <th>Bad Debt</th>
            <td>{{ number_format($bad_debt_total, 0, ',', '.') }}</td>
            @foreach ($bad_debt_list as $val)
                <td>{{ number_format($val, 0, ',', '.') }}</td>
            @endforeach
        </tr>
        <tr>
            <th>OPEX</th>
            <td>{{ number_format($opex_total, 0, ',', '.') }}</td>
            @foreach ($opex_list as $val)
                <td>{{ number_format($val, 0, ',', '.') }}</td>
            @endforeach
        </tr>
        <tr>
            <th>EBITDA</th>
            <td>{{ number_format($ebitda_total, 0, ',', '.') }}</td>
            @foreach ($ebitda_list as $val)
                <td>{{ number_format($val, 0, ',', '.') }}</td>
            @endforeach
        </tr>
        <tr>
            <th>Depresiasi</th>
            <td>{{ number_format($depresiasi_total, 0, ',', '.') }}</td>
            @foreach ($depresiasi_list as $val)
                <td>{{ number_format($val, 0, ',', '.') }}</td>
            @endforeach
        </tr>
        <tr>
            <th>EBIT</th>
            <td>{{ number_format($ebit_total, 0, ',', '.') }}</td>
            @foreach ($ebit_list as $val)
                <td>{{ number_format($val, 0, ',', '.') }}</td>
            @endforeach
        </tr>
        <tr>
            <th>Pajak</th>
            <td>{{ number_format($pajak_total, 0, ',', '.') }}</td>
            @foreach ($pajak_list as $val)
                <td>{{ number_format($val, 0, ',', '.') }}</td>
            @endforeach
        </tr>
        <tr>
            <th>Net Income</th>
            <td>{{ number_format($net_income_total, 0, ',', '.') }}</td>
            @foreach ($net_income_list as $val)
                <td>{{ number_format($val, 0, ',', '.') }}</td>
            @endforeach
        </tr>
    </tbody>
</table>


<h5 class="mt-4">Cash Flow Projection</h5>
<table class="table table-bordered">
    <thead class="table-light">
        <tr>
            <th>Label</th>
            <th class="fw-bold">Jumlah</th>
            @foreach ($tahun_list as $tahun)
                <th class="fw-bold">{{ $tahun }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($cashflow_projection as $row)
            <tr>
                <th>{{ $row['label'] }}</th>
                <td>{{ number_format($row['total'], 0, ',', '.') }}</td>
                @foreach ($row['data'] as $val)
                    <td>{{ is_numeric($val) ? number_format($val, 0, ',', '.') : $val }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>

<h5 class="mt-4">Feasibility Analysis</h5>
<table class="table table-bordered table-striped align-middle">
    <tr>
        <th>NPV</th>
        <td>
            Rp {{ number_format($npv, 0, ',', '.') }}
            <span class="badge {{ $status_npv == 'Layak' ? 'bg-success' : 'bg-danger' }}">
                {{ $status_npv }}
            </span>
        </td>
    </tr>
    <tr>
        <th>IRR</th>
        <td>
            {{ $irr }}%
            <span class="badge {{ $status_irr == 'Layak' ? 'bg-success' : 'bg-danger' }}">
                {{ $status_irr }}
            </span>
        </td>
    </tr>
    <tr class="{{ $payback_text == 'Kontrak Kurang Panjang' ? 'table-danger' : 'table-success' }}">
        <th>Payback Period</th>
        <td>
            <span class="badge {{ $payback_text == 'Kontrak Kurang Panjang' ? 'bg-danger' : 'bg-success' }}">
                {{ $payback_text }}
            </span>
            <span class="badge {{ $pbb_output2 == 'Kontrak Kurang Panjang' ? 'bg-danger' : 'bg-success' }}">
                {{ $pbb_output2 }}
            </span>
        </td>
    </tr>
    <tr class="{{ $bet_text == 'Kontrak Kurang Panjang' ? 'table-danger' : 'table-success' }}">
        <th>Break Even Time (BET)</th>
        <td>
            <span class="badge {{ $bet_text == 'Kontrak Kurang Panjang' ? 'bg-danger' : 'bg-success' }}">
                {{ $bet_text }}
            </span>
        </td>
    </tr>
</table>



<form method="GET" action="{{ url('/projess/investasi') }}">
    <button class="btn btn-secondary mt-3">← Kembali ke Form</button>
</form>


@endsection
