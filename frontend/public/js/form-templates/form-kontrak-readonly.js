/**
 * Helper: Clean text - remove leading/trailing whitespace per line and overall
 * Handles all Unicode whitespace characters including non-breaking spaces
 */
function cleanText(value) {
    if (!value || value === null || value === undefined) return '-';

    // Convert to string
    let strValue = String(value);
    
    // Remove all types of Unicode whitespace from start and end
    // This includes: space, tab, non-breaking space (\u00A0), and other Unicode spaces
    strValue = strValue.replace(/^[\s\u00A0\u2000-\u200B\u2028\u2029\u202F\u205F\u3000]+|[\s\u00A0\u2000-\u200B\u2028\u2029\u202F\u205F\u3000]+$/g, '');
    
    if (strValue === '') return '-';

    // Split by newline, trim each line (including all Unicode whitespace), remove empty lines, then join
    const cleaned = strValue
        .split(/\r?\n/)
        .map(line => line.replace(/^[\s\u00A0\u2000-\u200B\u2028\u2029\u202F\u205F\u3000]+|[\s\u00A0\u2000-\u200B\u2028\u2029\u202F\u205F\u3000]+$/g, ''))
        .filter(line => line.length > 0)
        .join('\n');
    
    return cleaned || '-';
}

const FormTemplateKontrakReadOnly = {
    /**
     * Metadata
     */
    meta: {
        title: 'Data Kontrak Layanan',
        description: 'Hasil ekstraksi Ground Truth (Read-Only)',
    },

    /**
     * Main render function (READ-ONLY)
     */
    render: function (data, container) {
        container.innerHTML = '';

        const wrapper = document.createElement('div');
        wrapper.className = 'template-kontrak';

        // Section 1: Judul Project
        if (data.judul_project !== undefined) {
            wrapper.appendChild(this.createJudulProjectField(data.judul_project));
        }

        // Section 2: Nama Pelanggan
        if (data.nama_pelanggan !== undefined) {
            wrapper.appendChild(this.createNamaPelangganField(data.nama_pelanggan));
        }

        // Section 3: Nomor Kontrak (Table)
        if (data.nomor_surat_utama !== undefined || data.nomor_surat_lainnya !== undefined) {
            wrapper.appendChild(this.createNomorKontrakTable(data.nomor_surat_utama, data.nomor_surat_lainnya));
        }

        // Section 4: Tanggal (Table)
        if (this.hasTanggalData(data)) {
            wrapper.appendChild(this.createTanggalTable(data));
        }

        // Section 5: Detail Pembayaran (Table)
        if (this.hasPembayaranData(data)) {
            wrapper.appendChild(this.createDetailPembayaranTable(data));
        }

        // Section 6: Detail Rekening (Table)
        if (data.detail_rekening) {
            wrapper.appendChild(this.createDetailRekeningTable(data.detail_rekening));
        }

        // Section 7: Ketentuan Layanan (Table)
        if (data.slg !== undefined || data.skema_bisnis !== undefined) {
            wrapper.appendChild(this.createKetentuanLayananTable(data.slg, data.skema_bisnis));
        }

        // Section 8: Rujukan (Table - Read Only)
        if (data.rujukan && Object.keys(data.rujukan).length > 0) {
            wrapper.appendChild(this.createRujukanSection(data.rujukan));
        }

        // Section 8b: Data BAST (NOMOR MITRA, NOMOR TELKOM, TANGGAL BAST)
        const bastSection = this.createBastDataSection(data);
        if (bastSection) {
            wrapper.appendChild(bastSection);
        }

        // Section 9: Pejabat Penanda Tangan (Sections)
        // Always show so BAST/other doc-only views still display the section; use empty object when missing
        wrapper.appendChild(this.createPejabatSection(data.pejabat_penanda_tangan || {}));

        container.appendChild(wrapper);
    },

    /**
     * Helper: Check if tanggal data exists
     */
    hasTanggalData: function (data) {
        return data.delivery || data.delivery_date || data.tanggal_kontrak ||
            (data.jangka_waktu && (data.jangka_waktu.start_date || data.jangka_waktu.end_date || data.jangka_waktu.duration));
    },

    /**
     * Helper: Check if pembayaran data exists
     */
    hasPembayaranData: function (data) {
        return data.dpp_raw !== undefined || data.harga_satuan_raw !== undefined ||
            data.metode_pembayaran !== undefined || data.terms_of_payment !== undefined;
    },

    /**
     * Create Judul Project field (READ-ONLY)
     */
    createJudulProjectField: function (value) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';
        const cleanValue = cleanText(value);

        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'kontrak-field';

        const label = document.createElement('label');
        label.className = 'kontrak-label';
        label.textContent = 'Judul Project';

        const helpText = document.createElement('small');
        helpText.className = 'kontrak-help-text';
        helpText.textContent = 'Nama Lengkap Proyek atau Kontrak Layanan';

        const inputDiv = document.createElement('div');
        inputDiv.className = 'kontrak-input';
        inputDiv.style.cssText = 'background: #f9fafb; cursor: default; min-height: 3rem; white-space: pre-wrap;';
        inputDiv.textContent = cleanValue; // Use textContent to avoid whitespace issues

        fieldDiv.appendChild(label);
        fieldDiv.appendChild(helpText);
        fieldDiv.appendChild(inputDiv);
        section.appendChild(fieldDiv);

        return section;
    },

    /**
     * Create Nama Pelanggan field (READ-ONLY)
     */
    createNamaPelangganField: function (value) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';
        const cleanValue = cleanText(value);

        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'kontrak-field';

        const label = document.createElement('label');
        label.className = 'kontrak-label';
        label.textContent = 'Nama Pelanggan';

        const helpText = document.createElement('small');
        helpText.className = 'kontrak-help-text';
        helpText.textContent = 'Nama Instansi atau Perusahaan Pelanggan';

        const inputDiv = document.createElement('div');
        inputDiv.className = 'kontrak-input';
        inputDiv.style.cssText = 'background: #f9fafb; cursor: default; min-height: 3rem; white-space: pre-wrap;';
        inputDiv.textContent = cleanValue; // Use textContent to avoid whitespace issues

        fieldDiv.appendChild(label);
        fieldDiv.appendChild(helpText);
        fieldDiv.appendChild(inputDiv);
        section.appendChild(fieldDiv);

        return section;
    },

    /**
     * Create Nomor Kontrak table (READ-ONLY)
     */
    createNomorKontrakTable: function (utama, lainnya) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';

        const cleanUtama = cleanText(utama);
        const cleanLainnya = cleanText(lainnya);

        section.innerHTML = `
            <div class="kontrak-field">
                <label class="kontrak-label">Nomor Kontrak</label>
                <small class="kontrak-help-text">Nomor Surat Kontrak Utama dan Tambahan (Jika Ada)</small>
                <div class="kontrak-table-wrapper">
                    <table class="kontrak-table kontrak-table-static">
                        <tbody>
                            <tr>
                                <td class="kontrak-table-label">Utama</td>
                                <td>
                                    <div class="kontrak-table-input" style="background: #f9fafb; cursor: default;">
                                        ${cleanUtama}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="kontrak-table-label">Lainnya</td>
                                <td>
                                    <div class="kontrak-table-input" style="background: #f9fafb; cursor: default;">
                                        ${cleanLainnya}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        return section;
    },

    /**
     * Create Tanggal table (READ-ONLY)
     */
    createTanggalTable: function (data) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';

        if (!data || typeof data !== 'object') data = {};
        var jw = (data.jangka_waktu && typeof data.jangka_waktu === 'object') ? data.jangka_waktu : {};
        const deliveryValue = cleanText(data.delivery_date || data.delivery);
        const tanggalKontrakVal = data.tanggal_kontrak || '';
        const tanggalKontrak = cleanText(tanggalKontrakVal);
        var startDateRaw = jw.start_date || jw.startDate || data.start_date || data.startDate || data['start date'] || '';
        var endDateRaw = jw.end_date || jw.endDate || data.end_date || data.endDate || data['end date'] || '';
        if (!startDateRaw && tanggalKontrakVal) startDateRaw = tanggalKontrakVal;
        if (!endDateRaw && (data.delivery_date || data.delivery)) endDateRaw = data.delivery_date || data.delivery;
        const startDate = cleanText(startDateRaw);
        const endDate = cleanText(endDateRaw);
        const duration = cleanText(jw.duration || data.duration);

        section.innerHTML = `
        <div class="kontrak-field">
            <label class="kontrak-label">Tanggal</label>
            <small class="kontrak-help-text">Tanggal-Tanggal Penting Dalam Kontrak</small>
            
            <div class="kontrak-table-wrapper">
                <table class="kontrak-table kontrak-table-static">
                    <tbody>
                        <tr>
                            <td class="kontrak-table-label">Delivery</td>
                            <td>
                                <div class="kontrak-table-input" style="background: #f9fafb; cursor: default;">
                                    ${deliveryValue}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="kontrak-table-label">Jangka Waktu (Awal)</td>
                            <td>
                                <div class="kontrak-table-input" style="background: #f9fafb; cursor: default;">
                                    ${startDate}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="kontrak-table-label">Jangka Waktu (Akhir)</td>
                            <td>
                                <div class="kontrak-table-input" style="background: #f9fafb; cursor: default;">
                                    ${endDate}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="kontrak-table-label">Durasi</td>
                            <td>
                                <div class="kontrak-table-input" style="background: #f9fafb; cursor: default;">
                                    ${duration}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="kontrak-table-label">Kontrak</td>
                            <td>
                                <div class="kontrak-table-input" style="background: #f9fafb; cursor: default;">
                                    ${tanggalKontrak}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    `;

        return section;
    },

    /**
     * Create Detail Pembayaran table (READ-ONLY)
     */
    createDetailPembayaranTable: function (data) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';

        const formatRupiah = (value) => {
            if (!value && value !== 0) return '-';
            return 'Rp ' + value.toLocaleString('id-ID');
        };

        const cleanMetode = cleanText(data.metode_pembayaran);
        const cleanTerms = cleanText(data.terms_of_payment);

        section.innerHTML = `
            <div class="kontrak-field">
                <label class="kontrak-label">Detail Pembayaran</label>
                <small class="kontrak-help-text">Informasi Nilai dan Metode Pembayaran</small>
                
                <div class="kontrak-table-wrapper">
                    <table class="kontrak-table kontrak-table-static">
                        <tbody>
                            <tr>
                                <td class="kontrak-table-label">DPP</td>
                                <td>
                                    <div class="kontrak-table-input" style="background: #f9fafb; cursor: default;">
                                        ${formatRupiah(data.dpp_raw)}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="kontrak-table-label">Bulanan</td>
                                <td>
                                    <div class="kontrak-table-input" style="background: #f9fafb; cursor: default;">
                                        ${formatRupiah(data.harga_satuan_raw)}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="kontrak-table-label">Metode</td>
                                <td>
                                    <div class="kontrak-table-input" style="background: #f9fafb; cursor: default;">
                                        ${cleanMetode}
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="kontrak-table-label">Term of Payment</td>
                                <td>
                                    <div class="kontrak-table-input" style="background: #f9fafb; cursor: default;">
                                        ${cleanTerms}
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        return section;
    },

    /**
     * Create Detail Rekening table (READ-ONLY)
     */
    createDetailRekeningTable: function (rekening) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';

        const data = rekening || {};

        // Clean all values to remove leading/trailing whitespace
        const cleanNomorRekening = cleanText(data.nomor_rekening);
        const cleanNamaBank = cleanText(data.nama_bank);
        const cleanAtasNama = cleanText(data.atas_nama);
        const cleanKantorCabang = cleanText(data.kantor_cabang);

        // Create structure using DOM methods to avoid template literal whitespace issues
        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'kontrak-field';

        const label = document.createElement('label');
        label.className = 'kontrak-label';
        label.textContent = 'Detail Rekening';

        const helpText = document.createElement('small');
        helpText.className = 'kontrak-help-text';
        helpText.textContent = 'Informasi Rekening Bank untuk Pembayaran';

        const tableWrapper = document.createElement('div');
        tableWrapper.className = 'kontrak-table-wrapper';

        const table = document.createElement('table');
        table.className = 'kontrak-table kontrak-table-static';

        const tbody = document.createElement('tbody');

        // Helper function to create table row
        const createRow = (labelText, value) => {
            const tr = document.createElement('tr');
            const labelTd = document.createElement('td');
            labelTd.className = 'kontrak-table-label';
            labelTd.textContent = labelText;

            const valueTd = document.createElement('td');
            const valueDiv = document.createElement('div');
            valueDiv.className = 'kontrak-table-input';
            valueDiv.style.cssText = 'background: #f9fafb; cursor: default;';
            if (labelText === 'Atas Nama' || labelText === 'Kantor Cabang') {
                valueDiv.style.cssText += ' min-height: 3rem; white-space: pre-wrap;';
            }
            valueDiv.textContent = value; // Use textContent to avoid whitespace issues

            valueTd.appendChild(valueDiv);
            tr.appendChild(labelTd);
            tr.appendChild(valueTd);
            return tr;
        };

        tbody.appendChild(createRow('Nomor Rekening', cleanNomorRekening));
        tbody.appendChild(createRow('Nama Bank', cleanNamaBank));
        tbody.appendChild(createRow('Atas Nama', cleanAtasNama));
        tbody.appendChild(createRow('Kantor Cabang', cleanKantorCabang));

        table.appendChild(tbody);
        tableWrapper.appendChild(table);

        fieldDiv.appendChild(label);
        fieldDiv.appendChild(helpText);
        fieldDiv.appendChild(tableWrapper);
        section.appendChild(fieldDiv);

        return section;
    },

    /**
     * Create Ketentuan Layanan table (READ-ONLY)
     */
    createKetentuanLayananTable: function (slg, skema) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';

        const cleanSlg = cleanText(slg);
        const cleanSkema = cleanText(skema);

        // Create structure using DOM methods to avoid template literal whitespace issues
        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'kontrak-field';

        const label = document.createElement('label');
        label.className = 'kontrak-label';
        label.textContent = 'Ketentuan Layanan';

        const helpText = document.createElement('small');
        helpText.className = 'kontrak-help-text';
        helpText.textContent = 'Service Level Guarantee dan Skema Bisnis';

        const tableWrapper = document.createElement('div');
        tableWrapper.className = 'kontrak-table-wrapper';

        const table = document.createElement('table');
        table.className = 'kontrak-table kontrak-table-static';

        const tbody = document.createElement('tbody');

        // Helper function to create table row
        const createRow = (labelText, value, isMultiline = false) => {
            const tr = document.createElement('tr');
            const labelTd = document.createElement('td');
            labelTd.className = 'kontrak-table-label';
            labelTd.textContent = labelText;

            const valueTd = document.createElement('td');
            const valueDiv = document.createElement('div');
            valueDiv.className = 'kontrak-table-input';
            valueDiv.style.cssText = 'background: #f9fafb; cursor: default;';
            if (isMultiline) {
                valueDiv.style.cssText += ' min-height: 3rem; white-space: pre-wrap;';
            }
            valueDiv.textContent = value; // Use textContent to avoid whitespace issues

            valueTd.appendChild(valueDiv);
            tr.appendChild(labelTd);
            tr.appendChild(valueTd);
            return tr;
        };

        tbody.appendChild(createRow('SLG', cleanSlg));
        tbody.appendChild(createRow('Skema Bisnis', cleanSkema, true));

        table.appendChild(tbody);
        tableWrapper.appendChild(table);

        fieldDiv.appendChild(label);
        fieldDiv.appendChild(helpText);
        fieldDiv.appendChild(tableWrapper);
        section.appendChild(fieldDiv);

        return section;
    },

    /**
     * Create Rujukan section (READ-ONLY - No Add/Delete buttons)
     */
    createRujukanSection: function (rujukanData) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';

        const headerDiv = document.createElement('div');
        headerDiv.innerHTML = `
            <label class="kontrak-label">Rujukan</label>
            <small class="kontrak-help-text">Dokumen atau Surat Rujukan Terkait Kontrak</small>
        `;

        const tableWrapper = document.createElement('div');
        tableWrapper.className = 'kontrak-table-wrapper';

        const table = document.createElement('table');
        table.className = 'kontrak-table';

        table.innerHTML = `
            <thead>
                <tr>
                    <th style="width: 60px;"></th>
                    <th>Rujukan</th>
                </tr>
            </thead>
            <tbody id="rujukan-tbody">
            </tbody>
        `;

        const tbody = table.querySelector('#rujukan-tbody');

        if (rujukanData && Object.keys(rujukanData).length > 0) {
            let index = 0;
            for (const [key, value] of Object.entries(rujukanData)) {
                tbody.appendChild(this.createRujukanRow(value, index));
                index++;
            }
        } else {
            tbody.innerHTML = `
                <tr class="kontrak-table-empty">
                    <td colspan="2">
                        <i class="fas fa-inbox"></i>
                        <p>Tidak ada rujukan</p>
                    </td>
                </tr>
            `;
        }

        tableWrapper.appendChild(table);
        section.appendChild(headerDiv);
        section.appendChild(tableWrapper);

        return section;
    },

    /**
     * Create single rujukan row (READ-ONLY)
     */
    createRujukanRow: function (value, index) {
        const tr = document.createElement('tr');
        tr.className = 'kontrak-table-row';

        // Clean the value to remove leading/trailing whitespace
        const cleanValue = cleanText(value);

        // Create table number cell
        const numTd = document.createElement('td');
        numTd.className = 'kontrak-table-number';
        numTd.textContent = index + 1;

        // Create content cell with div
        const contentTd = document.createElement('td');
        const contentDiv = document.createElement('div');
        contentDiv.className = 'kontrak-table-input';
        contentDiv.style.cssText = 'background: #f9fafb; cursor: default; min-height: 3rem; white-space: pre-wrap;';
        contentDiv.textContent = cleanValue; // Use textContent to avoid HTML parsing issues

        contentTd.appendChild(contentDiv);
        tr.appendChild(numTd);
        tr.appendChild(contentTd);

        return tr;
    },

    /**
     * Create Data BAST section (READ-ONLY): NOMOR MITRA, NOMOR TELKOM, TANGGAL BAST.
     * Accepts: data.BAST/data.bast (nested), data.nomor (mitra/telkom), or backend flat shape (nomor_mitra, nomor_telkom, tanggal_bast).
     */
    createBastDataSection: function (data) {
        const bast = data?.BAST || data?.bast;
        // Nested: bast.nomor; flat: data.nomor (mitra/telkom); backend: data.nomor_mitra, data.nomor_telkom
        const nomorFromNested = bast?.nomor;
        const nomorFromFlat = data?.nomor && (data.nomor.mitra || data.nomor.telkom) ? data.nomor : null;
        const hasFlatBackend = (data?.nomor_mitra !== undefined && data.nomor_mitra !== '' && data.nomor_mitra !== null) ||
            (data?.nomor_telkom !== undefined && data.nomor_telkom !== '' && data.nomor_telkom !== null);
        const nomor = nomorFromNested || nomorFromFlat || (hasFlatBackend ? { mitra: data.nomor_mitra, telkom: data.nomor_telkom } : undefined);
        const tanggalBast = bast?.tanggal_bast ?? data?.tanggal_bast;
        const hasNomor = nomor && (nomor.mitra || nomor.telkom || (nomor.mitra !== undefined && nomor.mitra !== '-') || (nomor.telkom !== undefined && nomor.telkom !== '-'));
        const hasTanggal = tanggalBast !== undefined && tanggalBast !== null && String(tanggalBast).trim() !== '';

        if (!hasNomor && !hasTanggal) return null;

        const section = document.createElement('div');
        section.className = 'kontrak-section';
        section.style.maxWidth = '100%';
        section.style.overflow = 'hidden';

        const cleanMitra = cleanText(nomor?.mitra);
        const cleanTelkom = cleanText(nomor?.telkom);
        const cleanDate = hasTanggal ? cleanText(String(tanggalBast).trim().split(/\r?\n/)[0] || '') : '';

        let html = `<div class="kontrak-field" style="max-width: 100%; overflow: hidden;"><label class="kontrak-label">Data BAST</label>`;

        if (hasNomor) {
            html += `
                <div class="kontrak-table-wrapper" style="margin-top: 0.5rem;">
                    <table class="kontrak-table kontrak-table-static">
                        <tbody>
                            <tr><td class="kontrak-table-label">NOMOR MITRA</td><td><div class="kontrak-input" style="background: #f9fafb; cursor: default; word-break: break-word; overflow-wrap: break-word;">${cleanMitra}</div></td></tr>
                            <tr><td class="kontrak-table-label">NOMOR TELKOM</td><td><div class="kontrak-input" style="background: #f9fafb; cursor: default; word-break: break-word; overflow-wrap: break-word;">${cleanTelkom}</div></td></tr>
                        </tbody>
                    </table>
                </div>`;
        }
        if (hasTanggal) {
            html += `<div style="margin-top: 0.5rem;"><label class="kontrak-pejabat-label">TANGGAL DOKUMEN BAST</label><div class="kontrak-input" style="background: #f9fafb; cursor: default; word-break: break-word;">${cleanDate}</div></div>`;
        }

        html += `</div>`;
        section.innerHTML = html;
        return section;
    },

    /**
     * Create Pejabat Penanda Tangan section (READ-ONLY)
     */
    createPejabatSection: function (pejabatData) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';
        section.style.maxWidth = '100%';
        section.style.overflow = 'hidden';

        const data = pejabatData || {};
        const documents = ['bast'];
        const docLabels = {
            'bast': 'BAST',
        };

        let html = `<div class="kontrak-field" style="max-width: 100%; overflow: hidden;"><label class="kontrak-label">Pejabat Penanda Tangan</label><small class="kontrak-help-text">Pejabat yang Menandatangani Dokumen</small>`;

        documents.forEach(doc => {
            const docData = data[doc] || data[doc.toUpperCase?.() || doc] || data[doc.toLowerCase?.() || doc] || {};
            const cleanTelkom = cleanText(docData.telkom);
            const cleanMitra = cleanText(docData.mitra);

            html += `<div class="kontrak-pejabat-section" style="max-width: 100%; overflow: hidden;"><div class="kontrak-pejabat-header">${docLabels[doc]}</div><div class="kontrak-pejabat-item" style="max-width: 100%; overflow: hidden;"><label class="kontrak-pejabat-label">Telkom</label><div class="kontrak-input" style="background: #f9fafb; cursor: default; min-height: 3rem; max-width: 100%; width: 100%; box-sizing: border-box; word-break: break-word; overflow-wrap: break-word; white-space: normal; text-align: left; display: block;">${cleanTelkom}</div></div><div class="kontrak-pejabat-item" style="max-width: 100%; overflow: hidden;"><label class="kontrak-pejabat-label">Mitra</label><div class="kontrak-input" style="background: #f9fafb; cursor: default; min-height: 3rem; max-width: 100%; width: 100%; box-sizing: border-box; word-break: break-word; overflow-wrap: break-word; white-space: normal; text-align: left; display: block;">${cleanMitra}</div></div></div>`;
        });

        html += `</div>`;
        section.innerHTML = html;

        return section;
    }
};