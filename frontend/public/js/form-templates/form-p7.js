/**
 * FormTemplateP7 - Global Data P7 component.
 * UI and data match form-kontrak; uses labels "Nomor Surat Penetapan Calon Mitra" and
 * "Tanggal Surat Penetapan Calon Mitra" (aligned with advance-review-handler.js).
 * Requires FormTemplateKontrak to be loaded.
 */
(function () {
    'use strict';

    const FormTemplateP7 = {
        meta: {
            title: 'Data P7',
            description: 'Validasi dan isi data P7 (Surat Penetapan Calon Mitra, Tanggal, Detail Pembayaran, Pejabat).',
        },

        escapeHtml: function (s) {
            if (s == null) return '';
            return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        },

        /**
         * P7-specific section: Nomor Surat Penetapan Calon Mitra, Tanggal Surat Penetapan Calon Mitra.
         * Same UI structure as form-kontrak (kontrak-section, kontrak-field, kontrak-table).
         */
        createSuratPenetapanSection: function (data) {
            const section = document.createElement('div');
            section.className = 'kontrak-section';
            const Kontrak = typeof FormTemplateKontrak !== 'undefined' ? FormTemplateKontrak : null;
            // Backend/GroundTruth use nomor_surat_penetapan_calon_mitra / tanggal_surat_penetapan_calon_mitra; also support nomor/tanggal for advance-review-handler
            const nomor = (data && (
                data.nomor_surat_penetapan_calon_mitra != null ? data.nomor_surat_penetapan_calon_mitra :
                data.nomor != null ? data.nomor : data.nomor_p7
            )) || '';
            const tanggalValue = (data && (
                data.tanggal_surat_penetapan_calon_mitra != null ? data.tanggal_surat_penetapan_calon_mitra :
                data.tanggal != null ? data.tanggal : data.tanggal_p7
            )) || '';
            const dateForInput = Kontrak && Kontrak.convertDateToInput ? Kontrak.convertDateToInput(tanggalValue) : (tanggalValue || '');
            section.innerHTML = `
                <div class="kontrak-field">
                    <label class="kontrak-label">Surat Penetapan Calon Mitra</label>
                    <small class="kontrak-help-text">Nomor dan Tanggal Surat Penetapan Calon Mitra</small>
                    <div class="kontrak-field" style="margin-top: 0.75rem;">
                        <label class="kontrak-label" for="p7_nomor">Nomor Surat Penetapan Calon Mitra</label>
                        <input type="text"
                               class="kontrak-table-input"
                               id="p7_nomor"
                               value="${this.escapeHtml(nomor)}"
                               placeholder="Nomor Surat Penetapan Calon Mitra"
                               style="display: block; width: 100%; margin-top: 0.25rem;">
                    </div>
                    <div class="kontrak-field" style="margin-top: 0.75rem;">
                        <label class="kontrak-label" for="p7_tanggal">Tanggal Surat Penetapan Calon Mitra</label>
                        <input type="date"
                               class="kontrak-table-input"
                               id="p7_tanggal"
                               value="${this.escapeHtml(dateForInput)}"
                               style="display: block; width: 100%; margin-top: 0.25rem;">
                    </div>
                </div>
            `;
            return section;
        },

        /**
         * Main render: Surat Penetapan (nomor/tanggal) + TanggalTable + DetailPembayaranTable + PejabatSection.
         */
        render: function (data, container) {
            container.innerHTML = '';
            const wrapper = document.createElement('div');
            wrapper.className = 'template-kontrak template-p7';

            const Kontrak = typeof FormTemplateKontrak !== 'undefined' ? FormTemplateKontrak : null;
            if (!Kontrak) {
                container.appendChild(document.createTextNode('FormTemplateKontrak is required for Data P7.'));
                return;
            }

            const safeData = data && typeof data === 'object' ? data : {};

            wrapper.appendChild(this.createSuratPenetapanSection(safeData));
            wrapper.appendChild(Kontrak.createTanggalTable(safeData));
            wrapper.appendChild(Kontrak.createDetailPembayaranTable(safeData));
            wrapper.appendChild(Kontrak.createPejabatSection(safeData.pejabat_penanda_tangan || {}));

            container.appendChild(wrapper);

            if (Kontrak.initAutoResize) {
                setTimeout(function () { Kontrak.initAutoResize(); }, 100);
            }
            if (Kontrak.attachTanggalDurationListeners) {
                setTimeout(function () { Kontrak.attachTanggalDurationListeners(wrapper); }, 100);
            }
        },

        /**
         * Collect data: nomor, tanggal (P7) + Tanggal, Detail Pembayaran, Pejabat (same shape as Kontrak for ground truth).
         */
        collectData: function () {
            const Kontrak = typeof FormTemplateKontrak !== 'undefined' ? FormTemplateKontrak : null;
            const convertDateFromInput = Kontrak && Kontrak.convertDateFromInput
                ? function (s) { return Kontrak.convertDateFromInput(s); }
                : function (s) {
                    if (!s || typeof s !== 'string') return '';
                    const p = s.split('-');
                    if (p.length !== 3) return s;
                    return p[2] + '-' + p[1] + '-' + p[0];
                };
            const parseDurationFromDb = Kontrak && Kontrak.parseDurationFromDb
                ? function (s) { return Kontrak.parseDurationFromDb(s); }
                : function () { return { value: 0, unit: 'Bulan' }; };

            const data = {
                nomor: document.getElementById('p7_nomor')?.value?.trim() || null,
                tanggal: null,
                delivery: null,
                delivery_date: null,
                jangka_waktu: { start_date: null, end_date: null, duration: null },
                tanggal_kontrak: null,
                dpp_raw: parseFloat(document.getElementById('kontrak_dpp')?.value) || 0,
                harga_satuan_raw: parseFloat(document.getElementById('kontrak_harga_satuan')?.value) || 0,
                metode_pembayaran: document.getElementById('kontrak_metode')?.value?.trim() || null,
                terms_of_payment: document.getElementById('kontrak_terms')?.value?.trim() || null,
                pejabat_penanda_tangan: {
                    baut: { telkom: null, mitra: null },
                    bast: { telkom: null, mitra: null },
                    bapl: { telkom: null, mitra: null },
                    bard: { telkom: null, mitra: null },
                },
            };

            const p7Tanggal = document.getElementById('p7_tanggal')?.value;
            if (p7Tanggal) data.tanggal = convertDateFromInput(p7Tanggal);

            // Backend/GroundTruth expect these keys; advance-review-handler uses nomor/tanggal
            data.nomor_surat_penetapan_calon_mitra = data.nomor;
            data.tanggal_surat_penetapan_calon_mitra = data.tanggal;

            const tanggalKontrak = document.getElementById('kontrak_tanggal_kontrak')?.value;
            if (tanggalKontrak) data.tanggal_kontrak = convertDateFromInput(tanggalKontrak);

            const deliveryInput = document.getElementById('kontrak_delivery')?.value;
            if (deliveryInput) {
                const converted = convertDateFromInput(deliveryInput);
                data.delivery = converted;
                data.delivery_date = converted;
            }

            const startDate = document.getElementById('kontrak_start_date')?.value;
            const endDate = document.getElementById('kontrak_end_date')?.value;
            if (startDate) data.jangka_waktu.start_date = convertDateFromInput(startDate);
            if (endDate) data.jangka_waktu.end_date = convertDateFromInput(endDate);

            const durationInput = document.getElementById('kontrak_duration')?.value;
            if (durationInput && typeof durationInput === 'string' && durationInput.trim()) {
                const parsed = parseDurationFromDb(durationInput.trim());
                if (parsed.value >= 0) data.jangka_waktu.duration = parsed.value + ' ' + parsed.unit;
            }

            ['baut', 'bast', 'bapl', 'bard'].forEach(function (doc) {
                const telkom = document.getElementById('pejabat_' + doc + '_telkom')?.value;
                const mitra = document.getElementById('pejabat_' + doc + '_mitra')?.value;
                data.pejabat_penanda_tangan[doc].telkom = telkom?.trim() || null;
                data.pejabat_penanda_tangan[doc].mitra = mitra?.trim() || null;
            });

            return data;
        },
    };

    window.FormTemplateP7 = FormTemplateP7;
})();
