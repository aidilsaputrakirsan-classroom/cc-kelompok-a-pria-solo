/**
 * Form Template: BAUT Read-Only (Berita Acara Uji Teknis)
 * Display-only version for viewing submitted data
 */

/**
 * Helper: Clean text - remove leading/trailing whitespace per line and overall
 * Handles all Unicode whitespace characters including non-breaking spaces
 */
function cleanTextBAUT(value) {
    if (!value || value === null || value === undefined) return '-';

    // Convert to string
    let strValue = String(value);
    
    // Remove all types of Unicode whitespace from start and end
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

const FormTemplateBAUTReadOnly = {
    /**
     * Metadata
     */
    meta: {
        title: 'Data BAUT',
        description: 'Berita Acara Uji Teknis - Technical Acceptance Report (Read-Only)',
    },

    /**
     * Main render function (READ-ONLY)
     */
    render: function (data, container) {
        container.innerHTML = '';

        const wrapper = document.createElement('div');
        wrapper.className = 'template-baut';

        // Section 1: Tanggal BAUT
        if (data.tanggal_baut !== undefined) {
            wrapper.appendChild(this.createTanggalBAUTField(data.tanggal_baut));
        }

        // Section 2: Lampiran BAUT (READ-ONLY)
        if (data.lampiran_baut && Object.keys(data.lampiran_baut).length > 0) {
            wrapper.appendChild(this.createLampiranBAUTSection(data.lampiran_baut));
        }

        container.appendChild(wrapper);
    },

    /**
     * Create Tanggal BAUT field (READ-ONLY)
     */
    createTanggalBAUTField: function (value) {
        const section = document.createElement('div');
        section.className = 'baut-section';

        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'baut-field';

        const label = document.createElement('label');
        label.className = 'baut-label';
        label.textContent = 'Tanggal BAUT';

        const helpText = document.createElement('small');
        helpText.className = 'baut-help-text';
        helpText.textContent = 'Tanggal Berita Acara Uji Teknis';

        const inputDiv = document.createElement('div');
        inputDiv.className = 'baut-input';
        inputDiv.style.cssText = 'background: #f9fafb; cursor: default; padding: 0.75rem;';
        inputDiv.textContent = cleanTextBAUT(value);

        fieldDiv.appendChild(label);
        fieldDiv.appendChild(helpText);
        fieldDiv.appendChild(inputDiv);
        section.appendChild(fieldDiv);

        return section;
    },

    /**
     * Create Lampiran BAUT section (READ-ONLY)
     */
    createLampiranBAUTSection: function (lampiranData) {
        const section = document.createElement('div');
        section.className = 'baut-section';

        const fieldDiv = document.createElement('div');
        fieldDiv.className = 'baut-field';

        const label = document.createElement('label');
        label.className = 'baut-label';
        label.textContent = 'Lampiran BAUT';

        const helpText = document.createElement('small');
        helpText.className = 'baut-help-text';
        helpText.textContent = 'Daftar Lampiran Berita Acara Uji Teknis';

        const tableWrapper = document.createElement('div');
        tableWrapper.className = 'baut-table-wrapper';

        const table = document.createElement('table');
        table.className = 'baut-table baut-table-static';

        table.innerHTML = `
            <thead>
                <tr>
                    <th style="width: 60px;"></th>
                    <th>No Order</th>
                    <th>Tanggal Aktivasi</th>
                </tr>
            </thead>
            <tbody>
            </tbody>
        `;

        const tbody = table.querySelector('tbody');

        if (lampiranData && Object.keys(lampiranData).length > 0) {
            let index = 1;
            for (const [noOrder, tanggalAktivasi] of Object.entries(lampiranData)) {
                const tr = document.createElement('tr');

                const numTd = document.createElement('td');
                numTd.className = 'baut-table-number';
                numTd.textContent = index;

                const noOrderTd = document.createElement('td');
                const noOrderDiv = document.createElement('div');
                noOrderDiv.className = 'baut-table-input';
                noOrderDiv.style.cssText = 'background: #f9fafb; cursor: default;';
                noOrderDiv.textContent = cleanTextBAUT(noOrder);
                noOrderTd.appendChild(noOrderDiv);

                const tanggalTd = document.createElement('td');
                const tanggalDiv = document.createElement('div');
                tanggalDiv.className = 'baut-table-input';
                tanggalDiv.style.cssText = 'background: #f9fafb; cursor: default;';
                tanggalDiv.textContent = cleanTextBAUT(tanggalAktivasi);
                tanggalTd.appendChild(tanggalDiv);

                tr.appendChild(numTd);
                tr.appendChild(noOrderTd);
                tr.appendChild(tanggalTd);
                tbody.appendChild(tr);

                index++;
            }
        } else {
            tbody.innerHTML = `
                <tr class="baut-table-empty">
                    <td colspan="3">
                        <i class="fas fa-inbox"></i>
                        <p>Tidak ada lampiran BAUT</p>
                    </td>
                </tr>
            `;
        }

        tableWrapper.appendChild(table);
        fieldDiv.appendChild(label);
        fieldDiv.appendChild(helpText);
        fieldDiv.appendChild(tableWrapper);
        section.appendChild(fieldDiv);

        return section;
    }

};
