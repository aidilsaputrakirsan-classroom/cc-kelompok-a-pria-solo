const FormTemplateKontrak = {
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

        // Section 1: Judul Project
        wrapper.appendChild(this.createJudulProjectField(data.judul_project));

        // Section 2: Nama Pelanggan
        wrapper.appendChild(this.createNamaPelangganField(data.nama_pelanggan));

        // Section 3: Nomor Kontrak (Table)
        wrapper.appendChild(this.createNomorKontrakTable(data.nomor_surat_utama, data.nomor_surat_lainnya));

        // Section 4: Tanggal (Table) - ✅ FIXED MAPPING
        wrapper.appendChild(this.createTanggalTable(data));

        // Section 5: Detail Pembayaran (Table)
        wrapper.appendChild(this.createDetailPembayaranTable(data));

        // Section 6: Detail Rekening (Table)
        wrapper.appendChild(this.createDetailRekeningTable(data.detail_rekening));

        // Section 7: Ketentuan Layanan (Table)
        wrapper.appendChild(this.createKetentuanLayananTable(data.slg, data.skema_bisnis));

        // Section 8: Rujukan (Dynamic Table)
        wrapper.appendChild(this.createRujukanSection(data.rujukan));

        // Section 9: Pejabat Penanda Tangan (Sections)
        wrapper.appendChild(this.createPejabatSection(data.pejabat_penanda_tangan));

        container.appendChild(wrapper);

        // Auto-resize all textareas
        setTimeout(() => this.initAutoResize(), 100);
    },

    /**
     * Helper: Convert date to yyyy-mm-dd format for input[type="date"]
     * Handles multiple formats:
     * - "1 Januari 2025" (Indonesian month name)
     * - "dd-mm-yyyy" (standard format)
     */
    convertDateToInput: function (dateStr) {
        if (!dateStr || typeof dateStr !== 'string') return '';

        // Try parsing Indonesian date format: "1 Januari 2025"
        const indonesianDate = this.parseIndonesianDate(dateStr);
        if (indonesianDate) {
            const year = indonesianDate.getFullYear();
            const month = String(indonesianDate.getMonth() + 1).padStart(2, '0');
            const day = String(indonesianDate.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        // Try parsing dd-mm-yyyy format
        const parts = dateStr.split('-');
        if (parts.length === 3) {
            const [day, month, year] = parts;
            if (year.length === 4) {
                return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
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

        // ✅ FLEXIBLE: Handle both 'delivery' and 'delivery_date'
        const deliveryValue = data.delivery_date || data.delivery || '';
        const delivery = this.convertDateToInput(deliveryValue);

        const tanggalKontrak = this.convertDateToInput(data.tanggal_kontrak);
        const startDate = this.convertDateToInput(data.jangka_waktu?.start_date);
        const endDate = this.convertDateToInput(data.jangka_waktu?.end_date);

        let duration = 0;
        if (data.jangka_waktu?.duration) {
            const match = data.jangka_waktu.duration.match(/\d+/);
            duration = match ? parseInt(match[0]) : 0;
        }

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
                                <input type="number" 
                                       class="kontrak-table-input" 
                                       id="kontrak_duration"
                                       value="${duration}"
                                       placeholder="0"
                                       min="0">
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
                    <i class="fas fa-trash-alt"></i>
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
     * Create Pejabat Penanda Tangan section
     */
    createPejabatSection: function (pejabatData) {
        const section = document.createElement('div');
        section.className = 'kontrak-section';

        const data = pejabatData || {};
        const documents = ['baut', 'bast', 'bapl', 'bard'];
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
            const docData = data[doc] || {};
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

        // Duration with "Bulan"
        const duration = parseInt(document.getElementById('kontrak_duration')?.value);
        if (duration) {
            data.jangka_waktu.duration = `${duration} Bulan`;
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

        return data;
    }

};