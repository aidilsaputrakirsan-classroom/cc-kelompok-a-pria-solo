@extends('layouts.app')

@section('content')
<div class="container py-4">

    {{-- Tampilkan error jika validasi gagal --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('projess.investasi.store') }}">
        @csrf

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nama Proyek</label>
                <input type="text" name="nama_project" class="form-control" value="{{ $request->nama_project ?? '' }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Biaya Investasi (Rp)</label>
                <input type="text" name="biaya_investasi" class="form-control rupiah" value="{{ $request->biaya_investasi ?? '' }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Pendapatan Bulanan (Rp)</label>
                <input type="text" name="pendapatan_per_bulan" class="form-control rupiah" value="{{ $request->pendapatan_per_bulan ?? '' }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Revenue OTC (Rp)</label>
                <input type="text" name="revenue_otc" class="form-control rupiah" value="{{ $request->revenue_otc ?? '' }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Cost OBL Bulanan (Rp)</label>
                <input type="text" name="cost_obl_bulanan" class="form-control rupiah" value="{{ $request->cost_obl_bulanan ?? '' }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Cost OBL OTC (Rp)</label>
                <input type="text" name="cost_obl_otc" class="form-control rupiah" value="{{ $request->cost_obl_otc ?? '' }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Periode (bulan)</label>
                <input type="number" name="periode_bulan" class="form-control" value="{{ $request->periode_bulan ?? '' }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Biaya Operasional (%)</label>
                <input type="number" step="0.01" name="biaya_operasional" class="form-control" value="{{ old('biaya_operasional', 20) }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Biaya Marketing (%)</label>
                <input type="number" step="0.01" name="biaya_marketing" class="form-control" value="{{ old('biaya_marketing', 30) }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Bad Debt (%)</label>
                <input type="number" step="0.01" name="bad_debt" class="form-control" value="{{ old('bad_debt', 5) }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Taxes (%)</label>
                <input type="number" step="0.01" name="taxes" class="form-control" value="{{ old('taxes', 30) }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Tingkat Diskonto (%)</label>
                <input type="number" step="0.01" name="tingkat_diskonto" class="form-control" value="{{ old('tingkat_diskonto', 0) }}" required>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary">Hitung Analisa</button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    document.querySelectorAll('.rupiah').forEach(input => {
        input.addEventListener('input', function (e) {
            let cursorPosition = this.selectionStart;
            let value = this.value.replace(/\D/g, '');
            let formatted = new Intl.NumberFormat('id-ID').format(value);
            this.value = formatted;

            // Set cursor tetap di akhir
            this.setSelectionRange(this.value.length, this.value.length);
        });
    });
</script>
<script>
    function formatRupiahInput(input) {
        let value = input.value.replace(/\D/g, '');
        if (value) {
            input.value = new Intl.NumberFormat('id-ID').format(value);
        }
    }

    document.querySelectorAll('.rupiah').forEach(input => {
        // Format saat user mengetik
        input.addEventListener('input', function () {
            let cursorPosition = this.selectionStart;
            let value = this.value.replace(/\D/g, '');
            this.value = new Intl.NumberFormat('id-ID').format(value);
            this.setSelectionRange(this.value.length, this.value.length);
        });

        // Format ulang saat halaman pertama kali dimuat
        formatRupiahInput(input);
    });
</script>
@endsection