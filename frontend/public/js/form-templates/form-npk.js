const FormTemplateNPK = {
    /**
     * Metadata
     */
    meta: {
        title: 'Data NPK',
        description: 'Fill in the correct information for this document.',
    },

    /**
     * ✅ Format number dengan thousand separator (100000 → 100.000)
     */
    formatNumber: function (value) {
        if (!value && value !== 0) return '';

        // Convert to string and remove any existing separators
        const num = value.toString().replace(/\./g, '');

        // Add thousand separator
        return num.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    },

    /**
     * ✅ Parse formatted number back to raw number (100.000 → 100000)
     */
    parseNumber: function (value) {
        if (!value) return 0;

        // Remove all dots and convert to number
        const cleaned = value.toString().replace(/\./g, '');
        return parseFloat(cleaned) || 0;
    },

    /**
     * ✅ Setup number formatting on input field
     */
    setupNumberFormatting: function (inputElement) {
        if (!inputElement) return;

        // Format on blur
        inputElement.addEventListener('blur', (e) => {
            const rawValue = this.parseNumber(e.target.value);
            e.target.value = this.formatNumber(rawValue);
        });

        // Allow only numbers and dots
        inputElement.addEventListener('input', (e) => {
            let value = e.target.value;
            // Remove any character that's not a number or dot
            value = value.replace(/[^\d.]/g, '');
            e.target.value = value;
        });

        // Format on focus out initially
        if (inputElement.value) {
            inputElement.value = this.formatNumber(inputElement.value);
        }
    },

    /**
     * ✅ Initialize number formatting for all number inputs
     */
    initNumberFormatting: function () {
        const numberInputs = document.querySelectorAll('.npk-number-input');
        numberInputs.forEach(input => {
            this.setupNumberFormatting(input);
        });
    },

    /**
     * Main render function
     */
    render: function (data, container) {
        container.innerHTML = '';

        const wrapper = document.createElement('div');
        wrapper.className = 'template-npk';

        wrapper.appendChild(this.createSIDField(data.SID));
        wrapper.appendChild(this.createProrateSection(data.prorate));
        wrapper.appendChild(this.createUsageSection(data.nilai_satuan_usage));

        container.appendChild(wrapper);

        // ✅ Setup number formatting for all existing inputs
        setTimeout(() => {
            this.initNumberFormatting();
        }, 100);
    },

    /**
     * Create SID field - Table view with add/delete functionality
     */
    createSIDField: function (sidValue) {
        const section = document.createElement('div');
        section.className = 'npk-section';

        const headerWithButton = document.createElement('div');
        headerWithButton.className = 'npk-table-header';

        const headerDiv = document.createElement('div');
        headerDiv.innerHTML = `
            <label class="npk-label">
                <i class="fas fa-id-card"></i>
                Service ID (SID)
            </label>
            <small class="npk-help-text">Unique identifier for this service</small>
        `;

        const addButton = document.createElement('button');
        addButton.type = 'button';
        addButton.className = 'npk-add-btn';
        addButton.innerHTML = 'Tambah';
        addButton.onclick = () => this.addSIDItem();

        headerWithButton.appendChild(headerDiv);
        headerWithButton.appendChild(addButton);

        const tableWrapper = document.createElement('div');
        tableWrapper.className = 'npk-table-wrapper';

        const table = document.createElement('table');
        table.className = 'npk-table';
        table.id = 'sid-table';

        // Table header
        table.innerHTML = `
            <thead>
                <tr>
                    <th>Service ID (SID)</th>
                    <th class="npk-table-action">Action</th>
                </tr>
            </thead>
            <tbody id="sid-tbody">
            </tbody>
        `;

        const tbody = table.querySelector('#sid-tbody');

        // If SID value exists, create initial row
        if (sidValue) {
            tbody.appendChild(this.createSIDRow(sidValue, 0));
        } else {
            tbody.innerHTML = this.getEmptyRowHTML('sid', 2);
        }

        tableWrapper.appendChild(table);
        section.appendChild(headerWithButton);
        section.appendChild(tableWrapper);

        return section;
    },

    /**
     * Create a single SID row
     */
    createSIDRow: function (sidValue, index) {
        const tr = document.createElement('tr');
        tr.className = 'npk-table-row';
        tr.dataset.index = index;

        tr.innerHTML = `
            <td>
                <input type="text" 
                       class="npk-table-input" 
                       id="sid_value_${index}"
                       value="${sidValue || ''}"
                       placeholder="Enter Service ID">
            </td>
            <td class="npk-table-action">
                <button type="button" class="npk-delete-btn" onclick="FormTemplateNPK.deleteSIDItem(${index})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        return tr;
    },

    /**
     * Add new SID item
     */
    addSIDItem: function () {
        const tbody = document.getElementById('sid-tbody');

        // Remove empty message if exists
        const emptyRow = tbody.querySelector('.npk-table-empty');
        if (emptyRow) {
            emptyRow.remove();
        }

        const existingRows = tbody.querySelectorAll('.npk-table-row');
        const newIndex = existingRows.length;

        const newRow = this.createSIDRow('', newIndex);
        tbody.appendChild(newRow);

        // Focus on the new input
        setTimeout(() => {
            const newInput = document.getElementById(`sid_value_${newIndex}`);
            if (newInput) {
                newInput.focus();
            }
        }, 100);
    },

    /**
     * Delete SID item
     */
    deleteSIDItem: function (index) {
        if (!confirm('Hapus SID ini?')) return;

        const tbody = document.getElementById('sid-tbody');
        const row = tbody.querySelector(`[data-index="${index}"]`);

        if (row) {
            row.remove();
        }

        this.reindexSIDItems();

        const remainingRows = tbody.querySelectorAll('.npk-table-row');
        if (remainingRows.length === 0) {
            tbody.innerHTML = this.getEmptyRowHTML('sid', 2);
        }
    },

    /**
     * Re-index SID items
     */
    reindexSIDItems: function () {
        const tbody = document.getElementById('sid-tbody');
        const rows = tbody.querySelectorAll('.npk-table-row');

        rows.forEach((row, newIndex) => {
            row.dataset.index = newIndex;

            const sidInput = row.querySelector('input');
            const deleteBtn = row.querySelector('.npk-delete-btn');

            if (sidInput) {
                sidInput.id = `sid_value_${newIndex}`;
            }
            if (deleteBtn) {
                deleteBtn.setAttribute('onclick', `FormTemplateNPK.deleteSIDItem(${newIndex})`);
            }
        });
    },

    /**
     * Create prorate section with table
     */
    createProrateSection: function (prorateData) {
        const section = document.createElement('div');
        section.className = 'npk-section';

        const headerWithButton = document.createElement('div');
        headerWithButton.className = 'npk-table-header';

        const headerDiv = document.createElement('div');
        headerDiv.innerHTML = `
            <label class="npk-label">
                <i class="fas fa-percentage"></i>
                Prorate Information
            </label>
            <small class="npk-help-text">Prorate values for billing adjustments</small>
        `;

        const addButton = document.createElement('button');
        addButton.type = 'button';
        addButton.className = 'npk-add-btn';
        addButton.innerHTML = 'Tambah';
        addButton.onclick = () => this.addProrateItem();

        headerWithButton.appendChild(headerDiv);
        headerWithButton.appendChild(addButton);

        const tableWrapper = document.createElement('div');
        tableWrapper.className = 'npk-table-wrapper';

        const table = document.createElement('table');
        table.className = 'npk-table';
        table.id = 'prorate-table';

        // Table header
        table.innerHTML = `
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Hari</th>
                    <th class="npk-table-action">Action</th>
                </tr>
            </thead>
            <tbody id="prorate-tbody">
            </tbody>
        `;

        const tbody = table.querySelector('#prorate-tbody');

        // ✅ HANDLE BOTH ARRAY AND OBJECT FORMAT WITH SORTING
        if (prorateData) {
            const sortedData = this.sortProrateData(prorateData);

            if (sortedData.length > 0) {
                sortedData.forEach((item, index) => {
                    tbody.appendChild(this.createProrateRow(item, index));
                });
            } else {
                tbody.innerHTML = this.getEmptyRowHTML('prorate', 3);
            }
        } else {
            tbody.innerHTML = this.getEmptyRowHTML('prorate', 3);
        }

        tableWrapper.appendChild(table);
        section.appendChild(headerWithButton);
        section.appendChild(tableWrapper);

        return section;
    },

    /**
     * ✅ Sort prorate data (handles both array and object format)
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
     * ✅ Compare two period strings (e.g., "Maret 2025" vs "April 2025")
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
     * ✅ Parse period string into {month, year}
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
     * ✅ Convert month name to number
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
     * Create single prorate row
     */
    createProrateRow: function (item, index) {
        const tr = document.createElement('tr');
        tr.className = 'npk-table-row';
        tr.dataset.index = index;

        const period = typeof item === 'object' ? (item.period || '') : '';
        const value = typeof item === 'object' ? (item.value || 0) : (item || 0);

        // ✅ Format value with thousand separator
        const formattedValue = this.formatNumber(value);

        tr.innerHTML = `
            <td>
                <input type="text" 
                       class="npk-table-input prorate-period-input" 
                       id="prorate_period_${index}"
                       value="${period}"
                       placeholder="Contoh: Maret 2024">
            </td>
            <td>
                <input type="text" 
                       class="npk-table-input prorate-value-input npk-number-input" 
                       id="prorate_value_${index}"
                       value="${formattedValue}"
                       placeholder="0"
                       style="text-align: center;">
            </td>
            <td class="npk-table-action">
                <button type="button" class="npk-delete-btn" onclick="FormTemplateNPK.deleteProrateItem(${index})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        return tr;
    },

    /**
     * Add new prorate item
     */
    addProrateItem: function () {
        const tbody = document.getElementById('prorate-tbody');

        // Remove empty message if exists
        const emptyRow = tbody.querySelector('.npk-table-empty');
        if (emptyRow) {
            emptyRow.remove();
        }

        const existingRows = tbody.querySelectorAll('.npk-table-row');
        const newIndex = existingRows.length;

        const newRow = this.createProrateRow({ period: '', value: 0 }, newIndex);
        tbody.appendChild(newRow);

        // ✅ Setup number formatting for the new input
        setTimeout(() => {
            const periodInput = document.getElementById(`prorate_period_${newIndex}`);
            const valueInput = document.getElementById(`prorate_value_${newIndex}`);

            this.setupNumberFormatting(valueInput);
            periodInput?.focus();
        }, 100);
    },

    /**
     * Delete prorate item
     */
    deleteProrateItem: function (index) {
        if (!confirm('Hapus prorate ini?')) return;

        const tbody = document.getElementById('prorate-tbody');
        const row = tbody.querySelector(`[data-index="${index}"]`);

        if (row) {
            row.remove();
        }

        this.reindexProrateItems();

        const remainingRows = tbody.querySelectorAll('.npk-table-row');
        if (remainingRows.length === 0) {
            tbody.innerHTML = this.getEmptyRowHTML('prorate', 3);
        }
    },

    /**
     * Re-index prorate items
     */
    reindexProrateItems: function () {
        const tbody = document.getElementById('prorate-tbody');
        const rows = tbody.querySelectorAll('.npk-table-row');

        rows.forEach((row, newIndex) => {
            row.dataset.index = newIndex;

            const periodInput = row.querySelector('.prorate-period-input');
            const valueInput = row.querySelector('.prorate-value-input');
            const deleteBtn = row.querySelector('.npk-delete-btn');

            periodInput.id = `prorate_period_${newIndex}`;
            valueInput.id = `prorate_value_${newIndex}`;
            deleteBtn.setAttribute('onclick', `FormTemplateNPK.deleteProrateItem(${newIndex})`);
        });
    },

    /**
     * Create usage section with table
     */
    createUsageSection: function (usageData) {
        const section = document.createElement('div');
        section.className = 'npk-section';

        const headerWithButton = document.createElement('div');
        headerWithButton.className = 'npk-table-header';

        const headerDiv = document.createElement('div');
        headerDiv.innerHTML = `
            <label class="npk-label">
                <i class="fas fa-calendar-alt"></i>
                Monthly Usage Values
            </label>
            <small class="npk-help-text">Enter the usage value for each month</small>
        `;

        const addButton = document.createElement('button');
        addButton.type = 'button';
        addButton.className = 'npk-add-btn';
        addButton.innerHTML = 'Tambah';
        addButton.onclick = () => this.addUsageItem();

        headerWithButton.appendChild(headerDiv);
        headerWithButton.appendChild(addButton);

        const tableWrapper = document.createElement('div');
        tableWrapper.className = 'npk-table-wrapper';

        const table = document.createElement('table');
        table.className = 'npk-table';
        table.id = 'usage-table';

        // Table header
        table.innerHTML = `
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th>Harga</th>
                    <th class="npk-table-action">Action</th>
                </tr>
            </thead>
            <tbody id="usage-tbody">
            </tbody>
        `;

        const tbody = table.querySelector('#usage-tbody');
        tbody.innerHTML = this.createUsageRows(usageData);

        tableWrapper.appendChild(table);
        section.appendChild(headerWithButton);
        section.appendChild(tableWrapper);

        return section;
    },

    /**
     * ✅ Create usage rows with sorting and number formatting
     */
    createUsageRows: function (usageData) {
        if (!usageData || Object.keys(usageData).length === 0) {
            return this.getEmptyRowHTML('usage', 3);
        }

        // ✅ Sort entries by month/year before rendering
        const sortedEntries = Object.entries(usageData).sort((a, b) => {
            return this.comparePeriods(a[0], b[0]);
        });

        let html = '';
        sortedEntries.forEach(([month, value], index) => {
            // ✅ Format value with thousand separator
            const formattedValue = this.formatNumber(value);

            html += `
                <tr class="npk-table-row" data-index="${index}">
                    <td>
                        <input type="text" 
                               class="npk-table-input usage-month-input" 
                               id="usage_month_${index}"
                               value="${month}"
                               placeholder="Contoh: Februari 2025">
                    </td>
                    <td>
                        <input type="text" 
                               class="npk-table-input usage-value-input npk-number-input" 
                               id="usage_value_${index}"
                               value="${formattedValue}"
                               placeholder="0"
                               style="text-align: right; font-weight: 600;">
                    </td>
                    <td class="npk-table-action">
                        <button type="button" class="npk-delete-btn" onclick="FormTemplateNPK.deleteUsageItem(${index})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        return html;
    },

    /**
     * ✅ Get empty row HTML
     */
    getEmptyRowHTML: function (type, colspan) {
        const messages = {
            'prorate': 'Tidak ada data prorate',
            'usage': 'Tidak ada data usage',
            'sid': 'Tidak ada Service ID'
        };

        return `
            <tr class="npk-table-empty">
                <td colspan="${colspan}">
                    <i class="fas fa-inbox"></i>
                    <p>${messages[type] || 'Tidak ada data'}</p>
                    <small>Klik tombol "Tambah" untuk menambah data</small>
                </td>
            </tr>
        `;
    },

    /**
     * Add new usage item
     */
    addUsageItem: function () {
        const tbody = document.getElementById('usage-tbody');

        // Remove empty message if exists
        const emptyRow = tbody.querySelector('.npk-table-empty');
        if (emptyRow) {
            emptyRow.remove();
        }

        const existingRows = tbody.querySelectorAll('.npk-table-row');
        const newIndex = existingRows.length;

        const tr = document.createElement('tr');
        tr.className = 'npk-table-row';
        tr.dataset.index = newIndex;
        tr.innerHTML = `
            <td>
                <input type="text" 
                       class="npk-table-input usage-month-input" 
                       id="usage_month_${newIndex}"
                       value=""
                       placeholder="Contoh: Februari 2025">
            </td>
            <td>
                <input type="text" 
                       class="npk-table-input usage-value-input npk-number-input" 
                       id="usage_value_${newIndex}"
                       value="0"
                       placeholder="0"
                       style="text-align: right; font-weight: 600;">
            </td>
            <td class="npk-table-action">
                <button type="button" class="npk-delete-btn" onclick="FormTemplateNPK.deleteUsageItem(${newIndex})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;

        tbody.appendChild(tr);

        // ✅ Setup number formatting for the new input
        setTimeout(() => {
            const monthInput = document.getElementById(`usage_month_${newIndex}`);
            const valueInput = document.getElementById(`usage_value_${newIndex}`);

            this.setupNumberFormatting(valueInput);
            monthInput?.focus();
        }, 100);
    },

    /**
     * Delete usage item
     */
    deleteUsageItem: function (index) {
        if (!confirm('Hapus periode ini?')) return;

        const tbody = document.getElementById('usage-tbody');
        const row = tbody.querySelector(`[data-index="${index}"]`);

        if (row) {
            row.remove();
        }

        this.reindexUsageItems();

        const remainingRows = tbody.querySelectorAll('.npk-table-row');
        if (remainingRows.length === 0) {
            tbody.innerHTML = this.getEmptyRowHTML('usage', 3);
        }
    },

    /**
     * Re-index usage items
     */
    reindexUsageItems: function () {
        const tbody = document.getElementById('usage-tbody');
        const rows = tbody.querySelectorAll('.npk-table-row');

        rows.forEach((row, newIndex) => {
            row.dataset.index = newIndex;

            const monthInput = row.querySelector('.usage-month-input');
            const valueInput = row.querySelector('.usage-value-input');
            const deleteBtn = row.querySelector('.npk-delete-btn');

            monthInput.id = `usage_month_${newIndex}`;
            valueInput.id = `usage_value_${newIndex}`;
            deleteBtn.setAttribute('onclick', `FormTemplateNPK.deleteUsageItem(${newIndex})`);
        });
    },

    /**
     * ✅ Collect data from form with number parsing
     */
    collectData: function () {
        const data = {
            SID: null,
            prorate: [],
            nilai_satuan_usage: {}
        };

        // Get SID from table (support multiple SIDs)
        const sidTbody = document.getElementById('sid-tbody');
        if (sidTbody) {
            const sidRows = sidTbody.querySelectorAll('.npk-table-row');
            if (sidRows.length === 1) {
                // Single SID - return as string
                const sidInput = document.getElementById('sid_value_0');
                if (sidInput) {
                    data.SID = sidInput.value || null;
                }
            } else if (sidRows.length > 1) {
                // Multiple SIDs - return as array
                const sidArray = [];
                sidRows.forEach((row, index) => {
                    const sidInput = document.getElementById(`sid_value_${index}`);
                    if (sidInput && sidInput.value) {
                        sidArray.push(sidInput.value);
                    }
                });
                data.SID = sidArray.length > 0 ? (sidArray.length === 1 ? sidArray[0] : sidArray) : null;
            }
        } else {
            // Fallback to old format
            const sidInput = document.getElementById('npk_sid');
            if (sidInput) {
                data.SID = sidInput.value || null;
            }
        }

        // ✅ Get prorate values - PARSE formatted numbers
        const prorateTbody = document.getElementById('prorate-tbody');
        if (prorateTbody) {
            const prorateRows = prorateTbody.querySelectorAll('.npk-table-row');
            prorateRows.forEach((row, index) => {
                const period = document.getElementById(`prorate_period_${index}`)?.value;
                const valueStr = document.getElementById(`prorate_value_${index}`)?.value;

                // ✅ Parse back to raw number (remove dots)
                const value = this.parseNumber(valueStr);

                if (period) {
                    data.prorate.push({ period, value });
                }
            });
        }

        // ✅ Get usage values - PARSE formatted numbers
        const usageTbody = document.getElementById('usage-tbody');
        if (usageTbody) {
            const usageRows = usageTbody.querySelectorAll('.npk-table-row');
            usageRows.forEach((row, index) => {
                const month = document.getElementById(`usage_month_${index}`)?.value;
                const valueStr = document.getElementById(`usage_value_${index}`)?.value;

                // ✅ Parse back to raw number (remove dots)
                const value = this.parseNumber(valueStr);

                if (month) {
                    data.nilai_satuan_usage[month] = value;
                }
            });
        }

        return data;
    }
};