const FormTemplateNPKReadOnly = {
    /**
     * Metadata
     */
    meta: {
        title: 'Data NPK',
        description: 'Hasil ekstraksi Ground Truth (Read-Only)',
    },

    /**
     * Format number dengan thousand separator (100000 → 100.000)
     */
    formatNumber: function (value) {
        if (!value && value !== 0) return '-';
        const num = value.toString().replace(/\./g, '');
        return num.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    },

    /**
     * Main render function (READ-ONLY)
     */
    render: function (data, container) {
        container.innerHTML = '';

        const wrapper = document.createElement('div');
        wrapper.className = 'template-npk';

        // Section 1: SID
        if (data.SID !== undefined) {
            wrapper.appendChild(this.createSIDField(data.SID));
        }

        // Section 2: Prorate
        if (data.prorate && data.prorate.length > 0) {
            wrapper.appendChild(this.createProrateSection(data.prorate));
        }

        // Section 3: Usage
        if (data.nilai_satuan_usage && Object.keys(data.nilai_satuan_usage).length > 0) {
            wrapper.appendChild(this.createUsageSection(data.nilai_satuan_usage));
        }

        container.appendChild(wrapper);
    },

    /**
     * Create SID field (READ-ONLY) - Table view
     */
    createSIDField: function (sidValue) {
        const section = document.createElement('div');
        section.className = 'npk-section';

        const headerDiv = document.createElement('div');
        headerDiv.innerHTML = `
            <label class="npk-label">
                <i class="fas fa-id-card"></i>
                Service ID (SID)
            </label>
            <small class="npk-help-text">Unique identifier for this service</small>
        `;

        const tableWrapper = document.createElement('div');
        tableWrapper.className = 'npk-table-wrapper';

        const table = document.createElement('table');
        table.className = 'npk-table';
        table.id = 'sid-table';

        // Handle both single SID (string) and multiple SIDs (array)
        let sidArray = [];
        if (Array.isArray(sidValue)) {
            sidArray = sidValue;
        } else if (typeof sidValue === 'string' && sidValue.includes(',')) {
            // Comma-separated string
            sidArray = sidValue.split(',').map(s => s.trim()).filter(s => s.length > 0);
        } else if (sidValue) {
            // Single SID
            sidArray = [sidValue];
        }

        // Table header
        table.innerHTML = `
            <thead>
                <tr>
                    <th>Service ID (SID)</th>
                </tr>
            </thead>
            <tbody id="sid-tbody">
            </tbody>
        `;

        const tbody = table.querySelector('#sid-tbody');

        if (sidArray.length > 0) {
            sidArray.forEach((sid, index) => {
                const tr = document.createElement('tr');
                tr.className = 'npk-table-row';
                tr.innerHTML = `
                    <td>
                        <div class="npk-table-input" style="background: #f9fafb; cursor: default; padding: 0.625rem 0.875rem; border: 2px solid #e5e7eb; border-radius: 10px;">
                            ${sid || '-'}
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = `
                <tr class="npk-table-empty">
                    <td>
                        <i class="fas fa-inbox"></i>
                        <p>Tidak ada Service ID</p>
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
     * Create prorate section (READ-ONLY - No Add/Delete buttons)
     */
    createProrateSection: function (prorateData) {
        const section = document.createElement('div');
        section.className = 'npk-section';

        const headerDiv = document.createElement('div');
        headerDiv.innerHTML = `
            <label class="npk-label">
                <i class="fas fa-percentage"></i>
                Prorate Information
            </label>
            <small class="npk-help-text">Prorate values for billing adjustments</small>
        `;

        const tableWrapper = document.createElement('div');
        tableWrapper.className = 'npk-table-wrapper';

        const table = document.createElement('table');
        table.className = 'npk-table';

        // Table header
        table.innerHTML = `
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Hari</th>
                </tr>
            </thead>
            <tbody id="prorate-tbody">
            </tbody>
        `;

        const tbody = table.querySelector('#prorate-tbody');

        // Sort and render prorate data
        const sortedData = this.sortProrateData(prorateData);

        if (sortedData.length > 0) {
            sortedData.forEach((item, index) => {
                tbody.appendChild(this.createProrateRow(item, index));
            });
        } else {
            tbody.innerHTML = `
                <tr class="npk-table-empty">
                    <td colspan="2">
                        <i class="fas fa-inbox"></i>
                        <p>Tidak ada data prorate</p>
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
     * Sort prorate data (handles both array and object format)
     */
    sortProrateData: function (data) {
        if (!data) return [];

        // If already array from backend (new format)
        if (Array.isArray(data)) {
            return data; // Already sorted by backend
        }

        // If object format (old format), convert and sort
        const items = [];
        for (const [period, value] of Object.entries(data)) {
            items.push({ period, value });
        }

        // Sort by period (month year)
        return items.sort((a, b) => {
            return this.comparePeriods(a.period, b.period);
        });
    },

    /**
     * Compare two period strings (e.g., "Maret 2025" vs "April 2025")
     */
    comparePeriods: function (periodA, periodB) {
        const parseA = this.parsePeriod(periodA);
        const parseB = this.parsePeriod(periodB);

        // Compare year first
        if (parseA.year !== parseB.year) {
            return parseA.year - parseB.year;
        }

        // Then compare month
        return parseA.month - parseB.month;
    },

    /**
     * Parse period string into {month, year}
     */
    parsePeriod: function (period) {
        const parts = period.trim().split(' ');

        if (parts.length >= 2) {
            const monthStr = parts[0];
            const year = parseInt(parts[1]) || 0;
            const month = this.getMonthNumber(monthStr);

            return { month, year };
        }

        return { month: 0, year: 0 };
    },

    /**
     * Convert month name to number
     */
    getMonthNumber: function (monthStr) {
        const months = {
            'januari': 1, 'jan': 1,
            'februari': 2, 'feb': 2,
            'maret': 3, 'mar': 3,
            'april': 4, 'apr': 4,
            'mei': 5,
            'juni': 6, 'jun': 6,
            'juli': 7, 'jul': 7,
            'agustus': 8, 'agu': 8, 'agt': 8,
            'september': 9, 'sep': 9, 'sept': 9,
            'oktober': 10, 'okt': 10, 'oct': 10,
            'november': 11, 'nov': 11,
            'desember': 12, 'des': 12, 'dec': 12,
        };

        const lower = monthStr.toLowerCase().trim();
        return months[lower] || 0;
    },

    /**
     * Create single prorate row (READ-ONLY)
     */
    createProrateRow: function (item, index) {
        const tr = document.createElement('tr');
        tr.className = 'npk-table-row';

        const period = typeof item === 'object' ? (item.period || '-') : '-';
        const value = typeof item === 'object' ? (item.value || 0) : (item || 0);
        const formattedValue = this.formatNumber(value);

        tr.innerHTML = `
            <td>
                <div class="npk-table-input" style="background: #f9fafb; cursor: default;">
                    ${period}
                </div>
            </td>
            <td>
                <div class="npk-table-input" style="background: #f9fafb; cursor: default; text-align: center;">
                    ${formattedValue}
                </div>
            </td>
        `;

        return tr;
    },

    /**
     * Create usage section (READ-ONLY - No Add/Delete buttons)
     */
    createUsageSection: function (usageData) {
        const section = document.createElement('div');
        section.className = 'npk-section';

        const headerDiv = document.createElement('div');
        headerDiv.innerHTML = `
            <label class="npk-label">
                <i class="fas fa-calendar-alt"></i>
                Monthly Usage Values
            </label>
            <small class="npk-help-text">Enter the usage value for each month</small>
        `;

        const tableWrapper = document.createElement('div');
        tableWrapper.className = 'npk-table-wrapper';

        const table = document.createElement('table');
        table.className = 'npk-table';

        // Table header
        table.innerHTML = `
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Harga</th>
                </tr>
            </thead>
            <tbody id="usage-tbody">
            </tbody>
        `;

        const tbody = table.querySelector('#usage-tbody');
        tbody.innerHTML = this.createUsageRows(usageData);

        tableWrapper.appendChild(table);
        section.appendChild(headerDiv);
        section.appendChild(tableWrapper);

        return section;
    },

    /**
     * Create usage rows with sorting (READ-ONLY)
     */
    createUsageRows: function (usageData) {
        if (!usageData || Object.keys(usageData).length === 0) {
            return `
                <tr class="npk-table-empty">
                    <td colspan="2">
                        <i class="fas fa-inbox"></i>
                        <p>Tidak ada data usage</p>
                    </td>
                </tr>
            `;
        }

        // Sort entries by month/year before rendering
        const sortedEntries = Object.entries(usageData).sort((a, b) => {
            return this.comparePeriods(a[0], b[0]);
        });

        let html = '';
        sortedEntries.forEach(([month, value], index) => {
            const formattedValue = this.formatNumber(value);

            html += `
                <tr class="npk-table-row">
                    <td>
                        <div class="npk-table-input" style="background: #f9fafb; cursor: default;">
                            ${month}
                        </div>
                    </td>
                    <td>
                        <div class="npk-table-input" style="background: #f9fafb; cursor: default; text-align: right; font-weight: 600;">
                            ${formattedValue}
                        </div>
                    </td>
                </tr>
            `;
        });

        return html;
    }
};