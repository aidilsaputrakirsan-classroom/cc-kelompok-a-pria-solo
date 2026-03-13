/**
 * Form Template: BAUT (Berita Acara Uji Teknis)
 * Editable version with add/edit/delete capabilities
 */

const FormTemplateBAUT = {
    /**
     * Metadata
     */
    meta: {
        title: 'Data BAUT',
        description: 'Berita Acara Uji Teknis - Technical Acceptance Report',
    },

    /**
     * Main render function
     */
    render: function (data, container) {
        container.innerHTML = '';

        const wrapper = document.createElement('div');
        wrapper.className = 'template-baut';

        // Section 1: Tanggal BAUT
        wrapper.appendChild(this.createTanggalBAUTField(data.tanggal_baut));

        // Section 2: Lampiran BAUT (Dynamic Table)
        wrapper.appendChild(this.createLampiranBAUTSection(data.lampiran_baut));

        container.appendChild(wrapper);
    },

    /**
     * Helper: Convert date to yyyy-mm-dd format for input[type="date"]
     */
    convertDateToInput: function (dateStr) {
        if (!dateStr || typeof dateStr !== 'string') return '';

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
     * Helper: Convert date from yyyy-mm-dd to dd-mm-yyyy
     */
    convertDateFromInput: function (dateStr) {
        if (!dateStr || typeof dateStr !== 'string') return '';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        const [year, month, day] = parts;
        return `${day}-${month}-${year}`;
    },

    /**
     * Create Tanggal BAUT field
     */
    createTanggalBAUTField: function (value) {
        const section = document.createElement('div');
        section.className = 'baut-section';

        section.innerHTML = `
            <div class="baut-field">
                <label class="baut-label">Tanggal BAUT</label>
                <small class="baut-help-text">Tanggal Berita Acara Uji Teknis</small>
                <input type="date" 
                       class="baut-input" 
                       id="baut_tanggal_baut"
                       value="${this.convertDateToInput(value || '')}">
            </div>
        `;

        return section;
    },

    /**
     * Create Lampiran BAUT section with dynamic table (Two Columns: No Order & Tanggal Aktivasi)
     */
    createLampiranBAUTSection: function (lampiranData) {
        const section = document.createElement('div');
        section.className = 'baut-section';

        const headerWithButton = document.createElement('div');
        headerWithButton.className = 'baut-table-header';

        const headerDiv = document.createElement('div');
        headerDiv.innerHTML = `
            <label class="baut-label">Lampiran BAUT</label>
            <small class="baut-help-text">Daftar Lampiran Berita Acara Uji Teknis</small>
        `;

        const addButton = document.createElement('button');
        addButton.type = 'button';
        addButton.className = 'baut-add-btn';
        addButton.innerHTML = 'Tambah';
        addButton.onclick = () => this.addLampiranBAUTItem();

        headerWithButton.appendChild(headerDiv);
        headerWithButton.appendChild(addButton);

        const tableWrapper = document.createElement('div');
        tableWrapper.className = 'baut-table-wrapper';

        const table = document.createElement('table');
        table.className = 'baut-table';
        table.id = 'lampiran-baut-table';

        table.innerHTML = `
            <thead>
                <tr>
                    <th>No Order</th>
                    <th>Tanggal Aktivasi</th>
                    <th class="baut-table-action"></th>
                </tr>
            </thead>
            <tbody id="lampiran-baut-tbody">
            </tbody>
        `;

        const tbody = table.querySelector('#lampiran-baut-tbody');

        if (lampiranData && Object.keys(lampiranData).length > 0) {
            let index = 0;
            for (const [noOrder, tanggalAktivasi] of Object.entries(lampiranData)) {
                tbody.appendChild(this.createLampiranBAUTRow(noOrder, tanggalAktivasi, index));
                index++;
            }
        } else {
            tbody.innerHTML = `
                <tr class="baut-table-empty">
                    <td colspan="3">
                        <i class="fas fa-inbox"></i>
                        <p>Tidak ada lampiran BAUT</p>
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
     * Create single lampiran BAUT row (No Order & Tanggal Aktivasi)
     */
    createLampiranBAUTRow: function (noOrder, tanggalAktivasi, index) {
        const tr = document.createElement('tr');
        tr.className = 'baut-table-row';
        tr.dataset.index = index;

        const tanggalActivInput = this.convertDateToInput(tanggalAktivasi);

        tr.innerHTML = `
            <td>
                <input type="text" 
                       class="baut-table-input lampiran-baut-no-order-input" 
                       id="lampiran_baut_no_order_${index}"
                       placeholder="e.g., 1-34430325346"
                       value="${noOrder || ''}">
            </td>
            <td>
                <input type="date" 
                       class="baut-table-input lampiran-baut-tanggal-input" 
                       id="lampiran_baut_tanggal_${index}"
                       value="${tanggalActivInput || ''}">
            </td>
            <td class="baut-table-action">
                <button type="button" class="baut-delete-btn" onclick="FormTemplateBAUT.deleteLampiranBAUTItem(${index})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        return tr;
    },

    /**
     * Add new lampiran BAUT item
     */
    addLampiranBAUTItem: function () {
        const tbody = document.getElementById('lampiran-baut-tbody');

        const emptyRow = tbody.querySelector('.baut-table-empty');
        if (emptyRow) {
            emptyRow.remove();
        }

        const existingRows = tbody.querySelectorAll('.baut-table-row');
        const newIndex = existingRows.length;

        const newRow = this.createLampiranBAUTRow('', '', newIndex);
        tbody.appendChild(newRow);

        setTimeout(() => {
            const noOrderInput = document.getElementById(`lampiran_baut_no_order_${newIndex}`);
            if (noOrderInput) {
                noOrderInput.focus();
            }
        }, 100);
    },

    /**
     * Delete lampiran BAUT item
     */
    deleteLampiranBAUTItem: function (index) {
        if (!confirm('Hapus lampiran BAUT ini?')) return;

        const tbody = document.getElementById('lampiran-baut-tbody');
        const row = tbody.querySelector(`[data-index="${index}"]`);

        if (row) {
            row.remove();
        }

        this.reindexLampiranBAUTItems();

        const remainingRows = tbody.querySelectorAll('.baut-table-row');
        if (remainingRows.length === 0) {
            tbody.innerHTML = `
                <tr class="baut-table-empty">
                    <td colspan="3">
                        <i class="fas fa-inbox"></i>
                        <p>Tidak ada lampiran BAUT</p>
                        <small>Klik tombol "Tambah" untuk menambah</small>
                    </td>
                </tr>
            `;
        }
    },

    /**
     * Re-index lampiran BAUT items
     */
    reindexLampiranBAUTItems: function () {
        const tbody = document.getElementById('lampiran-baut-tbody');
        const rows = tbody.querySelectorAll('.baut-table-row');

        rows.forEach((row, newIndex) => {
            row.dataset.index = newIndex;

            const noOrderInput = row.querySelector('.lampiran-baut-no-order-input');
            const tanggalInput = row.querySelector('.lampiran-baut-tanggal-input');
            const deleteBtn = row.querySelector('.baut-delete-btn');

            noOrderInput.id = `lampiran_baut_no_order_${newIndex}`;
            tanggalInput.id = `lampiran_baut_tanggal_${newIndex}`;
            deleteBtn.setAttribute('onclick', `FormTemplateBAUT.deleteLampiranBAUTItem(${newIndex})`);
        });
    },

    /**
     * ✅ Collect data from form
     */
    collectData: function () {
        const data = {
            tanggal_baut: null,
            lampiran_baut: {}
        };

        // Tanggal BAUT
        const tanggalBautInput = document.getElementById('baut_tanggal_baut')?.value;
        if (tanggalBautInput) {
            data.tanggal_baut = this.convertDateFromInput(tanggalBautInput);
        }

        // Lampiran BAUT (Two columns: No Order & Tanggal Aktivasi)
        const lampiranBAUTTbody = document.getElementById('lampiran-baut-tbody');
        if (lampiranBAUTTbody) {
            const lampiranRows = lampiranBAUTTbody.querySelectorAll('.baut-table-row');
            lampiranRows.forEach((row, index) => {
                const noOrder = document.getElementById(`lampiran_baut_no_order_${index}`)?.value;
                const tanggalAktivasi = document.getElementById(`lampiran_baut_tanggal_${index}`)?.value;
                if (noOrder && tanggalAktivasi) {
                    const convertedDate = this.convertDateFromInput(tanggalAktivasi);
                    data.lampiran_baut[noOrder] = convertedDate;
                }
            });
        }

        return data;
    }

};
