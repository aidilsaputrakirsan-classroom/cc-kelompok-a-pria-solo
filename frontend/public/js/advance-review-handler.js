// pdf-advance-result.js - Complete with Nested Object Handling + ROTATE FEATURE

pdfjsLib.GlobalWorkerOptions.workerSrc =
    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

// Global variables
let pdfDocument = null;
let PDF_PATH = '';
let CURRENT_TICKET_NUMBER = null;
let CURRENT_DOC_TYPE = null;
let pageRotations = {}; // State untuk menyimpan rotasi per halaman

// ============================================
// GROUND TRUTH RENDERING HELPERS (aligned with validate-ground-truth.js)
// ============================================

/**
 * Detect if a field is a date field by key name (same as validate-ground-truth.js)
 */
function isDateField(key) {
    const dateKeywords = ['tanggal', 'date', 'tgl', 'delivery'];
    return dateKeywords.some(keyword => key.toLowerCase().includes(keyword));
}

/**
 * Detect if a field is a "no order -> tanggal aktivasi" map (e.g. lampiran_baut).
 * Structure: object with string keys (no order) and string values (dd-mm-yyyy dates).
 * Only keys in knownKeys are rendered as this table - so tanggal_baut (single date or object)
 * is never shown as "Tanggal Aktivasi" table; only lampiran_baut is.
 */
function isOrderDateMapField(key, value) {
    if (typeof value !== 'object' || value === null || Array.isArray(value)) return false;
    const knownKeys = ['lampiran_baut'];
    return knownKeys.includes(key);
}

/**
 * Render read-only order-date table (No Order + Tanggal Aktivasi).
 * Same structure as validate-ground-truth.js but display-only.
 */
function renderOrderDateTableReadOnly(container, label, data) {
    const wrapper = document.createElement('div');
    wrapper.className = 'form-section order-date-table-wrapper';
    const header = document.createElement('div');
    header.className = 'form-section-header';
    header.textContent = label;
    wrapper.appendChild(header);

    const tableContainer = document.createElement('div');
    tableContainer.className = 'order-date-table-container';
    const table = document.createElement('table');
    table.className = 'order-date-table kontrak-table kontrak-table-static';
    table.innerHTML = `
        <thead>
            <tr>
                <th>No Order</th>
                <th>Tanggal Aktivasi</th>
            </tr>
        </thead>
        <tbody></tbody>
    `;
    const tbody = table.querySelector('tbody');
    Object.entries(data || {}).forEach(([noOrder, dateStr]) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><div class="kontrak-table-input" style="background: #f9fafb; cursor: default;">${escapeHtml(noOrder)}</div></td>
            <td><div class="kontrak-table-input" style="background: #f9fafb; cursor: default;">${cleanText(dateStr)}</div></td>
        `;
        tbody.appendChild(tr);
    });
    tableContainer.appendChild(table);
    wrapper.appendChild(tableContainer);
    container.appendChild(wrapper);
}

/**
 * Create a read-only form section with header and body (for nested objects).
 * Returns { section, body } so caller can append section to container and recurse into body.
 */
function createFormSectionReadOnly(title) {
    const section = document.createElement('div');
    section.className = 'form-section';
    const header = document.createElement('div');
    header.className = 'form-section-header';
    header.textContent = title;
    const body = document.createElement('div');
    body.className = 'form-section-body';
    section.appendChild(header);
    section.appendChild(body);
    return { section, body };
}

/**
 * Recursively render all ground truth fields read-only, matching validate-ground-truth.js behavior.
 * Renders: nested objects as sections, order-date maps as tables, scalars with _raw display, dates, arrays.
 */
function renderGroundTruthFieldsReadOnly(data, container) {
    if (!data || typeof data !== 'object') return;
    for (const [key, value] of Object.entries(data)) {
        if (key.startsWith('_') || key.endsWith('_raw') || key === '') continue;

        const label = formatFieldLabel(key);

        if (value === null || value === undefined) {
            container.appendChild(createSimpleField(label, '-'));
        } else if (typeof value === 'object' && !Array.isArray(value) && isOrderDateMapField(key, value)) {
            renderOrderDateTableReadOnly(container, label, value);
        } else if (typeof value === 'object' && !Array.isArray(value)) {
            const singleDate = getSingleTanggalValue(value);
            if (singleDate !== undefined && isDateField(key)) {
                container.appendChild(createSimpleDateField(label, singleDate));
            } else {
                const { section, body } = createFormSectionReadOnly(label);
                container.appendChild(section);
                renderGroundTruthFieldsReadOnly(value, body);
            }
        } else if (Array.isArray(value)) {
            const display = value.length === 0 ? '-' : (typeof value[0] === 'object' ? JSON.stringify(value, null, 2) : value.join(', '));
            container.appendChild(createSimpleField(label, display));
        } else {
            const rawKey = key + '_raw';
            let displayValue = data[rawKey] !== undefined ? data[rawKey] : value;
            if (isDateField(key) && displayValue) {
                container.appendChild(createSimpleDateField(label, displayValue));
            } else {
                container.appendChild(createSimpleField(label, displayValue));
            }
        }
    }
}

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

/**
 * Initialize Advance Result Viewer
 */
function initAdvanceResultViewer(pdfUrl, ticketNumber, docType) {
    console.log('📌 initAdvanceResultViewer called');
    console.log('   PDF URL:', pdfUrl);
    console.log('   Ticket:', ticketNumber);
    console.log('   Doc Type:', docType);

    PDF_PATH = pdfUrl;
    CURRENT_TICKET_NUMBER = ticketNumber;
    CURRENT_DOC_TYPE = docType;

    // Reset rotasi saat load dokumen baru
    pageRotations = {};

    // Show loading overlay
    showPageLoadingOverlay();

    Promise.all([
        loadAdvanceResultPDF(),
        loadAdvanceResultData()
    ]).then(() => {
        // Hide loading overlay when both PDF and data are loaded
        hidePageLoadingOverlay();
    }).catch(error => {
        console.error('Error initializing viewer:', error);
        hidePageLoadingOverlay();
        showError(`Gagal memuat dokumen: ${error.message}`);
    });
}

/**
 * Load dan render PDF (SEQUENTIAL untuk urutan yang benar)
 */
async function loadAdvanceResultPDF() {
    try {
        console.log('📄 Loading PDF from:', PDF_PATH);
        showLoading();

        pdfDocument = await pdfjsLib.getDocument(PDF_PATH).promise;
        console.log(`✅ PDF Loaded. Total Pages: ${pdfDocument.numPages}`);

        const container = document.getElementById('pdf-container');
        container.innerHTML = '';

        // SEQUENTIAL RENDERING untuk urutan halaman yang benar
        for (let pageNum = 1; pageNum <= pdfDocument.numPages; pageNum++) {
            try {
                const page = await pdfDocument.getPage(pageNum);
                const pageElement = await renderSimplePage(page, pageNum);
                container.appendChild(pageElement);
                console.log(`✅ Page ${pageNum}/${pdfDocument.numPages} rendered`);
            } catch (error) {
                console.error(`❌ Failed to render page ${pageNum}:`, error);
            }
        }

        document.getElementById('loading-indicator').style.display = 'none';
        document.getElementById('pdf-container').style.display = 'block';

    } catch (error) {
        console.error('❌ Error loading PDF:', error);
        showError(`Gagal memuat PDF: ${error.message}`);
    }
}

/**
 * [ROTATE FEATURE] Render halaman PDF dengan support rotasi
 */
async function renderSimplePage(page, pageNum) {
    // 1. Ambil rotasi dari state (Default 0)
    const rotation = pageRotations[pageNum] || 0;
    const scale = 1.5;

    // 2. Terapkan rotasi ke viewport
    const viewport = page.getViewport({ scale, rotation });

    const pageCard = document.createElement('div');
    pageCard.className = 'pdf-page-card';
    pageCard.id = `page-${pageNum}`;

    const pageHeader = document.createElement('div');
    pageHeader.className = 'page-header';
    pageHeader.innerHTML = `
        <div class="page-header-info">
            <i class="bi bi-file-text"></i>
            <span class="page-number">Halaman ${pageNum} dari ${pdfDocument.numPages} (Rotasi: ${rotation}°)</span>
        </div>
        <button class="rotate-btn" onclick="rotateAdvanceResultPage(${pageNum})" title="Putar Halaman" type="button">
            <i class="bi bi-arrow-clockwise"></i> Putar
        </button>
    `;
    pageCard.appendChild(pageHeader);

    const canvas = document.createElement('canvas');
    canvas.className = 'pdf-canvas';
    canvas.id = `pdf-canvas-${pageNum}`;
    canvas.width = viewport.width;
    canvas.height = viewport.height;

    const context = canvas.getContext('2d', { alpha: false });
    await page.render({ canvasContext: context, viewport: viewport }).promise;

    const pageContent = document.createElement('div');
    pageContent.className = 'page-content';
    pageContent.id = `page-content-${pageNum}`;
    pageContent.appendChild(canvas);
    pageCard.appendChild(pageContent);

    return pageCard;
}

/**
 * [ROTATE FEATURE] Logic Putar Halaman
 */
async function rotateAdvanceResultPage(pageNum) {
    if (!pdfDocument) {
        console.error('❌ PDF Document not loaded');
        return;
    }

    const btnIcon = document.querySelector(`#page-${pageNum} .rotate-btn i`);
    if (btnIcon) btnIcon.classList.add('fa-spin');

    try {
        // 1. Update State (tambah 90 derajat)
        const currentRotation = pageRotations[pageNum] || 0;
        const newRotation = (currentRotation + 90) % 360;
        pageRotations[pageNum] = newRotation;

        console.log(`🔄 Rotating Page ${pageNum}: ${currentRotation}° -> ${newRotation}°`);

        // 2. Render ulang dengan rotasi baru
        const page = await pdfDocument.getPage(pageNum);
        const scale = 1.5;
        const viewport = page.getViewport({ scale, rotation: newRotation });

        const canvas = document.createElement('canvas');
        canvas.className = 'pdf-canvas';
        canvas.id = `pdf-canvas-${pageNum}`;
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        const context = canvas.getContext('2d', { alpha: false });

        await page.render({
            canvasContext: context,
            viewport: viewport,
            intent: 'display'
        }).promise;

        // 3. Update DOM
        const pageContent = document.getElementById(`page-content-${pageNum}`);
        if (pageContent) {
            pageContent.innerHTML = '';
            pageContent.appendChild(canvas);
        }

        // Update label header
        const pageNumberSpan = document.querySelector(`#page-${pageNum} .page-number`);
        if (pageNumberSpan) {
            pageNumberSpan.textContent = `Halaman ${pageNum} dari ${pdfDocument.numPages} (Rotasi: ${newRotation}°)`;
        }

    } catch (error) {
        console.error('❌ Error rotating page:', error);
        alert('Gagal memutar halaman: ' + error.message);
    } finally {
        if (btnIcon) btnIcon.classList.remove('fa-spin');
    }
}

/**
 * Load advance result data (Ground Truth + Stages)
 */
async function loadAdvanceResultData() {
    try {
        const apiUrl = `/projess/api/advance-result/${CURRENT_TICKET_NUMBER}/${CURRENT_DOC_TYPE}/data`;
        console.log('📡 Fetching data from:', apiUrl);

        const response = await fetch(apiUrl);

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log('✅ Data received from backend:', data);

        // DEBUG: Log ground truth structure
        console.group('🔍 DEBUG: Ground Truth Structure');
        console.log('Ground Truth Data:', data.ground_truth);
        console.log('Keys:', Object.keys(data.ground_truth || {}));
        console.groupEnd();

        // DEBUG: Log review stages structure with all issues (including is_valid status)
        console.log('═══════════════════════════════════════════════════════════');
        console.log('🔍 DEBUG: Review Data Structure');
        console.log('═══════════════════════════════════════════════════════════');
        console.log('Review Stages:', data.review_stages);
        console.log('Review Data:', data.review_data);
        if (data.review_stages && Array.isArray(data.review_stages)) {
            console.log('Total Stages:', data.review_stages.length);
            data.review_stages.forEach((stage, idx) => {
                console.log(`\n📋 Stage ${idx + 1}:`, stage.stage_name || `Stage ${stage.stage_id}`);
                console.log('   Full Stage Object:', stage);
                if (stage.issues && Array.isArray(stage.issues)) {
                    console.log(`   Total Issues: ${stage.issues.length}`);
                    stage.issues.forEach((issue, issueIdx) => {
                        const desc = issue.description || issue.Description || issue.notes || issue.label || '-';
                        const isValid = issue.is_valid !== undefined ? issue.is_valid : 'N/A';
                        console.log(`\n   📝 Issue ${issueIdx + 1}:`);
                        console.log(`      Label: ${issue.label || 'N/A'}`);
                        console.log(`      Description: ${desc}`);
                        console.log(`      is_valid: ${isValid}`);
                        console.log(`      Status: ${issue.status || 'N/A'}`);
                        console.log(`      Full Issue Object:`, issue);
                    });
                } else {
                    console.log('   No issues array found in stage');
                }
            });
        } else {
            console.log('No review_stages array found in data');
        }
        console.log('═══════════════════════════════════════════════════════════');

        // Render Ground Truth
        // First, check if ground_truth has a nested "Ground Truth" key (from backend wrapper)
        let groundTruthToRender = data.ground_truth;
        if (data.ground_truth && data.ground_truth['Ground Truth']) {
            console.log('📦 Detected nested "Ground Truth" wrapper - unwrapping');
            groundTruthToRender = data.ground_truth['Ground Truth'];
        }

        // Check if ground_truth contains multiple doc_types (NOPES, KL, SP, WO, NPK, BAST, etc.)
        if (groundTruthToRender && typeof groundTruthToRender === 'object') {
            const keys = Object.keys(groundTruthToRender);
            const docTypeKeywords = ['NOPES', 'KL', 'SP', 'WO', 'Nota Pesanan', 'Kontrak Layanan', 'Surat Pesanan', 'Work Order', 'NPK', 'BAST', 'BAUT', 'BARD', 'P7', 'PR'];
            
            // Check if keys are doc_types (skip _metadata)
            const hasDocTypeKeys = keys.some(key => docTypeKeywords.includes(key) || !key.startsWith('_'));
            
            if (hasDocTypeKeys && keys.length > 1) {
                // This is ALL ground truths organized by doc_type
                console.log('🎨 Rendering ALL Ground Truths by Document Type');
                renderAllGroundTruthsByDocType(groundTruthToRender);
            } else {
                // Single ground truth object
                console.log('🎨 Rendering Single Ground Truth');
                renderGroundTruthFlexible(groundTruthToRender);
            }
        } else if (groundTruthToRender) {
            console.log('🎨 Rendering Ground Truth');
            renderGroundTruthFlexible(groundTruthToRender);
        } else {
            console.warn('⚠️ No ground truth data received');
        }

        // Render Stage Cards
        const stages = data.review_stages || data.review_data;
        if (stages) {
            console.log('📊 Rendering Stage Cards...');
            
            // DEBUG: Log all stages and issues with descriptions (including is_valid status)
            console.log('═══════════════════════════════════════════════════════════');
            console.log('🔍 DEBUG: Review Stages & Issues (Before Rendering)');
            console.log('═══════════════════════════════════════════════════════════');
            stages.forEach((stage, stageIndex) => {
                console.log(`\n📋 Stage ${stageIndex + 1}: ${stage.stage_name || `Stage ${stage.stage_id}`}`);
                if (stage.issues && Array.isArray(stage.issues)) {
                    console.log(`   Total Issues: ${stage.issues.length}`);
                    stage.issues.forEach((issue, issueIndex) => {
                        const description = issue.description || issue.Description || issue.notes || issue.label || '-';
                        const isValid = issue.is_valid !== undefined ? issue.is_valid : 'N/A';
                        console.log(`\n   📝 Issue ${issueIndex + 1}:`);
                        console.log(`      Label: ${issue.label || 'N/A'}`);
                        console.log(`      Description: ${description}`);
                        console.log(`      is_valid: ${isValid}`);
                        console.log(`      Status: ${issue.status || 'N/A'}`);
                    });
                } else {
                    console.log('   No issues in this stage');
                }
            });
            console.log('═══════════════════════════════════════════════════════════\n');
            
            renderStageCards(stages);
        } else {
            console.warn('⚠️ No stage data received');
        }

    } catch (error) {
        console.error('❌ Error loading advance result data:', error);
        const container = document.getElementById('ground-truth-container');
        container.innerHTML = `
            <div class="alert alert-warning" style="width: 100%;">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Gagal memuat data review: ${error.message}
            </div>
        `;
    }
}

/**
 * ✅ Render ALL ground truths by document type
 */
function renderAllGroundTruthsByDocType(allGroundTruths) {
    const container = document.getElementById('ground-truth-container');
    container.innerHTML = '';

    if (!allGroundTruths || Object.keys(allGroundTruths).length === 0) {
        container.innerHTML = `
            <div class="gt-empty-state">
                <i class="bi bi-inbox"></i>
                <p>Tidak ada data Ground Truth</p>
            </div>
        `;
        return;
    }

    console.log('📚 Rendering ALL ground truths. Doc types:', Object.keys(allGroundTruths));

    const wrapper = document.createElement('div');
    wrapper.className = 'all-ground-truths-container';

    // Sort doc_types by priority (skip _metadata)
    const docTypeOrder = ['Kontrak Layanan', 'KL', 'Nota Pesanan', 'NOPES', 'Surat Pesanan', 'SP', 'Work Order', 'WO', 'NPK', 'BAST', 'BAUT', 'BARD', 'P7', 'PR'];
    const sortedDocTypes = Object.keys(allGroundTruths)
        .filter(key => !key.startsWith('_')) // Skip metadata and internal fields
        .sort((a, b) => {
            const aIdx = docTypeOrder.indexOf(a);
            const bIdx = docTypeOrder.indexOf(b);
            return (aIdx === -1 ? 999 : aIdx) - (bIdx === -1 ? 999 : bIdx);
        });

    // Render each doc_type
    sortedDocTypes.forEach((docType, idx) => {
        const groundTruthData = allGroundTruths[docType];
        
        if (!groundTruthData || Object.keys(groundTruthData).length === 0) {
            return;
        }

        // Create section for this doc_type
        const section = document.createElement('div');
        section.className = 'gt-doc-type-section';

        // Header with doc_type name and field count
        const header = document.createElement('div');
        header.className = 'gt-doc-type-header';
        const fieldCount = Object.keys(groundTruthData).length;
        header.innerHTML = `
            <div class="gt-doc-type-info">
                <i class="bi bi-file-text"></i>
                <span class="gt-doc-type-title">${docType}</span>
                <span class="gt-field-count">${fieldCount} fields</span>
            </div>
        `;
        section.appendChild(header);

        // Content with all fields
        const content = document.createElement('div');
        content.className = 'gt-doc-type-content';

        const fieldsWrapper = document.createElement('div');
        fieldsWrapper.className = 'template-kontrak';
        renderAllGroundTruthFields(groundTruthData, fieldsWrapper);
        content.appendChild(fieldsWrapper);

        section.appendChild(content);
        wrapper.appendChild(section);

        // Add divider between sections (except after last)
        if (idx < sortedDocTypes.length - 1) {
            const divider = document.createElement('div');
            divider.className = 'gt-section-divider';
            wrapper.appendChild(divider);
        }
    });

    container.appendChild(wrapper);
}

/**
 * ✅ UNIVERSAL RENDERING: Render ALL ground truth fields dynamically
 * No filtering by doc_type - shows all available fields based on ticket_id
 */
function renderGroundTruthFlexible(groundTruth) {
    const container = document.getElementById('ground-truth-container');
    container.innerHTML = '';

    if (!groundTruth || Object.keys(groundTruth).length === 0) {
        container.innerHTML = `
            <div class="gt-empty-state">
                <i class="bi bi-inbox"></i>
                <p>Tidak ada data Ground Truth</p>
            </div>
        `;
        return;
    }

    console.log('🔍 Fields received from BE:', Object.keys(groundTruth));
    console.log('📄 Current Doc Type:', CURRENT_DOC_TYPE);
    console.log('🎨 Rendering ALL fields (no doc_type filtering)');

    const wrapper = document.createElement('div');
    wrapper.className = 'template-kontrak';

    // ========================================
    // RENDER ALL FIELDS DYNAMICALLY (NO DOC_TYPE FILTERING)
    // ========================================
    renderAllGroundTruthFields(groundTruth, wrapper);

    container.appendChild(wrapper);
}

/**
 * Render all ground truth fields dynamically
 * Uses smart detection to render fields based on their structure
 */
function renderAllGroundTruthFields(groundTruth, wrapper) {
    // Track rendered fields to avoid duplicates
    const renderedFields = new Set();
    
    // ========================================
    // 1. Standard Contract Fields (using template functions when available)
    // ========================================
    
    // Judul Project
    if (groundTruth.judul_project !== undefined && !renderedFields.has('judul_project')) {
        wrapper.appendChild(FormTemplateKontrakReadOnly.createJudulProjectField(groundTruth.judul_project));
        renderedFields.add('judul_project');
    }
    
    // Nama Pelanggan
    if (groundTruth.nama_pelanggan !== undefined && !renderedFields.has('nama_pelanggan')) {
        wrapper.appendChild(FormTemplateKontrakReadOnly.createNamaPelangganField(groundTruth.nama_pelanggan));
        renderedFields.add('nama_pelanggan');
    }
    
    // Nomor Kontrak
    if ((groundTruth.nomor_surat_utama !== undefined || groundTruth.nomor_surat_lainnya !== undefined) 
        && !renderedFields.has('nomor_surat')) {
        wrapper.appendChild(FormTemplateKontrakReadOnly.createNomorKontrakTable(
            groundTruth.nomor_surat_utama,
            groundTruth.nomor_surat_lainnya
        ));
        renderedFields.add('nomor_surat_utama');
        renderedFields.add('nomor_surat_lainnya');
    }
    
    // Tanggal Fields
    if (hasAnyTanggalField(groundTruth) && !renderedFields.has('tanggal')) {
        wrapper.appendChild(FormTemplateKontrakReadOnly.createTanggalTable(groundTruth));
        renderedFields.add('tanggal_kontrak');
        renderedFields.add('delivery');
        renderedFields.add('delivery_date');
        renderedFields.add('jangka_waktu');
    }
    
    // Pembayaran Fields
    if (hasAnyPembayaranField(groundTruth) && !renderedFields.has('pembayaran')) {
        wrapper.appendChild(FormTemplateKontrakReadOnly.createDetailPembayaranTable(groundTruth));
        renderedFields.add('dpp_raw');
        renderedFields.add('harga_satuan_raw');
        renderedFields.add('metode_pembayaran');
        renderedFields.add('terms_of_payment');
    }
    
    // Detail Rekening
    if (groundTruth.detail_rekening !== undefined && !renderedFields.has('detail_rekening')) {
        wrapper.appendChild(FormTemplateKontrakReadOnly.createDetailRekeningTable(groundTruth.detail_rekening));
        renderedFields.add('detail_rekening');
    }
    
    // Ketentuan Layanan (SLG & Skema Bisnis)
    if ((groundTruth.slg !== undefined || groundTruth.skema_bisnis !== undefined) 
        && !renderedFields.has('ketentuan_layanan')) {
        wrapper.appendChild(FormTemplateKontrakReadOnly.createKetentuanLayananTable(
            groundTruth.slg,
            groundTruth.skema_bisnis
        ));
        renderedFields.add('slg');
        renderedFields.add('skema_bisnis');
    }
    
    // Rujukan
    if (groundTruth.rujukan !== undefined && groundTruth.rujukan !== null 
        && Object.keys(groundTruth.rujukan).length > 0 
        && !renderedFields.has('rujukan')) {
        wrapper.appendChild(FormTemplateKontrakReadOnly.createRujukanSection(groundTruth.rujukan));
        renderedFields.add('rujukan');
    }
    
    // Pejabat Penanda Tangan
    if (groundTruth.pejabat_penanda_tangan !== undefined && !renderedFields.has('pejabat_penanda_tangan')) {
        wrapper.appendChild(FormTemplateKontrakReadOnly.createPejabatSection(groundTruth.pejabat_penanda_tangan));
        renderedFields.add('pejabat_penanda_tangan');
    }
    
    // ========================================
    // 2. NPK Specific Fields
    // ========================================
    const sidValue = groundTruth.SID || (groundTruth.NPK && groundTruth.NPK.SID);
    if (sidValue !== undefined && !renderedFields.has('SID')) {
        const npkWrapper = document.createElement('div');
        npkWrapper.className = 'template-npk';
        npkWrapper.appendChild(FormTemplateNPKReadOnly.createSIDField(sidValue));
        wrapper.appendChild(npkWrapper);
        renderedFields.add('SID');
        renderedFields.add('NPK');
    }
    
    const prorateValue = groundTruth.prorate || (groundTruth.NPK && groundTruth.NPK.prorate);
    if (prorateValue !== undefined && prorateValue.length > 0 && !renderedFields.has('prorate')) {
        const npkWrapper = document.createElement('div');
        npkWrapper.className = 'template-npk';
        npkWrapper.appendChild(FormTemplateNPKReadOnly.createProrateSection(prorateValue));
        wrapper.appendChild(npkWrapper);
        renderedFields.add('prorate');
    }
    
    const usageValue = groundTruth.nilai_satuan_usage || (groundTruth.NPK && groundTruth.NPK.nilai_satuan_usage);
    if (usageValue !== undefined && usageValue !== null && Object.keys(usageValue).length > 0 && !renderedFields.has('nilai_satuan_usage')) {
        const npkWrapper = document.createElement('div');
        npkWrapper.className = 'template-npk';
        npkWrapper.appendChild(FormTemplateNPKReadOnly.createUsageSection(usageValue));
        wrapper.appendChild(npkWrapper);
        renderedFields.add('nilai_satuan_usage');
    }
    
    // ========================================
    // 3. BAST Specific Fields
    // ========================================
    const tanggalBast = groundTruth.tanggal_bast || (groundTruth.BAST && groundTruth.BAST.tanggal_bast);
    if (tanggalBast !== undefined && !renderedFields.has('tanggal_bast')) {
        wrapper.appendChild(createSimpleDateField('Tanggal BAST', tanggalBast));
        renderedFields.add('tanggal_bast');
    }
    
    if (groundTruth.BAST && groundTruth.BAST.nomor && !renderedFields.has('BAST_nomor')) {
        wrapper.appendChild(createNomorBASTTable(groundTruth.BAST.nomor));
        renderedFields.add('BAST');
    }
    
    // ========================================
    // 4. BAUT Specific Fields
    // ========================================
    const tanggalBaut = getBautTanggalValue(groundTruth);
    if (tanggalBaut !== undefined && tanggalBaut !== null && tanggalBaut !== '' && !renderedFields.has('tanggal_baut')) {
        wrapper.appendChild(createSimpleDateField('Tanggal BAUT', tanggalBaut));
        renderedFields.add('tanggal_baut');
    }
    
    // ========================================
    // 5. BARD Specific Fields
    // ========================================
    const tanggalBard = groundTruth.tanggal_bard || (groundTruth.BARD && groundTruth.BARD.tanggal_bard);
    if (tanggalBard !== undefined && !renderedFields.has('tanggal_bard')) {
        wrapper.appendChild(createSimpleDateField('Tanggal BARD', tanggalBard));
        renderedFields.add('tanggal_bard');
    }
    
    // ========================================
    // 6. P7 Specific Fields (nomor & tanggal)
    // ========================================
    if (groundTruth.nomor !== undefined && !renderedFields.has('nomor')) {
        let nomorValue = groundTruth.nomor;
        
        // Handle nested objects - extract scalar value recursively
        if (typeof nomorValue === 'object' && !Array.isArray(nomorValue)) {
            nomorValue = extractScalarValue(nomorValue);
        }
        
        if (nomorValue && (typeof nomorValue === 'string' || typeof nomorValue === 'number')) {
            wrapper.appendChild(createSimpleField('Nomor Surat Penetapan Calon Mitra', nomorValue));
            renderedFields.add('nomor');
        }
    }
    
    if (groundTruth.tanggal !== undefined && !renderedFields.has('tanggal')) {
        const tanggalValue = getSingleTanggalValue(groundTruth.tanggal);
        if (tanggalValue) {
            wrapper.appendChild(createSimpleDateField('Tanggal Surat Penetapan Calon Mitra', tanggalValue));
            renderedFields.add('tanggal');
        }
    }
    
    // ========================================
    // 7. Render any remaining fields using same logic as validate-ground-truth.js
    // (nested sections, order-date tables, _raw display, dates, arrays - all fields shown)
    // ========================================
    const allKeys = Object.keys(groundTruth);
    const remainingKeys = allKeys.filter(key => !renderedFields.has(key) && !key.startsWith('_'));

    if (remainingKeys.length > 0) {
        const remainingData = {};
        remainingKeys.forEach(key => {
            remainingData[key] = groundTruth[key];
        });
        renderGroundTruthFieldsReadOnly(remainingData, wrapper);
    }
}

/**
 * Helper: Recursively extract scalar value from nested object
 * Digs deep into objects to find the first string/number value
 */
function extractScalarValue(value, maxDepth = 10) {
    if (maxDepth <= 0) return null;
    
    // Return if already scalar
    if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
        return value;
    }
    
    // Skip null/undefined
    if (value === null || value === undefined) {
        return null;
    }
    
    // If it's an array, check first element
    if (Array.isArray(value)) {
        if (value.length === 0) return null;
        return extractScalarValue(value[0], maxDepth - 1);
    }
    
    // If it's an object, recursively search (skip empty key "" to avoid e.g. BAUT[""] = "01-04-2025")
    if (typeof value === 'object') {
        const keys = Object.keys(value).filter(k => k !== '');
        if (keys.length === 0) return null;
        
        // Try each value recursively
        for (const key of keys) {
            const result = extractScalarValue(value[key], maxDepth - 1);
            if (result !== null && result !== undefined) {
                return result;
            }
        }
    }
    
    return null;
}

/**
 * Helper: Format field key to readable label
 */
function formatFieldLabel(key) {
    return key
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase());
}

/**
 * Helper: Create simple text field (generic)
 */
function createSimpleField(label, value) {
    const section = document.createElement('div');
    section.className = 'kontrak-section';
    const cleanValue = cleanText(value);
    section.innerHTML = `
        <div class="kontrak-field">
            <label class="kontrak-label">${label}</label>
            <div class="kontrak-input" style="background: #f9fafb; cursor: default; word-break: break-word; overflow-wrap: break-word; white-space: pre-line;">
                ${cleanValue}
            </div>
        </div>
    `;
    return section;
}

/**
 * Extract single date from a "tanggal" object that may have shape
 * { "": "01-08-2025", "tanggal": { "baut": "01-07-2025" } }.
 * Prefers tanggal.baut; fallback to first scalar.
 */
function getSingleTanggalValue(tanggalObj) {
    if (tanggalObj === undefined || tanggalObj === null) return undefined;
    if (typeof tanggalObj === 'string' || typeof tanggalObj === 'number') {
        const s = String(tanggalObj).trim();
        const firstLine = s.split(/\r?\n/)[0]?.trim();
        return firstLine || undefined;
    }
    if (typeof tanggalObj !== 'object' || Array.isArray(tanggalObj)) return undefined;
    const nested = tanggalObj.tanggal?.baut;
    if (nested !== undefined && nested !== null && typeof nested === 'string') return nested;
    const scalar = extractScalarValue(tanggalObj);
    return scalar !== null && scalar !== undefined ? String(scalar) : undefined;
}

/**
 * Extract Tanggal BAUT value from ground truth.
 * Prefers BAUT.tanggal.baut (e.g. {"tanggal": {"baut": "01-07-2025"}}); fallback to scalar tanggal_baut.
 */
function getBautTanggalValue(groundTruth) {
    const baut = groundTruth?.BAUT;
    if (!baut || typeof baut !== 'object') {
        return groundTruth?.tanggal_baut;
    }
    const tanggalBaut = baut.tanggal?.baut;
    if (tanggalBaut !== undefined && tanggalBaut !== null && typeof tanggalBaut === 'string') {
        return tanggalBaut;
    }
    return baut.tanggal_baut !== undefined ? baut.tanggal_baut : groundTruth?.tanggal_baut;
}

/**
 * Helper: Check if any tanggal field exists
 */
function hasAnyTanggalField(data) {
    return data.delivery !== undefined ||
        data.delivery_date !== undefined ||
        data.tanggal_kontrak !== undefined ||
        (data.jangka_waktu && (
            data.jangka_waktu.start_date !== undefined ||
            data.jangka_waktu.end_date !== undefined ||
            data.jangka_waktu.duration !== undefined
        ));
}

/**
 * Helper: Check if any pembayaran field exists
 */
function hasAnyPembayaranField(data) {
    return data.dpp_raw !== undefined ||
        data.harga_satuan_raw !== undefined ||
        data.metode_pembayaran !== undefined ||
        data.terms_of_payment !== undefined;
}

/**
 * Helper: Create simple date field (for BAST/BAUT/BARD).
 * Shows only the first line so multi-line values never display as two dates.
 */
function createSimpleDateField(label, value) {
    const section = document.createElement('div');
    section.className = 'kontrak-section';
    const str = value !== undefined && value !== null ? String(value).trim() : '';
    const firstLineOnly = str.split(/\r?\n/)[0]?.trim() || '-';
    const cleanValue = firstLineOnly === '-' ? '-' : cleanText(firstLineOnly);
    section.innerHTML = `
        <div class="kontrak-field">
            <label class="kontrak-label">${escapeHtml(label)}</label>
            <div class="kontrak-input" style="background: #f9fafb; cursor: default; word-break: break-word; overflow-wrap: break-word;">
                ${escapeHtml(cleanValue)}
            </div>
        </div>
    `;
    return section;
}

/**
 * Helper: Create Nomor BAST table
 */
function createNomorBASTTable(nomorObj) {
    const section = document.createElement('div');
    section.className = 'kontrak-section';
    
    const cleanTelkom = cleanText(nomorObj?.telkom);
    const cleanMitra = cleanText(nomorObj?.mitra);
    
    section.innerHTML = `
        <div class="kontrak-field">
            <label class="kontrak-label">Nomor BAST</label>
            <div class="kontrak-table-wrapper">
                <table class="kontrak-table kontrak-table-static">
                    <tbody>
                        <tr>
                            <td class="kontrak-table-label">Telkom</td>
                            <td>
                                <div class="kontrak-table-input" style="background: #f9fafb; cursor: default; word-break: break-word; overflow-wrap: break-word;">
                                    ${cleanTelkom}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="kontrak-table-label">Mitra</td>
                            <td>
                                <div class="kontrak-table-input" style="background: #f9fafb; cursor: default; word-break: break-word; overflow-wrap: break-word;">
                                    ${cleanMitra}
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    `;
    return section;
}

/**
 * Render Stage Cards
 */
function renderStageCards(stages) {
    const container = document.getElementById('stage-cards-container');
    container.innerHTML = '';

    if (!Array.isArray(stages) || stages.length === 0) {
        console.log('ℹ️ No stages to render, hiding container');
        container.style.display = 'none';
        return;
    }

    console.log(`✅ Rendering ${stages.length} stage cards`);
    container.style.display = 'flex';
    stages.forEach((stage, index) => {
        const stageCard = createStageCard(stage);
        container.appendChild(stageCard);
    });
}

function createStageCard(stage) {
    const cardDiv = document.createElement('div');
    cardDiv.className = 'stage-card';

    const header = document.createElement('div');
    header.className = 'stage-card-header';
    header.innerHTML = `
        <div class="stage-card-title">
            <i class="bi bi-clipboard-check"></i>
            <span>${stage.stage_name || 'Validasi Tahap ' + stage.stage_id}</span>
        </div>
    `;
    cardDiv.appendChild(header);

    const subcardsContainer = document.createElement('div');
    subcardsContainer.className = 'stage-subcards-container';

    // Get stage name untuk pass ke subcard
    const stageName = stage.stage_name || 'Validasi Tahap ' + stage.stage_id;

    // Only render issues that have a non-empty description (avoid showing label as description when description is "")
    const hasMeaningfulDescription = (issue) => {
        const d = (issue.description || issue.Description || issue.notes || '').trim();
        return d !== '';
    };
    const issuesToShow = (stage.issues || []).filter(hasMeaningfulDescription);

    if (issuesToShow.length > 0) {
        issuesToShow.forEach(issue => {
            const subcard = createStageSubcard(issue, stageName); // ← Pass stageName
            subcardsContainer.appendChild(subcard);
        });
    } else {
        subcardsContainer.innerHTML = `
            <div class="p-2 text-muted text-center small">
                Tidak ada isu ditemukan
            </div>
        `;
    }

    cardDiv.appendChild(subcardsContainer);
    return cardDiv;
}

/**
 * ✅ CREATE STAGE SUBCARD WITH [+] BUTTON AND RECOMMENDATIONS
 */
function createStageSubcard(issue, stageName) {
    const subcardDiv = document.createElement('div');
    subcardDiv.className = 'stage-subcard';

    if (issue.status) {
        subcardDiv.classList.add(`status-${issue.status}`);
    }

    // Main content container
    const contentDiv = document.createElement('div');
    contentDiv.className = 'stage-subcard-content';

    // Text and recommendation wrapper (to stack them vertically)
    const textWrapper = document.createElement('div');
    textWrapper.className = 'stage-subcard-text-wrapper';

    // Text section
    const textDiv = document.createElement('div');
    textDiv.className = 'stage-subcard-text';
    // Handle JSON structure: Use 'description' field, fallback to legacy structure: 'Description', 'notes', 'label'
    const descriptionText = issue.description || issue.Description || issue.notes || issue.label || '-';
    
    // DEBUG: Log description output value (even if is_valid: false)
    const isValid = issue.is_valid !== undefined ? issue.is_valid : 'N/A';
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log('📝 ISSUE DESCRIPTION OUTPUT:');
    console.log('   Stage:', stageName);
    console.log('   Label:', issue.label || 'N/A');
    console.log('   Description:', descriptionText);
    console.log('   is_valid:', isValid);
    console.log('   Status:', issue.status || 'N/A');
    console.log('   Full Issue Object:', issue);
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    
    textDiv.innerHTML = `<span class="issue-description">${descriptionText}</span>`;

    textWrapper.appendChild(textDiv);

    

    contentDiv.appendChild(textWrapper);

    // Add [+] button only if issue can be added to notes
    // Show button for review_data and error_message stages (can_add_to_notes === true)
    // Hide button for "No review notes" stages (can_add_to_notes === false)
    if (issue.can_add_to_notes === true) {
        const addBtn = document.createElement('button');
        addBtn.className = 'add-note-btn';
        addBtn.title = 'Tambah ke Notes';
        addBtn.innerHTML = '<i class="bi bi-plus"></i>';

        // ✅ Use addEventListener instead of inline onclick for better security and reliability
        addBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Check if the function exists before calling
            if (typeof window.addAdvanceIssueToNotes === 'function') {
                window.addAdvanceIssueToNotes(issue, stageName);
            } else {
                console.error('❌ addAdvanceIssueToNotes function not found. Make sure notes.js is loaded.');
            }
        });

        contentDiv.appendChild(addBtn);
    }
    subcardDiv.appendChild(contentDiv);

    return subcardDiv;
}

/**
 * Extract or generate recommendation from issue data
 * Checks for explicit recommendation field first, then generates from description text
 */
function extractRecommendation(issue, descriptionText, stageName) {
    // 1. Check if there's an explicit recommendation field in the issue
    if (issue.recommendation && issue.recommendation.trim()) {
        return escapeHtml(issue.recommendation.trim());
    }

    // 2. Check if there's a recommendation in the review_data structure
    if (issue.review_data && typeof issue.review_data === 'object') {
        const reviewData = issue.review_data;
        // Check if recommendation exists in nested structure
        if (reviewData.recommendation && reviewData.recommendation.trim()) {
            return escapeHtml(reviewData.recommendation.trim());
        }
    }

    // 3. Generate recommendation from description text by parsing it
    return generateRecommendationFromDescription(descriptionText, stageName);
}

/**
 * Generate recommendation text by parsing description to extract:
 * - Where the mistake is (location/field name)
 * - Description of the mistake
 */
function generateRecommendationFromDescription(descriptionText, stageName) {
    if (!descriptionText || descriptionText === '-' || descriptionText.trim() === '') {
        return null;
    }

    const text = descriptionText.trim();
    const textLower = text.toLowerCase();

    // Extract location/field name patterns
    let location = '';
    let mistakeDescription = '';

    // Pattern 1: Extract field names from common document fields (check first for accuracy)
    const commonFields = [
        { pattern: /nomor\s+surat/gi, name: 'Nomor Surat' },
        { pattern: /tanggal/gi, name: 'Tanggal' },
        { pattern: /dpp/gi, name: 'DPP' },
        { pattern: /nama\s+pelanggan/gi, name: 'Nama Pelanggan' },
        { pattern: /judul\s+project/gi, name: 'Judul Project' },
        { pattern: /delivery/gi, name: 'Delivery' },
        { pattern: /metode\s+pembayaran/gi, name: 'Metode Pembayaran' },
        { pattern: /terms\s+of\s+payment/gi, name: 'Terms of Payment' },
        { pattern: /skema\s+bisnis/gi, name: 'Skema Bisnis' },
        { pattern: /slg/gi, name: 'SLG' },
        { pattern: /detail\s+rekening/gi, name: 'Detail Rekening' },
        { pattern: /rujukan/gi, name: 'Rujukan' },
        { pattern: /pejabat\s+penanda\s+tangan/gi, name: 'Pejabat Penanda Tangan' },
        { pattern: /sid/gi, name: 'SID' },
        { pattern: /prorate/gi, name: 'Prorate' },
        { pattern: /nilai\s+satuan\s+usage/gi, name: 'Nilai Satuan Usage' },
        { pattern: /nomor\s+telkom/gi, name: 'Nomor Telkom' },
        { pattern: /nomor\s+mitra/gi, name: 'Nomor Mitra' },
        { pattern: /tanggal\s+bast/gi, name: 'Tanggal BAST' },
        { pattern: /tanggal\s+baut/gi, name: 'Tanggal BAUT' },
        { pattern: /tanggal\s+bard/gi, name: 'Tanggal BARD' }
    ];

    for (const field of commonFields) {
        if (field.pattern.test(textLower)) {
            location = field.name;
            break;
        }
    }

    // Pattern 2: Extract field names mentioned in the text with context
    if (!location) {
        const locationPatterns = [
            /(?:field|kolom|bagian|pada|di)\s+([^,\.]+)/gi,
            /([A-Z][a-z]+(?:\s+[A-Z][a-z]+)*)\s+(?:tidak|salah|hilang|missing|error)/gi,
            /(?:di|pada)\s+([^,\.]+?)\s+(?:ditemukan|terdapat|ada)/gi
        ];

        for (const pattern of locationPatterns) {
            const match = text.match(pattern);
            if (match && match.length > 0) {
                location = match[0].replace(/(?:field|kolom|bagian|pada|di)\s+/gi, '').trim();
                // Clean up location text
                location = location.replace(/^(yang|tersebut|ini|itu)\s+/gi, '').trim();
                break;
            }
        }
    }

    // If no specific location found, use stage name as context
    if (!location) {
        location = stageName || 'Dokumen';
    }

    // Extract mistake description - look for the reason/explanation
    const mistakePatterns = [
        // Pattern: "karena/sebab/akibat [reason]"
        /(?:karena|sebab|akibat|dikarenakan)\s+([^\.]+?)(?:\.|$)/gi,
        // Pattern: "tidak/salah/hilang [what]"
        /(?:tidak|salah|hilang|missing|error|tidak valid|tidak sesuai)\s+([^\.]+?)(?:\.|$)/gi,
        // Pattern: "perlu/harus/seharusnya [what should be]"
        /(?:perlu|harus|seharusnya)\s+([^\.]+?)(?:\.|$)/gi,
        // Pattern: Extract sentence after common error indicators
        /(?:ditemukan|terdapat|ada)\s+([^\.]+?)(?:\.|$)/gi
    ];

    for (const pattern of mistakePatterns) {
        const matches = text.match(pattern);
        if (matches && matches.length > 0) {
            // Take the first meaningful match
            mistakeDescription = matches[0].trim();
            // Clean up common prefixes
            mistakeDescription = mistakeDescription.replace(/^(karena|sebab|akibat|dikarenakan|tidak|salah|hilang|missing|error|perlu|harus|seharusnya|ditemukan|terdapat|ada)\s+/gi, '').trim();
            break;
        }
    }

    // If no specific mistake description found, use the full description text
    if (!mistakeDescription || mistakeDescription.length < 10) {
        mistakeDescription = text;
    }

    // Build recommendation text
    let recommendation = '';

    // Add location information
    if (location) {
        recommendation += `<strong>Lokasi Kesalahan:</strong> ${escapeHtml(location)}<br><br>`;
    }

    // Add mistake description
    recommendation += `<strong>Deskripsi Kesalahan:</strong> ${escapeHtml(mistakeDescription)}`;

    return recommendation;
}

/**
 * Helper: Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showLoading() {
    document.getElementById('loading-indicator').style.display = 'block';
    document.getElementById('pdf-container').style.display = 'none';
}

function showError(message) {
    const loadingIndicator = document.getElementById('loading-indicator');
    loadingIndicator.innerHTML = `
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-circle"></i> 
            <strong>Error:</strong> ${message}
        </div>
    `;
}

/**
 * Show full page loading overlay
 */
function showPageLoadingOverlay() {
    const overlay = document.getElementById('pageLoadingOverlay');
    if (overlay) {
        overlay.classList.add('show');
    }
}

/**
 * Hide full page loading overlay
 */
function hidePageLoadingOverlay() {
    const overlay = document.getElementById('pageLoadingOverlay');
    if (overlay) {
        // Remove show class to trigger fade out
        overlay.classList.remove('show');
        // Hide after fade out animation completes
        setTimeout(() => {
            if (!overlay.classList.contains('show')) {
                overlay.style.display = 'none';
            }
        }, 300);
    }
}

// Export functions to global scope
window.initAdvanceResultViewer = initAdvanceResultViewer;
window.rotateAdvanceResultPage = rotateAdvanceResultPage;

console.log('✅ pdf-advance-result.js loaded successfully with ROTATE feature');