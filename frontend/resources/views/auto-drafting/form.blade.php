{{-- resources/views/auto-drafting/form.blade.php --}}

<style>
    :root {
        --primary-color: #1d1d1f;
        --primary-hover: #2d2d2f;
        --border-color: #e5e7eb;
        --text-primary: #111827;
        --text-secondary: #6b7280;
        --bg-light: #f6f6f6;
        --bg-white: #ffffff;
        --success-color: #10b981;
        --danger-color: #ef4444;
        --warning-color: #f59e0b;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
        background: var(--bg-light);
        color: var(--text-primary);
        line-height: 1.6;
    }

    .draft-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
        min-height: 100vh;
    }

    .modern-card {
        background: var(--bg-white);
        border-radius: 24px;
        box-shadow: var(--shadow-md);
        padding: 2rem;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(0, 0, 0, 0.03);
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid var(--primary-color);
    }

    .section-icon {
        background: var(--primary-color);
        color: white;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        font-size: 1.125rem;
    }

    .section-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary-color);
        margin: 0;
        flex: 1;
    }

    .obl-badge {
        position: absolute;
        top: 1.5rem;
        right: 1.5rem;
        background: #f5f5f7;
        color: var(--primary-color);
        padding: 0.5rem 1.25rem;
        border-radius: 24px;
        font-weight: 700;
        font-size: 0.875rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
    }

    .col-span-2 {
        grid-column: span 2;
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-group label {
        display: block;
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 0.5rem;
    }

    .modern-input {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        font-size: 0.9375rem;
        background: var(--bg-white);
    }

    .modern-input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(29, 29, 31, 0.1);
    }

    textarea.modern-input {
        resize: vertical;
        min-height: 100px;
    }

    .hint-text {
        font-size: 0.75rem;
        color: var(--text-secondary);
        margin-top: 0.375rem;
        font-style: italic;
    }

    .doc-section {
        background: var(--bg-white);
        border-radius: 16px;
        padding: 1.5rem;
        margin-top: 1.5rem;
        border: 1px solid var(--border-color);
    }

    .doc-title {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--primary-color);
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-modern {
        border: none;
        padding: 0.875rem 1.5rem;
        border-radius: 24px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.2s;
        color: white;
    }

    .btn-primary {
        background: var(--primary-color);
    }

    .btn-success {
        background: var(--success-color);
    }

    .btn-danger {
        background: var(--danger-color);
    }

    .btn-tender {
        background: var(--warning-color);
    }

    .btn-sm-add {
        background: var(--bg-light);
        color: var(--text-primary);
        padding: 0.5rem 1rem;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        cursor: pointer;
        margin-top: 0.5rem;
    }

    .btn-sm-remove {
        color: var(--danger-color);
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1.25rem;
        margin-left: 0.5rem;
    }

    .layanan-wrapper {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .layanan-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .mitra-section {
        background: var(--bg-white);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .mitra-header {
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid var(--border-color);
        font-size: 1rem;
    }

    .add-obl-card {
        background: transparent;
        border: 2px dashed var(--border-color);
        border-radius: 24px;
        padding: 3rem;
        text-align: center;
    }

    .hidden {
        display: none !important;
    }

    .download-grid {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }

    .btn-doc {
        background: white;
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        padding: 0.625rem 1.25rem;
        border-radius: 24px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-doc:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-2px);
    }

    .overlay-loading {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.98);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        flex-direction: column;
    }

    .overlay-loading.show {
        display: flex;
    }

    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid var(--primary-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin-bottom: 1rem;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<div id="loading-overlay" class="overlay-loading">
    <div class="loading-spinner"></div>
    <div class="loading-text">Sedang Memproses...</div>
</div>

<div class="draft-wrapper">
    <div id="drafting-form">
        <div class="modern-card">
            <div class="section-header">
                <div class="section-icon"><i class="fa fa-info"></i></div>
                <h3 class="section-title">Informasi General Project {{ $id_rso }}</h3>
                <button type="button" class="btn-modern btn-primary" onclick="saveToLocalStorage()">
                    <i class="fa fa-save"></i> Simpan Draft
                </button>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Judul P1</label>
                    <input type="text" id="gen_judul_p1" class="modern-input" placeholder="Masukkan judul P1">
                </div>
                <div class="form-group">
                    <label>Nomor P1</label>
                    <input type="text" id="gen_nomor_p1" class="modern-input" placeholder="Masukkan nomor P1">
                </div>
                <div class="form-group">
                    <label>Tanggal P1 (Start Date)</label>
                    <input type="date" id="master_date" class="modern-input" onchange="recalculateAllDates()">
                    <div class="hint-text">Mengubah ini akan mereset semua tanggal OBL</div>
                </div>
                <div class="form-group">
                    <label>Pelanggan</label>
                    <input type="text" id="gen_pelanggan" class="modern-input" placeholder="Nama pelanggan">
                </div>
            </div>
        </div>

        <div id="obl-container"></div>

        <div class="modern-card add-obl-card">
            <button type="button" class="btn-modern btn-success" onclick="addObl()">
                <i class="fa fa-plus-circle"></i> Tambah OBL Baru
            </button>
        </div>
    </div>
</div>

<template id="obl-template">
    <div class="modern-card obl-row" id="obl_row_{INDEX}" data-index="{INDEX}" style="position: relative;">
        <div class="obl-badge">ID: <span class="obl-id-span">{{ $id_rso }}_OBL_{INDEX_DISPLAY}</span></div>

        <div class="section-header">
            <div class="section-icon"><i class="fa fa-file-text-o"></i></div>
            <h3 class="section-title">Data OBL {INDEX_DISPLAY}</h3>
        </div>

        <div class="form-group"
            style="margin-bottom: 1.5rem; padding: 1.25rem; border: 1px solid var(--border-color); border-radius: 16px; background: #fafafa;">
            <label>Daftar Layanan</label>
            <div class="layanan-wrapper" id="layanan-wrapper-{INDEX}"></div>
            <button type="button" class="btn-sm-add" onclick="addServiceInput({INDEX})">
                <i class="fa fa-plus"></i> Tambah Layanan
            </button>
        </div>

        <div class="doc-section" style="margin-top: 0; margin-bottom: 1.5rem;">
            <div class="doc-title"><i class="fa fa-cog"></i> Konfigurasi Pembayaran & Durasi</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Terms of Payment</label>
                    <select class="modern-input inp-top" onchange="updateAllPricesVisibility({INDEX})">
                        <option value="Bulanan">Bulanan</option>
                        <option value="OTC">OTC</option>
                        <option value="Termin">Termin</option>
                        <option value="Bulanan dan OTC">Bulanan dan OTC</option>
                        <option value="Bulanan dan Termin">Bulanan dan Termin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mulai Pekerjaan</label>
                    <input type="date" class="modern-input date-start" onchange="calculateDuration({INDEX})">
                </div>
                <div class="form-group">
                    <label>Akhir Pekerjaan</label>
                    <input type="date" class="modern-input date-end" onchange="calculateDuration({INDEX})">
                </div>
                <div class="form-group">
                    <label>Durasi Project</label>
                    <input type="text" class="modern-input input-duration" placeholder="Otomatis..." readonly>
                </div>
            </div>
        </div>

        <div class="mitra-section">
            <div class="mitra-header">MITRA 1 (Utama)</div>
            <div class="form-grid">
                <div class="form-group col-span-2">
                    <label>Nama Mitra 1</label>
                    <select class="modern-input mitra-selector-dynamic inp-mitra-1"
                        onchange="fetchAddress(this, 'inp-alamat-1')">
                        <option value="">Loading...</option>
                    </select>
                </div>
                <div class="form-group col-span-2">
                    <label>Alamat Mitra 1</label>
                    <textarea class="modern-input inp-alamat-1"></textarea>
                </div>
                <div class="form-group">
                    <label>Nomor SPH</label>
                    <input type="text" class="modern-input inp-nosph-1">
                </div>
                <div class="form-group">
                    <label>Tanggal SPH</label>
                    <input type="date" class="modern-input date-sph inp-tglsph-1">
                </div>
                <div class="form-group group-bulanan">
                    <label>Harga Bulanan</label>
                    <input type="text" class="modern-input inp-harga-bulanan-1 money-mask" placeholder="Rp 0">
                </div>
                <div class="form-group group-otc">
                    <label class="label-otc">Harga OTC/Termin</label>
                    <input type="text" class="modern-input inp-harga-otc-1 money-mask" placeholder="Rp 0">
                </div>
                <div class="form-group">
                    <label>Total Penawaran</label>
                    <input type="text" class="modern-input inp-harga-total-1 money-mask" placeholder="Rp 0">
                </div>
            </div>

            <div id="btn-tender-{INDEX}" style="margin-top: 1.5rem; border-top: 1px dashed #ccc; padding-top: 1rem;">
                <button type="button" class="btn-modern btn-tender" onclick="enableTender({INDEX})">
                    <i class="fa fa-users"></i> Aktifkan Mode Tender
                </button>
            </div>
        </div>

        <div id="section-mitra-2-{INDEX}" class="hidden mitra-section">
            <div class="mitra-header" style="color: var(--warning-color);">MITRA 2 (Pembanding)</div>
            <div class="form-grid">
                <div class="form-group col-span-2">
                    <label>Nama Mitra 2</label>
                    <select class="modern-input mitra-selector-dynamic inp-mitra-2"
                        onchange="fetchAddress(this, 'inp-alamat-2')">
                        <option value="">Loading...</option>
                    </select>
                </div>
                <div class="form-group col-span-2">
                    <label>Alamat Mitra 2</label>
                    <textarea class="modern-input inp-alamat-2"></textarea>
                </div>
                <div class="form-group">
                    <label>Nomor SPH</label>
                    <input type="text" class="modern-input inp-nosph-2">
                </div>
                <div class="form-group">
                    <label>Tanggal SPH</label>
                    <input type="date" class="modern-input date-sph inp-tglsph-2">
                </div>
                <div class="form-group group-bulanan">
                    <label>Harga Bulanan</label>
                    <input type="text" class="modern-input inp-harga-bulanan-2 money-mask" placeholder="Rp 0">
                </div>
                <div class="form-group group-otc">
                    <label class="label-otc">Harga OTC/Termin</label>
                    <input type="text" class="modern-input inp-harga-otc-2 money-mask" placeholder="Rp 0">
                </div>
                <div class="form-group">
                    <label>Total Penawaran</label>
                    <input type="text" class="modern-input inp-harga-total-2 money-mask" placeholder="Rp 0">
                </div>
            </div>
        </div>

        <div class="doc-section">
            <div class="doc-title"><i class="fa fa-file"></i> P2</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Tanggal P2</label>
                    <input type="date" class="modern-input date-p2">
                </div>
            </div>
        </div>

        <div class="doc-section">
            <div class="doc-title"><i class="fa fa-file"></i> P3</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nomor P3 (Mitra 1)</label>
                    <input type="text" class="modern-input inp-nop3-1">
                </div>
                <div class="form-group">
                    <label>Tanggal P3 (Mitra 1)</label>
                    <input type="date" class="modern-input date-p3 inp-tglp3-1">
                </div>
            </div>
            <div id="p3-mitra2-fields-{INDEX}" class="form-grid hidden"
                style="margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #eee;">
                <div class="form-group">
                    <label>Nomor P3 (Mitra 2)</label>
                    <input type="text" class="modern-input inp-nop3-2">
                </div>
                <div class="form-group">
                    <label>Tanggal P3 (Mitra 2)</label>
                    <input type="date" class="modern-input date-p3 inp-tglp3-2">
                </div>
            </div>
        </div>

        <div class="doc-section">
            <div class="doc-title"><i class="fa fa-file"></i> P4</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Tanggal P4</label>
                    <input type="date" class="modern-input date-p4">
                </div>
                <div class="form-group">
                    <label>Tanggal Delivery</label>
                    <input type="date" class="modern-input inp-target-delivery">
                </div>
                <div class="form-group">
                    <label>Skema Bisnis</label>
                    <select class="modern-input inp-skema">
                        <option value="Sewa Murni">Sewa Murni</option>
                        <option value="Beli Putus">Beli Putus</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>SLG (%)</label>
                    <input type="number" class="modern-input inp-slg" placeholder="99" min="0" max="100" oninput="if(this.value > 100) this.value = 100; if(this.value < 0) this.value = 0;">
                </div>
            </div>
        </div>

        <div class="doc-section">
            <div class="doc-title"><i class="fa fa-file"></i> P5</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Tanggal P5</label>
                    <input type="date" class="modern-input date-p5">
                </div>
            </div>
        </div>

        <div class="doc-section">
            <div class="doc-title"><i class="fa fa-file"></i> P6</div>
            <div id="p6-mitra-selector-{INDEX}" class="hidden"
                style="margin-bottom: 1.5rem; padding: 1rem; background: #eef2ff; border-radius: 12px;">
                <label style="font-weight: bold; display: block; margin-bottom: 0.5rem;">Pilih Mitra Pemenang</label>
                <select class="modern-input inp-mitra-final" onchange="updateP6FinalPrices({INDEX})">
                    <option value="">-- Pilih --</option>
                    <option value="mitra_1" class="opt-mitra-1">Mitra 1</option>
                    <option value="mitra_2" class="opt-mitra-2">Mitra 2</option>
                </select>
            </div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Tanggal P6</label>
                    <input type="date" class="modern-input date-p6">
                </div>
                <div class="form-group group-bulanan">
                    <label>Harga Bulanan (Nego)</label>
                    <input type="text" class="modern-input inp-final-bulan money-mask">
                </div>
                <div class="form-group group-otc">
                    <label class="label-otc">Harga OTC/Termin (Nego)</label>
                    <input type="text" class="modern-input inp-final-otc money-mask">
                </div>
                <div class="form-group">
                    <label>Total Nilai Project</label>
                    <input type="text" class="modern-input inp-final-total money-mask">
                </div>
            </div>
        </div>

        <div class="doc-section">
            <div class="doc-title"><i class="fa fa-file"></i> P7</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>Nomor P7</label>
                    <input type="text" class="modern-input inp-nop7">
                </div>
                <div class="form-group">
                    <label>Tanggal P7</label>
                    <input type="date" class="modern-input date-p7">
                </div>
            </div>
        </div>

        <div class="download-grid">
            <button type="button" class="btn-doc" onclick="generateDoc({INDEX}, 'P2')"><i
                    class="fa fa-file-word-o"></i> P2</button>
            <button type="button" class="btn-doc" onclick="generateDoc({INDEX}, 'P3')"><i
                    class="fa fa-file-word-o"></i> P3</button>
            <button type="button" class="btn-doc" onclick="generateDoc({INDEX}, 'P4')"><i
                    class="fa fa-file-word-o"></i> P4</button>
            <button type="button" class="btn-doc" onclick="generateDoc({INDEX}, 'P5')"><i
                    class="fa fa-file-word-o"></i> P5</button>
            <button type="button" class="btn-doc" onclick="generateDoc({INDEX}, 'P6-Nego')"><i
                    class="fa fa-file-word-o"></i> P6 Nego</button>
            <button type="button" class="btn-doc" onclick="generateDoc({INDEX}, 'P6-BestPrice')"><i
                    class="fa fa-file-word-o"></i> P6 Best</button>
            <button type="button" class="btn-doc" onclick="generateDoc({INDEX}, 'P7')"><i
                    class="fa fa-file-word-o"></i> P7</button>
        </div>

        <button type="button" class="btn-modern btn-danger" style="margin-top: 1.5rem; width: 100%;"
            onclick="removeObl({INDEX})">
            <i class="fa fa-trash"></i> Hapus OBL
        </button>
    </div>
</template>

<script>
    const currentRsoId = "{{ $id_rso }}";
    const storageKey = `draft_rso_${currentRsoId}`;
    const generateUrl = "{{ admin_url('rso/autodraft/generate') }}";
    const companyApiUrl = "{{ admin_url('api/companies') }}";
    const addressApiUrl = "{{ admin_url('api/company-address') }}";
    
    // Database API URLs
    const apiSaveUrl = "{{ admin_url('api/autodraft/save') }}";
    const apiGetUrl = "{{ admin_url('api/autodraft') }}/" + currentRsoId;
    const csrfToken = "{{ csrf_token() }}";

    window.cachedCompanies = [];
    window.oblIndex = 0;

    document.addEventListener("DOMContentLoaded", function() {
        // Load companies first, then load from database
        loadCompanies().then(() => {
            loadFromDatabase();
        }).catch(() => {
            // If company loading fails, still try to load from database
            loadFromDatabase();
        });

        document.body.addEventListener('input', function(e) {
            if (e.target.classList.contains('money-mask')) {
                let clean = e.target.value.replace(/[^0-9]/g, '');
                e.target.value = clean ? new Intl.NumberFormat('id-ID').format(clean) : '';
            }
        });
    });

    function formatMoneyInput(input) {
        let val = input.value.replace(/\./g, '').replace(/[^0-9]/g, '');
        input.value = val ? new Intl.NumberFormat('id-ID').format(val) : '';
    }

    function cleanMoney(val) {
        return val ? parseInt(val.replace(/\./g, '')) || 0 : 0;
    }

    window.updateAllPricesVisibility = function(idx) {
        const row = document.getElementById(`obl_row_${idx}`);
        if (!row) return;
        const topVal = row.querySelector('.inp-top').value.toLowerCase();
        const isBulanan = topVal.includes('bulanan');
        const isOtc = topVal.includes('otc') || topVal.includes('termin');
        row.querySelectorAll('.group-bulanan').forEach(el => el.classList.toggle('hidden', !isBulanan));
        row.querySelectorAll('.group-otc').forEach(el => el.classList.toggle('hidden', !isOtc));
    }

    window.updateP6FinalPrices = function(idx) {
        const row = document.getElementById(`obl_row_${idx}`);
        if (!row) return;
        const mitraFinal = row.querySelector('.inp-mitra-final').value;
        const hasMitra2 = row.querySelector('.inp-mitra-2').value !== "";
        let targetMitra = hasMitra2 && mitraFinal === 'mitra_2' ? '2' : '1';
        if (!mitraFinal) return;

        row.querySelector('.inp-final-bulan').value = row.querySelector(`.inp-harga-bulanan-${targetMitra}`).value;
        row.querySelector('.inp-final-otc').value = row.querySelector(`.inp-harga-otc-${targetMitra}`).value;
        row.querySelector('.inp-final-total').value = row.querySelector(`.inp-harga-total-${targetMitra}`).value;
    }

    window.addObl = function() {
        const tpl = document.getElementById('obl-template').innerHTML
            .replace(/{INDEX}/g, window.oblIndex)
            .replace(/{INDEX_DISPLAY}/g, window.oblIndex + 1);
        const div = document.createElement('div');
        div.innerHTML = tpl;
        document.getElementById('obl-container').appendChild(div.firstElementChild);
        addServiceInput(window.oblIndex);
        updateAllPricesVisibility(window.oblIndex);
        const row = document.getElementById('obl_row_' + window.oblIndex);
        row.querySelectorAll('.mitra-selector-dynamic').forEach(s => populateSelect(s, window.cachedCompanies));
        const mDate = document.getElementById('master_date').value;
        if (mDate) calculateDatesForIndex(window.oblIndex, mDate);
        window.oblIndex++;
    }

    window.removeObl = function(idx) {
        if (confirm('Hapus OBL?')) {
            document.getElementById('obl_row_' + idx).remove();
            saveToLocalStorage();
        }
    }

    window.enableTender = function(idx) {
        const row = document.getElementById(`obl_row_${idx}`);
        if (!row) return;

        document.getElementById(`section-mitra-2-${idx}`).classList.remove('hidden');
        document.getElementById(`p3-mitra2-fields-${idx}`).classList.remove('hidden');
        document.getElementById(`btn-tender-${idx}`).style.display = 'none';
        document.getElementById(`p6-mitra-selector-${idx}`).classList.remove('hidden');
        row.classList.add('is-tender');
        updateMitraFinalOptions(idx);
        updateAllPricesVisibility(idx);
    }

    window.calculateDuration = function(idx) {
        const row = document.getElementById('obl_row_' + idx);
        const startStr = row.querySelector('.date-start').value;
        const endStr = row.querySelector('.date-end').value;
        const output = row.querySelector('.input-duration');
        if (!startStr || !endStr) {
            output.value = "";
            return;
        }
        const d1 = new Date(startStr);
        const d2 = new Date(endStr);
        if (d2 < d1) {
            output.value = "Error";
            return;
        }
        
        // Calculate total months difference
        let months = (d2.getFullYear() - d1.getFullYear()) * 12 + (d2.getMonth() - d1.getMonth());
        
        // Create a temp date by adding months to start date
        let tempDate = new Date(d1);
        tempDate.setMonth(tempDate.getMonth() + months);
        
        // If tempDate is after d2, we've gone too far, reduce by 1 month
        if (tempDate > d2) {
            months--;
            tempDate = new Date(d1);
            tempDate.setMonth(tempDate.getMonth() + months);
        }
        
        // Calculate remaining days (inclusive of both start and end date of the remaining period)
        let diffTime = d2 - tempDate;
        let days = Math.round(diffTime / (1000 * 60 * 60 * 24));
        
        // If the remaining days complete a full month cycle (e.g., 1st to end of month)
        // Check if we're at the start of month and end date is last day of its month
        const isStartFirstOfMonth = d1.getDate() === 1;
        const isEndLastOfMonth = d2.getDate() === new Date(d2.getFullYear(), d2.getMonth() + 1, 0).getDate();
        
        // If start is 1st and end is last day of month, and days > 27, count as full month
        if (isStartFirstOfMonth && isEndLastOfMonth && days >= 28) {
            // Check if tempDate's day matches or we're spanning to end of month
            const tempLastDay = new Date(tempDate.getFullYear(), tempDate.getMonth() + 1, 0).getDate();
            if (tempDate.getDate() === 1 || days >= tempLastDay - 1) {
                months++;
                days = 0;
            }
        }
        
        // Build result string
        let result = "";
        if (months > 0) result += `${months} Bulan`;
        if (days > 0) {
            if (result) result += " ";
            result += `${days} Hari`;
        }
        if (!result) result = "0 Hari";
        output.value = result.trim();
    }

    function scrapeRowData(row) {
        const getVal = (sel) => {
            const el = row.querySelector(sel);
            if (!el) return '';
            // For select elements, get the selected option's text if value is empty
            if (el.tagName === 'SELECT' && el.selectedIndex >= 0) {
                const selectedOption = el.options[el.selectedIndex];
                return selectedOption ? (selectedOption.value || selectedOption.text || '') : '';
            }
            return el.value || '';
        };
        const getMoney = (sel) => cleanMoney(getVal(sel));
        const mitra2Input = row.querySelector('.inp-mitra-2');
        const hasMitra2 = mitra2Input && mitra2Input.value.trim() !== "";
        const serviceInputs = row.querySelectorAll('.obl-layanan-item');
        const services = Array.from(serviceInputs).map(i => i.value).filter(v => v !== "");
        
        // Get the OBL index from the row's data-index attribute (0-based) and convert to display (1-based)
        const oblIndex = parseInt(row.getAttribute('data-index') || '0', 10);
        const oblIndexDisplay = oblIndex + 1;

        return {
            obl_index: oblIndexDisplay,
            layanan: services,
            mitra_1: {
                nama: getVal('.inp-mitra-1'),
                alamat: getVal('.inp-alamat-1'),
                nomor_sph: getVal('.inp-nosph-1'),
                tanggal_sph: getVal('.inp-tglsph-1'),
                harga: getMoney('.inp-harga-total-1'),
                harga_bulanan: getMoney('.inp-harga-bulanan-1'),
                harga_otc: getMoney('.inp-harga-otc-1')
            },
            mitra_2: hasMitra2 ? {
                nama: getVal('.inp-mitra-2'),
                alamat: getVal('.inp-alamat-2'),
                nomor_sph: getVal('.inp-nosph-2'),
                tanggal_sph: getVal('.inp-tglsph-2'),
                harga: getMoney('.inp-harga-total-2'),
                harga_bulanan: getMoney('.inp-harga-bulanan-2'),
                harga_otc: getMoney('.inp-harga-otc-2')
            } : null,
            p2: {
                tanggal: getVal('.date-p2')
            },
            p3: {
                mitra_1: {
                    nomor: getVal('.inp-nop3-1'),
                    tanggal: getVal('.inp-tglp3-1')
                },
                mitra_2: hasMitra2 ? {
                    nomor: getVal('.inp-nop3-2'),
                    tanggal: getVal('.inp-tglp3-2')
                } : null
            },
            p4: {
                tanggal: getVal('.date-p4'),
                target: getVal('.inp-target-delivery'),
                top: getVal('.inp-top'),
                start: getVal('.date-start'),
                end: getVal('.date-end'),
                durasi: getVal('.input-duration'),
                skema: getVal('.inp-skema'),
                slg: getVal('.inp-slg')
            },
            p5: {
                tanggal: getVal('.date-p5'),
                terbilang: '',
                mode: hasMitra2 ? 'Tender' : 'Non-Tender'
            },
            p6: {
                harga_bulanan: getMoney('.inp-final-bulan'),
                harga_otc: getMoney('.inp-final-otc'),
                harga_total: getMoney('.inp-final-total'),
                skema: getVal('.inp-skema'),
                delivery: getVal('.date-p6'),
                slg: getVal('.inp-slg'),
                tanggal: getVal('.date-p6'),
                mitra_final: getVal('.inp-mitra-final')
            },
            p7: {
                nomor: getVal('.inp-nop7'),
                tanggal: getVal('.date-p7')
            }
        };
    }

    function gatherData() {
        const oblRows = document.querySelectorAll('.obl-row');
        const oblData = [];
        oblRows.forEach(row => oblData.push(scrapeRowData(row)));
        return {
            general: {
                id_rso: currentRsoId,
                judul_p1: document.getElementById('gen_judul_p1').value,
                nomor_p1: document.getElementById('gen_nomor_p1').value,
                tanggal_p1: document.getElementById('master_date').value,
                pelanggan: document.getElementById('gen_pelanggan').value,
            },
            obl: oblData
        };
    }

    window.addServiceInput = function(idx, value = '') {
        const wrapper = document.getElementById('layanan-wrapper-' + idx);
        const div = document.createElement('div');
        div.className = 'layanan-item';
        div.innerHTML =
            `<input type="text" class="modern-input obl-layanan-item" value="${value}" placeholder="Nama Layanan"><button type="button" class="btn-sm-remove" onclick="this.parentElement.remove()"><i class="fa fa-times"></i></button>`;
        wrapper.appendChild(div);
    }

    function loadCompanies() {
        return fetch(companyApiUrl).then(r => r.json()).then(data => {
            window.cachedCompanies = data;
            document.querySelectorAll('.mitra-selector-dynamic').forEach(s => populateSelect(s, data));
            return data;
        }).catch(err => console.error(err));
    }

    function populateSelect(select, data) {
        if (!select) return;
        const currentVal = select.value;
        select.innerHTML = '<option value="">-- Pilih Mitra --</option>';
        data.forEach(c => {
            let opt = document.createElement('option');
            opt.value = c.nama_perusahaan;
            opt.textContent = c.nama_perusahaan;
            select.appendChild(opt);
        });
        if (currentVal) select.value = currentVal;
    }

    window.fetchAddress = function(selectEl, targetClass) {
        const companyName = selectEl.value;
        const row = selectEl.closest('.obl-row');
        const targetEl = row.querySelector('.' + targetClass);
        if (!companyName) {
            targetEl.value = '';
            return;
        }
        targetEl.value = "Loading...";
        fetch(`${addressApiUrl}?name=${encodeURIComponent(companyName)}`).then(r => r.json()).then(data => {
            targetEl.value = data.address || '';
        }).catch(() => {
            targetEl.value = '';
        });
        const rowId = row.getAttribute('data-index');
        updateMitraFinalOptions(rowId);
    }

    window.updateMitraFinalOptions = function(idx) {
        const row = document.getElementById('obl_row_' + idx);
        if (!row) return;
        const namaMitra1 = row.querySelector('.inp-mitra-1').value || 'Mitra 1';
        const namaMitra2 = row.querySelector('.inp-mitra-2').value || 'Mitra 2';
        const opt1 = row.querySelector('.opt-mitra-1');
        const opt2 = row.querySelector('.opt-mitra-2');
        if (opt1) opt1.textContent = namaMitra1;
        if (opt2) opt2.textContent = namaMitra2;
    }

    window.addBusinessDays = function(dStr, days) {
        if (!dStr) return '';
        let d = new Date(dStr);
        let added = 0;
        while (added < days) {
            d.setDate(d.getDate() + 1);
            if (d.getDay() != 0 && d.getDay() != 6) added++;
        }
        return d.toISOString().split('T')[0];
    }

    window.calculateDatesForIndex = function(idx, start) {
        const row = document.getElementById('obl_row_' + idx);
        if (!row) return;
        const p2 = window.addBusinessDays(start, 1);
        const p3 = window.addBusinessDays(p2, 1);
        const p4 = window.addBusinessDays(p3, 1);
        const p5 = window.addBusinessDays(p4, 1);
        const p6 = window.addBusinessDays(p5, 1);
        const p7 = window.addBusinessDays(p6, 1);
        const sph = window.addBusinessDays(p4, 7);
        row.querySelector('.date-p2').value = p2;
        row.querySelectorAll('.date-p3').forEach(e => e.value = p3);
        row.querySelector('.date-p4').value = p4;
        row.querySelector('.date-p5').value = p5;
        row.querySelector('.date-p6').value = p6;
        row.querySelector('.date-p7').value = p7;
        row.querySelectorAll('.date-sph').forEach(e => e.value = sph);
    }

    window.recalculateAllDates = function() {
        const mDate = document.getElementById('master_date').value;
        if (mDate) {
            for (let i = 0; i < window.oblIndex; i++) {
                if (document.getElementById('obl_row_' + i)) calculateDatesForIndex(i, mDate);
            }
        }
    }

    function loadFromDatabase() {
        document.getElementById('loading-overlay').classList.add('show');
        document.querySelector('.loading-text').textContent = 'Memuat Data...';
        
        fetch(apiGetUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(result => {
            document.getElementById('loading-overlay').classList.remove('show');
            
            if (!result.success) {
                console.error('API Error:', result.message);
                addObl();
                return;
            }
            
            if (!result.exists || !result.data) {
                // No existing draft, create new OBL
                addObl();
                return;
            }
            
            const data = result.data;
            const gen = data.general || {};
            
            document.getElementById('gen_judul_p1').value = gen.judul_p1 || '';
            document.getElementById('gen_nomor_p1').value = gen.nomor_p1 || '';
            document.getElementById('master_date').value = gen.tanggal_p1 || '';
            document.getElementById('gen_pelanggan').value = gen.pelanggan || '';
            
            if (data.obl && data.obl.length > 0) {
                data.obl.forEach(oblData => {
                    const idx = window.oblIndex;
                    addObl();
                    setTimeout(() => fillOblRow(idx, oblData), 0);
                });
            } else {
                addObl();
            }
        })
        .catch(error => {
            document.getElementById('loading-overlay').classList.remove('show');
            console.error('Error loading from database:', error);
            // Fallback: try localStorage for backward compatibility
            loadFromLocalStorageFallback();
        });
    }
    
    // Fallback to localStorage for backward compatibility
    function loadFromLocalStorageFallback() {
        const raw = localStorage.getItem(storageKey);
        if (!raw) {
            addObl();
            return;
        }
        try {
            const data = JSON.parse(raw);
            const gen = data.general || {};
            document.getElementById('gen_judul_p1').value = gen.judul_p1 || '';
            document.getElementById('gen_nomor_p1').value = gen.nomor_p1 || '';
            document.getElementById('master_date').value = gen.tanggal_p1 || '';
            document.getElementById('gen_pelanggan').value = gen.pelanggan || '';
            if (data.obl && data.obl.length > 0) {
                data.obl.forEach(oblData => {
                    const idx = window.oblIndex;
                    addObl();
                    setTimeout(() => fillOblRow(idx, oblData), 0);
                });
            } else {
                addObl();
            }
        } catch (e) {
            addObl();
        }
    }

    function fillOblRow(idx, data) {
        const row = document.getElementById('obl_row_' + idx);
        if (!row) return;
        const setVal = (cls, val) => {
            const el = row.querySelector(cls);
            if (el) el.value = val || '';
        };
        const setMoney = (cls, val) => {
            const el = row.querySelector(cls);
            if (el) {
                el.value = val || '';
                formatMoneyInput(el);
            }
        };

        const wrapper = document.getElementById('layanan-wrapper-' + idx);
        wrapper.innerHTML = '';
        if (data.layanan) data.layanan.forEach(s => addServiceInput(idx, s));

        setVal('.inp-top', data.p4?.top);
        setVal('.date-start', data.p4?.start);
        setVal('.date-end', data.p4?.end);
        updateAllPricesVisibility(idx);
        if (data.p4?.start && data.p4?.end) window.calculateDuration(idx);

        setVal('.inp-mitra-1', data.mitra_1?.nama);
        setVal('.inp-alamat-1', data.mitra_1?.alamat);
        setVal('.inp-nosph-1', data.mitra_1?.nomor_sph);
        setVal('.inp-tglsph-1', data.mitra_1?.tanggal_sph);
        setMoney('.inp-harga-total-1', data.mitra_1?.harga);
        setMoney('.inp-harga-bulanan-1', data.mitra_1?.harga_bulanan);
        setMoney('.inp-harga-otc-1', data.mitra_1?.harga_otc);

        if (data.mitra_2 && data.mitra_2.nama) {
            enableTender(idx);
            setVal('.inp-mitra-2', data.mitra_2.nama);
            setVal('.inp-alamat-2', data.mitra_2.alamat);
            setVal('.inp-nosph-2', data.mitra_2.nomor_sph);
            setVal('.inp-tglsph-2', data.mitra_2.tanggal_sph);
            setMoney('.inp-harga-total-2', data.mitra_2?.harga);
            setMoney('.inp-harga-bulanan-2', data.mitra_2?.harga_bulanan);
            setMoney('.inp-harga-otc-2', data.mitra_2?.harga_otc);
        }

        setMoney('.inp-final-bulan', data.p6?.harga_bulanan);
        setMoney('.inp-final-otc', data.p6?.harga_otc);
        setMoney('.inp-final-total', data.p6?.harga_total);
        if (data.p6?.mitra_final) setVal('.inp-mitra-final', data.p6.mitra_final);

        setVal('.date-p2', data.p2?.tanggal);
        setVal('.inp-nop3-1', data.p3?.mitra_1?.nomor);
        setVal('.inp-tglp3-1', data.p3?.mitra_1?.tanggal);
        setVal('.inp-nop3-2', data.p3?.mitra_2?.nomor);
        setVal('.inp-tglp3-2', data.p3?.mitra_2?.tanggal);
        setVal('.date-p4', data.p4?.tanggal);
        setVal('.inp-target-delivery', data.p4?.target);
        setVal('.inp-skema', data.p4?.skema);
        setVal('.inp-slg', data.p4?.slg);
        setVal('.date-p5', data.p5?.tanggal);
        setVal('.date-p6', data.p6?.tanggal);
        setVal('.inp-nop7', data.p7?.nomor);
        setVal('.date-p7', data.p7?.tanggal);

        // Update mitra final options dengan nama mitra yang sebenarnya
        // Use setTimeout to ensure select values are properly set first
        setTimeout(() => {
            updateMitraFinalOptions(idx);
            updateP6FinalPrices(idx);
        }, 100);
    }

    window.saveToDatabase = function() {
        const data = gatherData();
        
        document.getElementById('loading-overlay').classList.add('show');
        document.querySelector('.loading-text').textContent = 'Menyimpan Draft...';
        
        fetch(apiSaveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            document.getElementById('loading-overlay').classList.remove('show');
            
            if (result.success) {
                // Also save to localStorage as backup
                data.timestamp = new Date().toISOString();
                localStorage.setItem(storageKey, JSON.stringify(data));
                
                alert('Draft Tersimpan!');
            } else {
                alert('Gagal menyimpan: ' + (result.message || 'Unknown error'));
            }
        })
        .catch(error => {
            document.getElementById('loading-overlay').classList.remove('show');
            console.error('Error saving to database:', error);
            
            // Fallback: save to localStorage
            data.timestamp = new Date().toISOString();
            localStorage.setItem(storageKey, JSON.stringify(data));
            alert('Draft Tersimpan ke Local Storage (Database tidak tersedia)');
        });
    }
    
    // Alias for backward compatibility
    window.saveToLocalStorage = window.saveToDatabase;

    window.generateDoc = function(idx, type) {
        const row = document.getElementById('obl_row_' + idx);
        if (!row) {
            alert("Data OBL tidak ditemukan");
            return;
        }
        const currentOblData = scrapeRowData(row);
        const generalInfo = gatherData().general;
        const payload = {
            doc_type: type,
            data: {
                general: generalInfo,
                obl: currentOblData
            }
        };

        document.getElementById('loading-overlay').classList.add('show');

        fetch(generateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        }).then(async response => {
            if (!response.ok) {
                const text = await response.text();
                try {
                    throw new Error(JSON.parse(text).message);
                } catch (e) {
                    console.error(text);
                    throw new Error("Server Error.");
                }
            }
            return response.blob();
        }).then(blob => {
            const hasMitra2 = currentOblData.mitra_2 && currentOblData.mitra_2.nama;
            const isZip = (type === 'P3' && hasMitra2);
            const ext = isZip ? 'zip' : 'docx';
            const idRso = (generalInfo.id_rso || 'ID').replace(/[^a-z0-9\s-]/gi, '').trim();
            const pelanggan = (generalInfo.pelanggan || 'Pelanggan').replace(/[^a-z0-9\s-]/gi, '').trim();

            let fileName = "";
            if (isZip) {
                fileName = `${idRso} [${type}] ${pelanggan}.${ext}`;
            } else {
                // Determine mitra name based on document type and selection
                let mitraName = '';
                
                if (['P2', 'P4', 'P5'].includes(type) && hasMitra2) {
                    // Tender mode for P2, P4, P5: use "TENDER"
                    mitraName = 'TENDER';
                } else if (['P6-Nego', 'P6-BestPrice', 'P7'].includes(type)) {
                    // P6 and P7: use selected mitra_final
                    const mitraFinal = currentOblData.p6?.mitra_final || 'mitra_1';
                    if (mitraFinal === 'mitra_2' && currentOblData.mitra_2?.nama) {
                        mitraName = currentOblData.mitra_2.nama;
                    } else {
                        mitraName = currentOblData.mitra_1?.nama || 'Mitra';
                    }
                } else {
                    // Default: use mitra_1
                    mitraName = currentOblData.mitra_1?.nama || 'Mitra';
                }
                
                mitraName = mitraName.replace(/[^a-z0-9\s-]/gi, '').trim();
                fileName = `${idRso} [${type}] ${pelanggan} - ${mitraName}.${ext}`;
            }

            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            a.remove();
            document.getElementById('loading-overlay').classList.remove('show');
        }).catch(err => {
            console.error(err);
            alert('Gagal: ' + err.message);
            document.getElementById('loading-overlay').classList.remove('show');
        });
    }
</script>
