const FormTemplateKontrak = {
    escapeHtml: function (s) {
        if (s == null) return '';
        return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    },

    /**
     * Metadata
     */
    meta: {
        title: 'Data Kontrak Layanan',
        description: 'Fill in the correct contract information.',
    },

    /**
     * Main render function
     */
    render: function (data, container) {
        container.innerHTML = '';

        const wrapper = document.createElement('div');
        wrapper.className = 'template-kontrak';

        const isBastOnly = (data.nomor_mitra !== undefined || data.nomor_telkom !== undefined || data.tanggal_bast !== undefined) && data.judul_project === undefined;

        if (isBastOnly) {
            // BAST-only view (validate-ground-truth): only Data BAST (editable) + Pejabat (BAST only)
            wrapper.appendChild(this.createBastDataSectionEditable(data));
            wrapper.appendChild(this.createPejabatSection(data.pejabat_penanda_tangan || {}, { documentsOnly: ['bast'] }));
        } else {
            // Full kontrak form
            wrapper.appendChild(this.createJudulProjectField(data.judul_project));
            wrapper.appendChild(this.createNamaPelangganField(data.nama_pelanggan));
            wrapper.appendChild(this.createNomorKontrakTable(data.nomor_surat_utama, data.nomor_surat_lainnya));
            wrapper.appendChild(this.createTanggalTable(data));
            wrapper.appendChild(this.createDetailPembayaranTable(data));
            wrapper.appendChild(this.createDetailRekeningTable(data.detail_rekening));
            wrapper.appendChild(this.createKetentuanLayananTable(data.slg, data.skema_bisnis));
            wrapper.appendChild(this.createRujukanSection(data.rujukan));
            wrapper.appendChild(this.createPejabatSection(data.pejabat_penanda_tangan));
        }

        container.appendChild(wrapper);

        // Auto-resize all textareas
        setTimeout(() => this.initAutoResize(), 100);
        // Recalc Durasi when start/end date change (Option A: calendar months / days)
        setTimeout(() => this.attachTanggalDurationListeners(wrapper), 100);
    },

    /**
     * When start or end date changes, recalc Durasi (Option A) and set text input (user can still edit).
     */
    attachTanggalDurationListeners: function (container) {
        var self = this;
        var startEl = container.querySelector('#kontrak_start_date');
        var endEl = container.querySelector('#kontrak_end_date');
        var durationEl = container.querySelector('#kontrak_duration');
        if (!startEl || !endEl || !durationEl) return;

        function updateDuration() {
            var startYmd = startEl.value && startEl.value.trim();
            var endYmd = endEl.value && endEl.value.trim();
            if (!startYmd || !endYmd) return;
            var result = self.computeDurationFromDates(startYmd, endYmd);
            durationEl.value = result.value + ' ' + result.unit;
        }

        startEl.addEventListener('change', updateDuration);
        endEl.addEventListener('change', updateDuration);
    },

    /**
     * Helper: Convert date to yyyy-mm-dd format for input[type="date"]
     * Handles: "1 Januari 2025", "dd-mm-yyyy", "dd/mm/yyyy", "yyyy-mm-dd" (from DB/API).
     */
    convertDateToInput: function (dateStr) {
        if (dateStr == null) return '';
        const trimmed = String(dateStr).trim();
        if (!trimmed) return '';

        const indonesianDate = this.parseIndonesianDate(trimmed);
        if (indonesianDate) {
            const year = indonesianDate.getFullYear();
            const month = String(indonesianDate.getMonth() + 1).padStart(2, '0');
            const day = String(indonesianDate.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Support both dashes and slashes (dd-mm-yyyy, dd/mm/yyyy, yyyy-mm-dd, yyyy/mm/dd)
        const parts = trimmed.split(/[-/]/);
        if (parts.length === 3) {
            const [a, b, c] = parts;
            if (a.length === 4 && b.length <= 2 && c.length <= 2) {
                return a + '-' + String(b).padStart(2, '0') + '-' + String(c).padStart(2, '0');
            }
            if (c.length === 4) {
                return c + '-' + String(b).padStart(2, '0') + '-' + String(a).padStart(2, '0');
            }
        }
        return '';
    },

    /**
     * Helper: Parse Indonesian date format "1 Januari 2025" to Date object
     */
    parseIndonesianDate: function (dateStr) {
        const monthMap = {
            'januari': 0, 'februari': 1, 'maret': 2, 'april': 3,
            'mei': 4, 'juni': 5, 'juli': 6, 'agustus': 7,
            'september': 8, 'oktober': 9, 'november': 10, 'desember': 11
        };

        // Match pattern: "1 Januari 2025" or "01 Januari 2025"
        const match = dateStr.trim().match(/^(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})$/);
        if (!match) return null;

        const day = parseInt(match[1]);
        const monthName = match[2].toLowerCase();
        const year = parseInt(match[3]);

        const monthIndex = monthMap[monthName];
        if (monthIndex === undefined) return null;

        return new Date(year, monthIndex, day);
    },

    /**
     * Compute gap in days between two yyyy-mm-dd dates.
     */
    computeGapInDays: function (startYmd, endYmd) {
        if (!startYmd || !endYmd) return 0;
        var s = new Date(startYmd + 'T00:00:00');
        var e = new Date(endYmd + 'T00:00:00');
        if (isNaN(s.getTime()) || isNaN(e.getTime())) return 0;
        return Math.round((e - s) / 86400000);
    },

    /**
     * Compute calendar months between two yyyy-mm-dd dates (no fixed 30/31 days).
     */
    computeCalendarMonths: function (startYmd, endYmd) {
        if (!startYmd || !endYmd) return 0;
        var p1 = startYmd.split('-').map(Number);
        var p2 = endYmd.split('-').map(Number);
        if (p1.length !== 3 || p2.length !== 3) return 0;
        var y1 = p1[0], m1 = p1[1], d1 = p1[2];
        var y2 = p2[0], m2 = p2[1], d2 = p2[2];
        var months = (y2 - y1) * 12 + (m2 - m1);
        if (d2 < d1) months -= 1;
        return Math.max(0, months);
    },

    /**
     * Option A: from start/end dates, get duration as { value, unit }.
     * If gap < 45 days use "Hari", else use calendar "Bulan".
     */
    computeDurationFromDates: function (startYmd, endYmd, thresholdDays) {
        thresholdDays = thresholdDays != null ? thresholdDays : 45;
        var gapDays = this.computeGapInDays(startYmd, endYmd);
        if (gapDays < 0) return { value: 0, unit: 'Hari' };
        if (gapDays < thresholdDays) return { value: gapDays, unit: 'Hari' };
        return { value: this.computeCalendarMonths(startYmd, endYmd), unit: 'Bulan' };
    },

    /**
     * Parse duration string from DB: "24 Bulan" or "4 Hari" -> { value, unit }.
     */
    parseDurationFromDb: function (str) {
        if (!str || typeof str !== 'string') return { value: 0, unit: 'Bulan' };
        var m = str.trim().match(/^(\d+)\s*(Bulan|Hari)$/i);
        if (!m) {
            var num = str.match(/\d+/);
            return { value: num ? parseInt(num[0], 10) : 0, unit: 'Bulan' };
        }
        return { value: parseInt(m[1], 10), unit: m[2].charAt(0).toUpperCase() + m[2].slice(1).toLowerCase() };
    },

    /**
     * Helper: Convert date yyyy-mm-dd to dd-mm-yyyy
     */
    convertDateFromInput: function (dateStr) {
        if (!dateStr || typeof dateStr !== 'string') return '';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        const [year, month, day] = parts;
        return `${day}-${month}-${year}`;
    },

    /**
     * Auto-resize textareas
     */
    initAutoResize: function () {
        const textareas = document.querySelectorAll('.template-kontrak textarea');
        textareas.forEach(textarea => {
            this.autoResize(textarea);
            textarea.addEventListener('input', () => this.autoResize(textarea));
        });
    },

    autoResize: function (textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    },

    /**
     * Create Judul Project field
     */
    createJudulProjectField: function (value) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';
        section.innerHTML = `
            <div class="kontrak-field">
                <label class="kontrak-label">Judul Project</label>
                <small class="kontrak-help-text">Nama Lengkap Proyek atau Kontrak Layanan</small>
                <textarea class="kontrak-textarea" 
                          id="kontrak_judul_project" 
                          rows="2"
                          placeholder="Enter project title">${value || ''}</textarea>
            </div>
        `;
        return section;
    },

    /**
     * Create Nama Pelanggan field
     */
    createNamaPelangganField: function (value) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';
        section.innerHTML = `
            <div class="kontrak-field">
                <label class="kontrak-label">Nama Pelanggan</label>
                <small class="kontrak-help-text">Nama Instansi atau Perusahaan Pelanggan</small>
                <textarea class="kontrak-textarea" 
                          id="kontrak_nama_pelanggan" 
                          rows="2"
                          placeholder="Enter customer name">${value || ''}</textarea>
            </div>
        `;
        return section;
    },

    /**
     * Create Nomor Kontrak table
     */
    createNomorKontrakTable: function (utama, lainnya) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';

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
                                    <input type="text" 
                                           class="kontrak-table-input" 
                                           id="kontrak_nomor_utama"
                                           value="${utama || ''}"
                                           placeholder="Enter primary contract number">
                                </td>
                            </tr>
                            <tr>
                                <td class="kontrak-table-label">Lainnya</td>
                                <td>
                                    <input type="text" 
                                           class="kontrak-table-input" 
                                           id="kontrak_nomor_lainnya"
                                           value="${lainnya || ''}"
                                           placeholder="Enter secondary contract number">
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        `;

        return section;
    },

    createTanggalTable: function (data) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';

        if (!data || typeof data !== 'object') data = {};
        var jw = (data.jangka_waktu && typeof data.jangka_waktu === 'object') ? data.jangka_waktu : {};

        const deliveryValue = data.delivery_date || data.delivery || '';
        const delivery = this.convertDateToInput(deliveryValue);
        const tanggalKontrakValue = data.tanggal_kontrak || '';
        const tanggalKontrak = this.convertDateToInput(tanggalKontrakValue);
        // Jangka Waktu (Awal)/(Akhir): use jangka_waktu if present, else fallback to Kontrak date and Delivery date
        var startDateRaw = jw.start_date || jw.startDate || data.start_date || data.startDate || data['start date'] || '';
        var endDateRaw = jw.end_date || jw.endDate || data.end_date || data.endDate || data['end date'] || '';
        if (!startDateRaw && tanggalKontrakValue) startDateRaw = tanggalKontrakValue;
        if (!endDateRaw && deliveryValue) endDateRaw = deliveryValue;
        const startDate = this.convertDateToInput(startDateRaw);
        const endDate = this.convertDateToInput(endDateRaw);

        var durationValue = 0;
        var durationUnit = 'Bulan';
        if (startDate && endDate) {
            var computed = this.computeDurationFromDates(startDate, endDate);
            durationValue = computed.value;
            durationUnit = computed.unit;
        } else {
            var durationStr = jw.duration || data.duration;
            var parsed = this.parseDurationFromDb(durationStr);
            durationValue = parsed.value;
            durationUnit = parsed.unit;
        }
        var durationDisplay = (durationValue || 0) + ' ' + durationUnit;

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
                                <input type="date" 
                                       class="kontrak-table-input" 
                                       id="kontrak_delivery"
                                       value="${delivery}">
                            </td>
                        </tr>
                        <tr>
                            <td class="kontrak-table-label">Jangka Waktu (Awal)</td>
                            <td>
                                <input type="date" 
                                       class="kontrak-table-input" 
                                       id="kontrak_start_date"
                                       value="${startDate}">
                            </td>
                        </tr>
                        <tr>
                            <td class="kontrak-table-label">Jangka Waktu (Akhir)</td>
                            <td>
                                <input type="date" 
                                       class="kontrak-table-input" 
                                       id="kontrak_end_date"
                                       value="${endDate}">
                            </td>
                        </tr>
                        <tr>
                            <td class="kontrak-table-label">Durasi</td>
                            <td>
                                <input type="text" 
                                       class="kontrak-table-input" 
                                       id="kontrak_duration"
                                       value="${durationDisplay}"
                                       placeholder="e.g. 30 Hari or 1 Bulan">
                            </td>
                        </tr>
                        <tr>
                            <td class="kontrak-table-label">Kontrak</td>
                            <td>
                                <input type="date" 
                                       class="kontrak-table-input" 
                                       id="kontrak_tanggal_kontrak"
                                       value="${tanggalKontrak}">
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
     * Create Detail Pembayaran table
     */
    createDetailPembayaranTable: function (data) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';

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
                                    <input type="number" 
                                           class="kontrak-table-input" 
                                           id="kontrak_dpp"
                                           value="${data.dpp_raw || 0}"
                                           placeholder="0">
                                </td>
                            </tr>
                            <tr>
                                <td class="kontrak-table-label">Bulanan</td>
                                <td>
                                    <input type="number" 
                                           class="kontrak-table-input" 
                                           id="kontrak_harga_satuan"
                                           value="${data.harga_satuan_raw || 0}"
                                           placeholder="0">
                                </td>
                            </tr>
                            <tr>
                                <td class="kontrak-table-label">Metode</td>
                                <td>
                                    <input type="text" 
                                           class="kontrak-table-input" 
                                           id="kontrak_metode"
                                           value="${data.metode_pembayaran || ''}"
                                           placeholder="Enter payment method">
                                </td>
                            </tr>
                            <tr>
                                <td class="kontrak-table-label">Term of Payment</td>
                                <td>
                                    <input type="text" 
                                           class="kontrak-table-input" 
                                           id="kontrak_terms"
                                           value="${data.terms_of_payment || ''}"
                                           placeholder="Enter payment terms">
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
     * Create Detail Rekening table
     */
    createDetailRekeningTable: function (rekening) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';

        const data = rekening || {};

        section.innerHTML = `
            <div class="kontrak-field">
                <label class="kontrak-label">Detail Rekening</label>
                <small class="kontrak-help-text">Informasi Rekening Bank untuk Pembayaran</small>
                
                <div class="kontrak-table-wrapper">
                    <table class="kontrak-table kontrak-table-static">
                        <tbody>
                            <tr>
                                <td class="kontrak-table-label">Nomor Rekening</td>
                                <td>
                                    <input type="text" 
                                           class="kontrak-table-input" 
                                           id="kontrak_nomor_rekening"
                                           value="${data.nomor_rekening || ''}"
                                           placeholder="Enter account number">
                                </td>
                            </tr>
                            <tr>
                                <td class="kontrak-table-label">Nama Bank</td>
                                <td>
                                    <input type="text" 
                                           class="kontrak-table-input" 
                                           id="kontrak_nama_bank"
                                           value="${data.nama_bank || ''}"
                                           placeholder="Enter bank name">
                                </td>
                            </tr>
                            <tr>
                                <td class="kontrak-table-label">Atas Nama</td>
                                <td>
                                    <textarea class="kontrak-table-textarea" 
                                              id="kontrak_atas_nama" 
                                              rows="2"
                                              placeholder="Enter account holder name">${data.atas_nama || ''}</textarea>
                                </td>
                            </tr>
                            <tr>
                                <td class="kontrak-table-label">Kantor Cabang</td>
                                <td>
                                    <textarea class="kontrak-table-textarea" 
                                              id="kontrak_kantor_cabang" 
                                              rows="2"
                                              placeholder="Enter branch office">${data.kantor_cabang || ''}</textarea>
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
     * Create Ketentuan Layanan table
     */
    createKetentuanLayananTable: function (slg, skema) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';

        let slgValue = 0;
        if (slg) {
            const match = slg.toString().match(/\d+/);
            slgValue = match ? parseInt(match[0]) : 0;
        }

        section.innerHTML = `
            <div class="kontrak-field">
                <label class="kontrak-label">Ketentuan Layanan</label>
                <small class="kontrak-help-text">Service Level Guarantee dan Skema Bisnis</small>
                
                <div class="kontrak-table-wrapper">
                    <table class="kontrak-table kontrak-table-static">
                        <tbody>
                            <tr>
                                <td class="kontrak-table-label">SLG</td>
                                <td>
                                    <input type="number" 
                                           class="kontrak-table-input" 
                                           id="kontrak_slg"
                                           value="${slgValue}"
                                           placeholder="0"
                                           min="0"
                                           max="100">
                                </td>
                            </tr>
                            <tr>
                                <td class="kontrak-table-label">Skema Bisnis</td>
                                <td>
                                    <input type="text" 
                                           class="kontrak-table-input" 
                                           id="kontrak_skema_bisnis"
                                           value="${skema || ''}"
                                           placeholder="Enter business scheme">
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
     * Create Rujukan section with dynamic table
     */
    createRujukanSection: function (rujukanData) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';

        const headerWithButton = document.createElement('div');
        headerWithButton.className = 'kontrak-table-header';

        const headerDiv = document.createElement('div');
        headerDiv.innerHTML = `
            <label class="kontrak-label">Rujukan</label>
            <small class="kontrak-help-text">Dokumen atau Surat Rujukan Terkait Kontrak</small>
        `;

        const addButton = document.createElement('button');
        addButton.type = 'button';
        addButton.className = 'kontrak-add-btn';
        addButton.innerHTML = 'Tambah';
        addButton.onclick = () => this.addRujukanItem();

        headerWithButton.appendChild(headerDiv);
        headerWithButton.appendChild(addButton);

        const tableWrapper = document.createElement('div');
        tableWrapper.className = 'kontrak-table-wrapper';

        const table = document.createElement('table');
        table.className = 'kontrak-table';
        table.id = 'rujukan-table';

        table.innerHTML = `
            <thead>
                <tr>
                    <th style="width: 60px;"></th>
                    <th>Rujukan</th>
                    <th class="kontrak-table-action">Action</th>
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
                    <td colspan="3">
                        <i class="fas fa-inbox"></i>
                        <p>Tidak ada rujukan</p>
                        <small>Klik tombol "Tambah" untuk menambah</small>
                    </td>
                </tr>
            `;
        }

        tableWrapper.appendChild(table);
        section.appendChild(headerWithButton);
        section.appendChild(tableWrapper);

        return section;
    },

    /**
     * Create single rujukan row
     */
    createRujukanRow: function (value, index) {
        const tr = document.createElement('tr');
        tr.className = 'kontrak-table-row';
        tr.dataset.index = index;

        tr.innerHTML = `
            <td class="kontrak-table-number">${index + 1}</td>
            <td>
                <textarea class="kontrak-table-textarea rujukan-value-input" 
                          id="rujukan_value_${index}"
                          rows="2"
                          placeholder="Enter reference">${value || ''}</textarea>
            </td>
            <td class="kontrak-table-action">
                <button type="button" class="kontrak-delete-btn" onclick="FormTemplateKontrak.deleteRujukanItem(${index})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        return tr;
    },

    /**
     * Add new rujukan item
     */
    addRujukanItem: function () {
        const tbody = document.getElementById('rujukan-tbody');

        const emptyRow = tbody.querySelector('.kontrak-table-empty');
        if (emptyRow) {
            emptyRow.remove();
        }

        const existingRows = tbody.querySelectorAll('.kontrak-table-row');
        const newIndex = existingRows.length;

        const newRow = this.createRujukanRow('', newIndex);
        tbody.appendChild(newRow);

        setTimeout(() => {
            const textarea = document.getElementById(`rujukan_value_${newIndex}`);
            if (textarea) {
                this.autoResize(textarea);
                textarea.addEventListener('input', () => this.autoResize(textarea));
                textarea.focus();
            }
        }, 100);
    },

    /**
     * Delete rujukan item
     */
    deleteRujukanItem: function (index) {
        if (!confirm('Hapus rujukan ini?')) return;

        const tbody = document.getElementById('rujukan-tbody');
        const row = tbody.querySelector(`[data-index="${index}"]`);

        if (row) {
            row.remove();
        }

        this.reindexRujukanItems();

        const remainingRows = tbody.querySelectorAll('.kontrak-table-row');
        if (remainingRows.length === 0) {
            tbody.innerHTML = `
                <tr class="kontrak-table-empty">
                    <td colspan="3">
                        <i class="fas fa-inbox"></i>
                        <p>Tidak ada rujukan</p>
                        <small>Klik tombol "Tambah" untuk menambah</small>
                    </td>
                </tr>
            `;
        }
    },

    /**
     * Re-index rujukan items
     */
    reindexRujukanItems: function () {
        const tbody = document.getElementById('rujukan-tbody');
        const rows = tbody.querySelectorAll('.kontrak-table-row');

        rows.forEach((row, newIndex) => {
            row.dataset.index = newIndex;

            const numberCell = row.querySelector('.kontrak-table-number');
            const valueInput = row.querySelector('.rujukan-value-input');
            const deleteBtn = row.querySelector('.kontrak-delete-btn');

            numberCell.textContent = newIndex + 1;
            valueInput.id = `rujukan_value_${newIndex}`;
            deleteBtn.setAttribute('onclick', `FormTemplateKontrak.deleteRujukanItem(${newIndex})`);
        });
    },

    /**
     * Create editable Data BAST section (NOMOR MITRA, NOMOR TELKOM, TANGGAL BAST).
     * Used when rendering BAST-only view (validate-ground-truth).
     */
    createBastDataSectionEditable: function (data) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';

        const bast = data?.BAST || data?.bast;
        const nomor = bast?.nomor || (data?.nomor && (data.nomor.mitra || data.nomor.telkom) ? data.nomor : null);
        const nomorMitra = nomor?.mitra ?? data?.nomor_mitra ?? '';
        const nomorTelkom = nomor?.telkom ?? data?.nomor_telkom ?? '';
        const tanggalBast = bast?.tanggal_bast ?? data?.tanggal_bast ?? '';

        const tanggalForInput = tanggalBast ? this.convertDateToInput(String(tanggalBast).trim().split(/\r?\n/)[0] || '') : '';

        section.innerHTML = `
            <div class="kontrak-field">
                <label class="kontrak-label">Data BAST</label>
                <div class="kontrak-field" style="margin-top: 0.75rem;">
                    <label class="kontrak-label" for="kontrak_nomor_mitra">NOMOR MITRA</label>
                    <input type="text" class="form-control kontrak-input" id="kontrak_nomor_mitra" value="${this.escapeHtml(String(nomorMitra))}" placeholder="Nomor BAST Mitra" style="display: block; width: 100%; margin-top: 0.25rem;">
                </div>
                <div class="kontrak-field" style="margin-top: 0.75rem;">
                    <label class="kontrak-label" for="kontrak_nomor_telkom">NOMOR TELKOM</label>
                    <input type="text" class="form-control kontrak-input" id="kontrak_nomor_telkom" value="${this.escapeHtml(String(nomorTelkom))}" placeholder="Nomor BAST Telkom" style="display: block; width: 100%; margin-top: 0.25rem;">
                </div>
                <div style="margin-top: 0.75rem;">
                    <label class="kontrak-label" for="kontrak_tanggal_bast">TANGGAL DOKUMEN BAST</label>
                    <input type="date" class="form-control kontrak-input" id="kontrak_tanggal_bast" value="${this.escapeHtml(tanggalForInput)}" placeholder="Tanggal BAST" style="display: block; width: 100%; margin-top: 0.25rem;">
                </div>
            </div>
        `;
        return section;
    },

    /**
     * Create Pejabat Penanda Tangan section.
     * @param {Object} pejabatData - { baut: {telkom, mitra}, bast: {...}, ... }
     * @param {Object} [options] - Optional. { documentsOnly: ['bast'] } to show only BAST (e.g. for BAST doc type).
     */
    createPejabatSection: function (pejabatData, options) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';

        const data = pejabatData || {};
        const allDocs = ['baut', 'bast', 'bapl', 'bard'];
        const documents = (options && Array.isArray(options.documentsOnly) && options.documentsOnly.length > 0)
            ? options.documentsOnly
            : allDocs;
        const docLabels = {
            'baut': 'BAUT',
            'bast': 'BAST',
            'bapl': 'BAPL',
            'bard': 'BARD'
        };

        let html = `
            <div class="kontrak-field">
                <label class="kontrak-label">Pejabat Penanda Tangan</label>
                <small class="kontrak-help-text">Pejabat yang Menandatangani Dokumen</small>
        `;

        documents.forEach(doc => {
            const docData = data[doc] || data[doc.toUpperCase?.() || doc] || data[doc.toLowerCase?.() || doc] || {};
            html += `
                <div class="kontrak-pejabat-section">
                    <div class="kontrak-pejabat-header">${docLabels[doc]}</div>
                    
                    <div class="kontrak-pejabat-item">
                        <label class="kontrak-pejabat-label">Telkom</label>
                        <textarea class="kontrak-textarea" 
                                  id="pejabat_${doc}_telkom" 
                                  rows="2"
                                  placeholder="Enter Telkom official">${docData.telkom || ''}</textarea>
                    </div>
                    
                    <div class="kontrak-pejabat-item">
                        <label class="kontrak-pejabat-label">Mitra</label>
                        <textarea class="kontrak-textarea" 
                                  id="pejabat_${doc}_mitra" 
                                  rows="2"
                                  placeholder="Enter partner official">${docData.mitra || ''}</textarea>
                    </div>
                </div>
            `;
        });

        html += `</div>`;
        section.innerHTML = html;

        return section;
    },

    /**
     * ✅ Collect data from form - FIXED: delivery_date key
     */
    collectData: function () {
        const data = {
            judul_project: document.getElementById('kontrak_judul_project')?.value || null,
            nama_pelanggan: document.getElementById('kontrak_nama_pelanggan')?.value || null,
            nomor_surat_utama: document.getElementById('kontrak_nomor_utama')?.value || null,
            nomor_surat_lainnya: document.getElementById('kontrak_nomor_lainnya')?.value || null,
            tanggal_kontrak: null,
            // ✅ KEEP BOTH KEYS for compatibility
            delivery: null,
            delivery_date: null,
            jangka_waktu: {
                start_date: null,
                end_date: null,
                duration: null
            },
            dpp_raw: parseFloat(document.getElementById('kontrak_dpp')?.value) || 0,
            harga_satuan_raw: parseFloat(document.getElementById('kontrak_harga_satuan')?.value) || 0,
            metode_pembayaran: document.getElementById('kontrak_metode')?.value || null,
            terms_of_payment: document.getElementById('kontrak_terms')?.value || null,
            detail_rekening: {
                nomor_rekening: document.getElementById('kontrak_nomor_rekening')?.value || null,
                nama_bank: document.getElementById('kontrak_nama_bank')?.value || null,
                atas_nama: document.getElementById('kontrak_atas_nama')?.value || null,
                kantor_cabang: document.getElementById('kontrak_kantor_cabang')?.value || null
            },
            slg: null,
            skema_bisnis: document.getElementById('kontrak_skema_bisnis')?.value || null,
            rujukan: {},
            pejabat_penanda_tangan: {
                baut: { telkom: null, mitra: null },
                bast: { telkom: null, mitra: null },
                bapl: { telkom: null, mitra: null },
                bard: { telkom: null, mitra: null }
            }
        };

        // Convert dates
        const tanggalKontrak = document.getElementById('kontrak_tanggal_kontrak')?.value;
        const deliveryInput = document.getElementById('kontrak_delivery')?.value;
        const startDate = document.getElementById('kontrak_start_date')?.value;
        const endDate = document.getElementById('kontrak_end_date')?.value;

        if (tanggalKontrak) data.tanggal_kontrak = this.convertDateFromInput(tanggalKontrak);

        // ✅ SAVE TO BOTH KEYS for maximum compatibility
        if (deliveryInput) {
            const convertedDelivery = this.convertDateFromInput(deliveryInput);
            data.delivery = convertedDelivery;
            data.delivery_date = convertedDelivery;
        }

        if (startDate) data.jangka_waktu.start_date = this.convertDateFromInput(startDate);
        if (endDate) data.jangka_waktu.end_date = this.convertDateFromInput(endDate);

        // Duration: parse "30 Hari" or "1 Bulan" from text input (user can type/correct)
        const durationInput = document.getElementById('kontrak_duration')?.value;
        if (durationInput && typeof durationInput === 'string' && durationInput.trim()) {
            var parsed = this.parseDurationFromDb(durationInput.trim());
            if (parsed.value >= 0) {
                data.jangka_waktu.duration = parsed.value + ' ' + parsed.unit;
            }
        }

        // SLG with %
        const slg = parseInt(document.getElementById('kontrak_slg')?.value);
        if (!isNaN(slg)) {
            data.slg = `${slg}%`;
        }

        // Rujukan
        const rujukanTbody = document.getElementById('rujukan-tbody');
        if (rujukanTbody) {
            const rujukanRows = rujukanTbody.querySelectorAll('.kontrak-table-row');
            rujukanRows.forEach((row, index) => {
                const value = document.getElementById(`rujukan_value_${index}`)?.value;
                if (value) {
                    data.rujukan[`${index + 1}`] = value;
                }
            });
        }

        // Pejabat
        const documents = ['baut', 'bast', 'bapl', 'bard'];
        documents.forEach(doc => {
            const telkom = document.getElementById(`pejabat_${doc}_telkom`)?.value;
            const mitra = document.getElementById(`pejabat_${doc}_mitra`)?.value;

            data.pejabat_penanda_tangan[doc].telkom = telkom || null;
            data.pejabat_penanda_tangan[doc].mitra = mitra || null;
        });

        // Data BAST (when BAST or BAST-only form was rendered)
        const nomorMitraEl = document.getElementById('kontrak_nomor_mitra');
        const nomorTelkomEl = document.getElementById('kontrak_nomor_telkom');
        const tanggalBastEl = document.getElementById('kontrak_tanggal_bast');
        if (nomorMitraEl) data.nomor_mitra = nomorMitraEl.value?.trim() || null;
        if (nomorTelkomEl) data.nomor_telkom = nomorTelkomEl.value?.trim() || null;
        if (tanggalBastEl && tanggalBastEl.value) data.tanggal_bast = this.convertDateFromInput(tanggalBastEl.value);

        return data;
    }

};