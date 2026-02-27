// validate-ground-truth.js - FULL CODE WITH ROTATE & DEBUG LOGS

// ============================================
// CONSTANTS & SETUP
// ============================================
const PDFJS_VERSION = '3.11.174';

// Setup PDF.js worker
if (typeof pdfjsLib === 'undefined') {
} else {
    pdfjsLib.GlobalWorkerOptions.workerSrc =
        `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/${PDFJS_VERSION}/pdf.worker.min.js`;
}

// Global variables
let currentDocType = null;
let currentPdfDoc = null;
let currentFormData = {};
let pageRotations = {}; // State untuk menyimpan rotasi per halaman

// ============================================
// DOCUMENT TYPE MAPPING (Backend ↔ Frontend)
// ============================================
const DOC_TYPE_MAPPING = {
    'Kontrak Layanan': 'KL',
    'Work Order': 'WO',
    'Surat Pesanan': 'SP',
    'Nota Pesanan': 'NOPES'
};

const REVERSE_DOC_TYPE_MAPPING = {
    'KL': 'Kontrak Layanan',
    'WO': 'Work Order',
    'SP': 'Surat Pesanan',
    'NOPES': 'Nota Pesanan'
};

// Form templates (Loaded from other scripts)
const FORM_TEMPLATES = {
    'NPK': typeof FormTemplateNPK !== 'undefined' ? FormTemplateNPK : null,
    'KL': typeof FormTemplateKontrak !== 'undefined' ? FormTemplateKontrak : null,
    'WO': typeof FormTemplateKontrak !== 'undefined' ? FormTemplateKontrak : null,
    'SP': typeof FormTemplateKontrak !== 'undefined' ? FormTemplateKontrak : null,
    'NOPES': typeof FormTemplateKontrak !== 'undefined' ? FormTemplateKontrak : null,
    'BAUT': typeof FormTemplateBAUT !== 'undefined' ? FormTemplateBAUT : null
};

// ============================================
// DEBUG: INSPECT BACKEND DATA
// ============================================
function inspectBackendData() {
    console.group('%c 🔍 DEBUG: DATA DARI BACKEND ', 'background: #222; color: #bada55; font-size: 12px; padding: 4px;');

    console.log('%c 1. Available Documents (List Dokumen):', 'color: #00bcd4; font-weight: bold;');
    if (typeof AVAILABLE_DOCUMENTS !== 'undefined') {
        console.table(AVAILABLE_DOCUMENTS); // Tampilkan sebagai tabel agar mudah dibaca
    } else {
        console.error('❌ AVAILABLE_DOCUMENTS is undefined');
    }

    console.log('%c 2. Ground Truth Data (Isi Data OCR/DB):', 'color: #00bcd4; font-weight: bold;');
    if (typeof GROUND_TRUTH_DATA !== 'undefined') {
        console.dir(GROUND_TRUTH_DATA); // Gunakan dir untuk bisa expand object tree
        
        // Additional debug: Show exact keys and field counts
        console.log('%c 2a. GROUND_TRUTH_DATA Keys:', 'color: #ff9800; font-weight: bold;');
        const gtKeys = Object.keys(GROUND_TRUTH_DATA);
        console.log('Keys found:', gtKeys);
        
        gtKeys.forEach(key => {
            const data = GROUND_TRUTH_DATA[key];
            const fieldKeys = data ? Object.keys(data) : [];
            console.log(`  [${key}] has ${fieldKeys.length} fields:`, fieldKeys);
        });
    } else {
        console.error('❌ GROUND_TRUTH_DATA is undefined');
    }

    console.log('%c 3. Ticket Info:', 'color: #00bcd4; font-weight: bold;');
    console.log('Ticket Number:', typeof TICKET_NUMBER !== 'undefined' ? TICKET_NUMBER : 'UNDEFINED');

    console.groupEnd();
}

// ============================================
// PROGRESS TRACKER
// ============================================
const ProgressTracker = {
    savedDocuments: new Set(),
    totalDocuments: 0,
    _notificationShown: false,

    init() {
        this.totalDocuments = typeof AVAILABLE_DOCUMENTS !== 'undefined' ? AVAILABLE_DOCUMENTS.length : 0;
        this.loadFromMemory();
        this.updateUI();
    },

    loadFromMemory() {
        if (window.ticketProgressState) {
            this.savedDocuments = new Set(window.ticketProgressState);
        }
    },

    saveToMemory() {
        window.ticketProgressState = Array.from(this.savedDocuments);
    },

    markAsSaved(docType) {
        this.savedDocuments.add(docType);
        this.saveToMemory();
        this.updateUI();
        this.updateStepBadge(docType);
    },

    getProgress() {
        return {
            saved: this.savedDocuments.size,
            total: this.totalDocuments,
            percentage: this.totalDocuments > 0 ? Math.round((this.savedDocuments.size / this.totalDocuments) * 100) : 0
        };
    },

    isComplete() {
        return this.savedDocuments.size === this.totalDocuments && this.totalDocuments > 0;
    },

    updateUI() {
        const progress = this.getProgress();
        const fillEl = document.getElementById('progress-bar-fill');
        const percentageEl = document.getElementById('progress-percentage');
        const textEl = document.getElementById('progress-text');
        const reviewBtn = document.getElementById('review-ticket-btn');

        if (!fillEl || !percentageEl || !textEl || !reviewBtn) return;

        fillEl.style.width = `${progress.percentage}%`;
        percentageEl.textContent = `${progress.percentage}%`;
        textEl.textContent = `${progress.saved} dari ${progress.total} dokumen telah direview`;

        if (progress.percentage === 100) {
            fillEl.classList.add('complete');
            reviewBtn.disabled = false;

            if (!this._notificationShown) {
                this.showCompletionNotification();
                this._notificationShown = true;
            }
        } else {
            fillEl.classList.remove('complete');
            reviewBtn.disabled = true;
            this._notificationShown = false;
        }
    },

    updateStepBadge(docType) {
        const stepEl = document.querySelector(`.step-item[data-doc-type="${docType}"]`);
        if (stepEl) {
            stepEl.classList.add('saved');
        }
    },

    showCompletionNotification() {
        if (document.querySelector('.completion-notification')) return;

        const notification = document.createElement('div');
        notification.className = 'completion-notification';
        notification.innerHTML = `
            <i class="bi bi-check-circle"></i>
            <span>Semua dokumen telah direview!</span>
        `;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
};

// ============================================
// INITIALIZE ON DOM READY
// ============================================
document.addEventListener('DOMContentLoaded', function () {
    console.log('🚀 Initializing Ground Truth Validation...');

    // Panggil fungsi debug untuk melihat data dari BE
    inspectBackendData();

    ProgressTracker.init();

    if (typeof AVAILABLE_DOCUMENTS !== 'undefined' && AVAILABLE_DOCUMENTS.length > 0) {
        switchDocument(AVAILABLE_DOCUMENTS[0].type);
    } else {
        console.warn('⚠️ No documents available for this ticket');
    }

    setupStepHandlers();
    setupSaveHandler();
    setupReviewButtonHandler();
});

// ============================================
// STEP HANDLERS
// ============================================
function setupStepHandlers() {
    document.querySelectorAll('.step-item').forEach(step => {
        step.addEventListener('click', function () {
            const docType = this.getAttribute('data-doc-type');
            switchDocument(docType);
        });
    });
}

// ============================================
// REVIEW BUTTON HANDLER
// ============================================
function setupReviewButtonHandler() {
    const reviewBtn = document.getElementById('review-ticket-btn');
    if (reviewBtn) {
        reviewBtn.addEventListener('click', function () {
            if (ProgressTracker.isComplete()) {
                handleReviewTicket();
            }
        });
    }
}

function handleReviewTicket() {
    if (!confirm('Apakah Anda yakin semua data sudah benar dan siap untuk direview?')) {
        return;
    }

    showLoading();
    const groundTruthJson = JSON.stringify(GROUND_TRUTH_DATA);

    console.log('🚀 Submitting review Payload:', {
        ticket: TICKET_NUMBER,
        doc_count: Object.keys(GROUND_TRUTH_DATA).length
    });

    const submitUrl = (typeof REVIEW_SUBMIT_URL !== 'undefined' && REVIEW_SUBMIT_URL) ? REVIEW_SUBMIT_URL : '/projess/api/review/submit';
    fetch(submitUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            ticket: TICKET_NUMBER,
            ground_truth: groundTruthJson
        })
    })
        .then(response => {
            console.log('Response status:', response.status);
            const contentType = response.headers.get('content-type');
            
            // Handle authentication errors (401 = session expired)
            if (response.status === 401) {
                hideLoading();
                alert('Session Anda telah expired. Halaman akan di-refresh.');
                setTimeout(() => window.location.reload(), 1000);
                throw new Error('Session expired');
            }
            
            // Handle CSRF token expired
            if (response.status === 419) {
                hideLoading();
                alert('Session Anda telah expired. Halaman akan di-refresh.');
                setTimeout(() => window.location.reload(), 1000);
                throw new Error('CSRF token expired');
            }
            
            // Check if response is JSON before parsing
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then((text) => {
                    console.error('Non-JSON response:', text.substring(0, 200));
                    // Check if it's an HTML redirect (login page)
                    if (text.includes('<!DOCTYPE') || text.includes('<html')) {
                        hideLoading();
                        alert('Session Anda telah expired. Halaman akan di-refresh.');
                        setTimeout(() => window.location.reload(), 1000);
                        throw new Error('Session expired - received HTML redirect');
                    }
                    throw new Error('Server returned non-JSON response');
                });
            }
            
            if (!response.ok) {
                return response.json().then((data) => {
                    throw new Error(data.message || `HTTP error: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            console.group('✅ Review Submission Response (From BE)');
            console.log('Response Data:', data);
            console.groupEnd();

            if (data.success && data.data.status === 'processing') {
                pollReviewStatus();
            } else {
                throw new Error(data.message || 'Gagal mengirim review');
            }
        })
        .catch(error => {
            console.error('❌ Error submitting review:', error);
            hideLoading();
            showErrorModal(`Terjadi kesalahan: ${error.message}`);
        });
}

// ============================================
// POLLING REVIEW STATUS
// ============================================
function pollReviewStatus() {
    let pollCount = 0;
    const maxPolls = 200;

    console.log('🔄 Starting status polling...');

    const pollInterval = setInterval(() => {
        pollCount++;

        const statusUrl = (typeof REVIEW_STATUS_URL !== 'undefined' && REVIEW_STATUS_URL) ? `${REVIEW_STATUS_URL}/${TICKET_NUMBER}` : `/projess/api/review/status/${TICKET_NUMBER}`;
        fetch(statusUrl, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF_TOKEN
            },
            credentials: 'same-origin'
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error: ${response.status}`);
                }
                // Check if response is actually JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error(`Expected JSON but got ${contentType || 'unknown content type'}`);
                }
                return response.json();
            })
            .then(data => {
                // Log status setiap kali polling (Optional: bisa dikurangi frekuensinya jika terlalu spam)
                console.log(`📊 Poll ${pollCount}/${maxPolls} - BE Status:`, data.status, data);

                if (data.status === 'completed') {
                    clearInterval(pollInterval);
                    console.log('✅ Review completed successfully!');
                    showSuccessOverlay(data.redirect_url || `/tickets/${TICKET_NUMBER}/advance-reviews`);
                }
                else if (data.status === 'failed') {
                    clearInterval(pollInterval);
                    console.error('❌ Review failed:', data.error);
                    showErrorModal(data.error || 'Review gagal diproses');
                }
                else if (data.status === 'not_found') {
                    clearInterval(pollInterval);
                    showErrorModal('Status review tidak ditemukan');
                }

                if (pollCount >= maxPolls) {
                    clearInterval(pollInterval);
                    showErrorModal('Proses review memakan waktu terlalu lama. Silakan cek status di halaman history.');
                }
            })
            .catch(error => {
                console.error('❌ Polling error:', error);
                console.warn('⚠️ API endpoint error. Check if `/api/review/status/' + TICKET_NUMBER + '` is accessible.');
                
                // Stop polling after multiple consecutive errors
                if (pollCount >= maxPolls) {
                    clearInterval(pollInterval);
                    showErrorModal('Gagal menghubungi server. Silakan coba lagi.');
                }
            });
    }, 3000);
}

// ============================================
// OVERLAY HELPERS
// ============================================
function showLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (!loadingOverlay) return;

    loadingOverlay.classList.add('show');
    document.body.style.overflow = 'hidden';

    // ... (kode loading UI existing)
}

function hideLoading() {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (!loadingOverlay) return;
    loadingOverlay.classList.remove('show');
    document.body.style.overflow = '';
}

function showSuccessOverlay(redirectUrl) {
    const loadingOverlay = document.getElementById('loadingOverlay');
    if (!loadingOverlay) {
        window.location.href = redirectUrl;
        return;
    }
    // Update UI Loading to Success
    const loadingText = loadingOverlay.querySelector('.loading-text');
    const spinner = loadingOverlay.querySelector('.loading-spinner');
    if (spinner) spinner.style.display = 'none';
    if (loadingText) loadingText.innerHTML = '<i class="bi bi-check-circle-fill text-success me-2"></i>Review Berhasil!';

    setTimeout(() => {
        window.location.href = redirectUrl;
    }, 1500);
}

function showErrorModal(message) {
    hideLoading();
    setTimeout(() => {
        // Implementasi modal error existing
        alert('Error: ' + message); // Fallback
    }, 100);
}

// ============================================
// DOCUMENT SWITCHING
// ============================================
function switchDocument(docType) {
    if (docType === currentDocType) return;

    console.log(`🔄 Switching to document: ${docType}`);
    currentDocType = docType;

    // [ROTATE FEATURE] Reset rotasi saat ganti dokumen
    pageRotations = {};

    document.querySelectorAll('.step-item').forEach(step => {
        if (step.getAttribute('data-doc-type') === docType) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });

    const doc = AVAILABLE_DOCUMENTS.find(d => d.type === docType);
    if (doc) {
        document.getElementById('current-doc-name').textContent = doc.label;
        // Debug: Log info dokumen yang sedang aktif
        console.log('📂 Active Document Info:', doc);
    }

    loadPDF(docType);
    loadFormData(docType);
}

// ============================================
// PDF LOADING & RENDERING
// ============================================
async function loadPDF(docType) {
    const loadingEl = document.getElementById('pdf-loading');
    const containerEl = document.getElementById('pdf-container');
    const errorEl = document.getElementById('pdf-error');

    loadingEl.style.display = 'block';
    containerEl.style.display = 'none';
    errorEl.style.display = 'none';
    containerEl.innerHTML = '';

    try {
        const doc = AVAILABLE_DOCUMENTS.find(d => d.type === docType);
        if (!doc || !doc.pdf_url) throw new Error('PDF URL not found');

        console.log('📄 Fetching PDF from:', doc.pdf_url);

        currentPdfDoc = await pdfjsLib.getDocument({
            url: doc.pdf_url,
            withCredentials: true
        }).promise;

        console.log(`✅ PDF Loaded. Total Pages: ${currentPdfDoc.numPages}`);

        for (let pageNum = 1; pageNum <= currentPdfDoc.numPages; pageNum++) {
            try {
                await renderPageProgressive(pageNum, containerEl);
                console.log(`✅ Page ${pageNum}/${currentPdfDoc.numPages} rendered`);
            } catch (error) {
                console.error(`❌ Failed to render page ${pageNum}:`, error);
            }
        }

        loadingEl.style.display = 'none';
        containerEl.style.display = 'block';

    } catch (error) {
        console.error('❌ Error loading PDF:', error);
        loadingEl.style.display = 'none';
        errorEl.style.display = 'block';
        document.getElementById('pdf-error-message').textContent = 'Gagal memuat PDF: ' + error.message;
    }
}

async function renderPageProgressive(pageNum, containerEl) {
    const page = await currentPdfDoc.getPage(pageNum);
    const pageElement = await renderPDFPage(page, pageNum);
    containerEl.appendChild(pageElement);
    return pageElement;
}

// [ROTATE FEATURE] Logic Render Halaman
async function renderPDFPage(page, pageNum) {
    // 1. Ambil rotasi dari state (Default 0)
    const rotation = pageRotations[pageNum] || 0;
    const scale = 1.3;

    // 2. Terapkan rotasi ke viewport
    const viewport = page.getViewport({ scale, rotation });

    const pageContainer = document.createElement('div');
    pageContainer.className = 'pdf-page-container mb-3';
    pageContainer.id = `pdf-page-${pageNum}`;

    const pageHeader = document.createElement('div');
    pageHeader.className = 'pdf-page-header';

    // Tombol Rotate
    pageHeader.innerHTML = `
        <small class="text-muted">Halaman ${pageNum} dari ${currentPdfDoc.numPages} (Rotasi: ${rotation}°)</small>
        <button class="rotate-btn" onclick="rotatePage(${pageNum})" title="Putar Halaman" type="button" style="margin-left: 10px;">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    `;
    pageContainer.appendChild(pageHeader);

    const canvasWrapper = document.createElement('div');
    canvasWrapper.className = 'pdf-canvas-wrapper';
    canvasWrapper.id = `pdf-canvas-wrapper-${pageNum}`;

    const canvas = document.createElement('canvas');
    canvas.width = viewport.width;
    canvas.height = viewport.height;

    const context = canvas.getContext('2d', { alpha: false });

    await page.render({
        canvasContext: context,
        viewport: viewport,
        intent: 'display'
    }).promise;

    canvasWrapper.appendChild(canvas);
    pageContainer.appendChild(canvasWrapper);

    return pageContainer;
}

// [ROTATE FEATURE] Logic Putar Halaman
async function rotatePage(pageNum) {
    if (!currentPdfDoc) return;

    const btnIcon = document.querySelector(`#pdf-page-${pageNum} .rotate-btn i`);
    if (btnIcon) btnIcon.classList.add('fa-spin');

    try {
        // 1. Update State (tambah 90 derajat)
        const currentRotation = pageRotations[pageNum] || 0;
        const newRotation = (currentRotation + 90) % 360;
        pageRotations[pageNum] = newRotation;

        console.log(`🔄 Rotating Page ${pageNum}: ${currentRotation}° -> ${newRotation}°`);

        // 2. Render ulang
        const page = await currentPdfDoc.getPage(pageNum);
        const scale = 1.3;
        const viewport = page.getViewport({ scale, rotation: newRotation });

        const canvas = document.createElement('canvas');
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        const context = canvas.getContext('2d', { alpha: false });

        await page.render({
            canvasContext: context,
            viewport: viewport,
            intent: 'display'
        }).promise;

        // 3. Update DOM
        const wrapper = document.getElementById(`pdf-canvas-wrapper-${pageNum}`);
        if (wrapper) {
            wrapper.innerHTML = '';
            wrapper.appendChild(canvas);
        }

        // Update label header
        const headerText = document.querySelector(`#pdf-page-${pageNum} .pdf-page-header small`);
        if (headerText) headerText.textContent = `Halaman ${pageNum} dari ${currentPdfDoc.numPages} (Rotasi: ${newRotation}°)`;

    } catch (error) {
        console.error('Error rotating page:', error);
        alert('Gagal memutar halaman.');
    } finally {
        if (btnIcon) btnIcon.classList.remove('fa-spin');
    }
}

// ============================================
// FORM DATA LOADING
// ============================================
function loadFormData(docType) {
    const formContainer = document.getElementById('extraction-form');
    const formTitle = document.getElementById('form-title');
    const dataKey = getDataKey(docType);

    if (!GROUND_TRUTH_DATA[dataKey]) {
        console.warn(`⚠️ No data found in GROUND_TRUTH_DATA for key: ${dataKey}`);
        formContainer.innerHTML = `
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Tidak ada data dari Backend untuk dokumen ini. Silakan isi form manual.
            </div>
        `;
        formTitle.textContent = `Data ${docType}`;
        return;
    }

    currentFormData = GROUND_TRUTH_DATA[dataKey];

    const template = FORM_TEMPLATES[dataKey];
    if (template) {
        formTitle.textContent = template.meta.title;
        template.render(currentFormData, formContainer);
    } else {
        const doc = AVAILABLE_DOCUMENTS.find(d => d.type === docType);
        formTitle.textContent = `Data ${doc ? doc.label : 'Ekstraksi'}`;
        formContainer.innerHTML = '';
        renderFormFields(currentFormData, formContainer);
    }
}

function getDataKey(frontendDocType) {
    return DOC_TYPE_MAPPING[frontendDocType] || frontendDocType;
}

// ============================================
// FORM UTILS (Render, Date, etc)
// ============================================
function convertDateToInput(dateStr) {
    if (!dateStr || typeof dateStr !== 'string') return '';
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    const [day, month, year] = parts;
    return `${year}-${month}-${day}`;
}

function convertDateFromInput(dateStr) {
    if (!dateStr || typeof dateStr !== 'string') return '';
    const parts = dateStr.split('-');
    if (parts.length !== 3) return dateStr;
    const [year, month, day] = parts;
    return `${day}-${month}-${year}`;
}

function isDateField(key) {
    const dateKeywords = ['tanggal', 'date', 'tgl', 'delivery'];
    return dateKeywords.some(keyword => key.toLowerCase().includes(keyword));
}

/**
 * Detect if a field is a "no order -> tanggal aktivasi" map (e.g. lampiran_baut).
 * Structure: object with string keys (no order) and string values (dd-mm-yyyy dates).
 */
function isOrderDateMapField(key, value) {
    if (typeof value !== 'object' || value === null || Array.isArray(value)) return false;
    const knownKeys = ['lampiran_baut'];
    if (knownKeys.includes(key)) return true;
    const entries = Object.entries(value);
    if (entries.length === 0) return false;
    return entries.every(([, v]) => typeof v === 'string');
}

/**
 * Render a table view with CRUD for no order + tanggal aktivasi data.
 * @param {HTMLElement} container - Parent to append to
 * @param {string} fieldId - Field id (e.g. lampiran_baut) for the hidden input
 * @param {string} label - Section label
 * @param {Record<string, string>} data - Object mapping no_order -> date string (dd-mm-yyyy)
 */
function renderOrderDateTable(container, fieldId, label, data) {
    const wrapper = document.createElement('div');
    wrapper.className = 'form-section order-date-table-wrapper';
    const header = document.createElement('div');
    header.className = 'form-section-header';
    header.textContent = label;
    wrapper.appendChild(header);

    const tableContainer = document.createElement('div');
    tableContainer.className = 'order-date-table-container';

    const table = document.createElement('table');
    table.className = 'order-date-table';
    table.innerHTML = `
        <thead>
            <tr>
                <th>No Order</th>
                <th>Tanggal Aktivasi</th>
                <th class="th-actions"></th>
            </tr>
        </thead>
        <tbody></tbody>
    `;
    const tbody = table.querySelector('tbody');

    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.id = fieldId;
    hiddenInput.dataset.orderDateTable = '1';
    hiddenInput.value = JSON.stringify(data || {});

    function getTableData() {
        const obj = {};
        tbody.querySelectorAll('tr[data-row]').forEach(tr => {
            const noOrder = tr.querySelector('.input-no-order')?.value?.trim();
            const dateVal = tr.querySelector('.input-tanggal')?.value?.trim();
            if (noOrder) {
                const dateStr = dateVal ? convertDateFromInput(dateVal) : '';
                obj[noOrder] = dateStr || '';
            }
        });
        return obj;
    }

    function syncHiddenInput() {
        hiddenInput.value = JSON.stringify(getTableData());
    }

    function addRow(noOrder = '', dateStr = '') {
        const tr = document.createElement('tr');
        tr.setAttribute('data-row', '1');
        const dateInputVal = dateStr ? convertDateToInput(dateStr) : '';
        tr.innerHTML = `
            <td><input type="text" class="form-control form-input input-no-order" placeholder="No Order" value="${escapeHtml(noOrder)}"></td>
            <td><input type="date" class="form-control form-input input-tanggal" value="${escapeHtml(dateInputVal)}"></td>
            <td class="td-actions">
                <button type="button" class="btn btn-row-delete" title="Hapus"><i class="bi bi-trash"></i></button>
            </td>
        `;
        tr.querySelector('.input-no-order').addEventListener('input', syncHiddenInput);
        tr.querySelector('.input-no-order').addEventListener('change', syncHiddenInput);
        tr.querySelector('.input-tanggal').addEventListener('input', syncHiddenInput);
        tr.querySelector('.input-tanggal').addEventListener('change', syncHiddenInput);
        tr.querySelector('.btn-row-delete').addEventListener('click', function () {
            tr.remove();
            syncHiddenInput();
        });
        tbody.appendChild(tr);
        syncHiddenInput();
    }

    Object.entries(data || {}).forEach(([noOrder, dateStr]) => addRow(noOrder, dateStr));
    if (tbody.querySelectorAll('tr').length === 0) addRow('', '');

    const addBtnWrap = document.createElement('div');
    addBtnWrap.className = 'order-date-table-add-wrap';
    const addBtn = document.createElement('button');
    addBtn.type = 'button';
    addBtn.className = 'btn btn-add-order-date';
    addBtn.innerHTML = '<i class="bi bi-plus me-1"></i> Tambah baris';
    addBtn.addEventListener('click', () => { addRow('', ''); });

    tableContainer.appendChild(table);
    addBtnWrap.appendChild(addBtn);
    tableContainer.appendChild(addBtnWrap);
    wrapper.appendChild(tableContainer);
    wrapper.appendChild(hiddenInput);
    container.appendChild(wrapper);
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function renderFormFields(data, container, parentKey = '') {
    for (const [key, value] of Object.entries(data)) {
        if (key.startsWith('_') || key.endsWith('_raw')) continue;

        const fieldId = parentKey ? `${parentKey}_${key}` : key;
        const label = formatLabel(key);

        if (value === null || value === undefined) {
            const fieldType = isDateField(key) ? 'date' : 'text';
            container.appendChild(createFormField(fieldId, label, '', fieldType));
        } else if (typeof value === 'object' && !Array.isArray(value) && isOrderDateMapField(key, value)) {
            renderOrderDateTable(container, fieldId, label, value);
        } else if (typeof value === 'object' && !Array.isArray(value)) {
            const section = createFormSection(label);
            renderFormFields(value, section, fieldId);
            container.appendChild(section);
        } else if (Array.isArray(value)) {
            container.appendChild(createFormField(fieldId, label, JSON.stringify(value), 'textarea', true));
        } else {
            const rawKey = key + '_raw';
            let displayValue = data[rawKey] !== undefined ? data[rawKey] : value;
            let fieldType = 'text';

            if (isDateField(key)) {
                fieldType = 'date';
                displayValue = convertDateToInput(displayValue);
            } else if (typeof displayValue === 'number') {
                fieldType = 'number';
            }

            container.appendChild(createFormField(fieldId, label, displayValue, fieldType));
        }
    }
}

function createFormField(id, label, value, type = 'text', readonly = false) {
    const formGroup = document.createElement('div');
    formGroup.className = 'form-group';
    const labelEl = document.createElement('label');
    labelEl.className = 'form-label';
    labelEl.textContent = label;
    labelEl.setAttribute('for', id);

    let inputEl;
    if (type === 'textarea') {
        inputEl = document.createElement('textarea');
        inputEl.className = 'form-control form-input';
        inputEl.rows = 3;
    } else {
        inputEl = document.createElement('input');
        inputEl.type = type;
        inputEl.className = 'form-control form-input';
    }

    inputEl.id = id;
    inputEl.value = value || '';
    if (readonly) {
        inputEl.readOnly = true;
        inputEl.classList.add('readonly');
    }

    formGroup.appendChild(labelEl);
    formGroup.appendChild(inputEl);
    return formGroup;
}

function createFormSection(title) {
    const section = document.createElement('div');
    section.className = 'form-section';
    const header = document.createElement('div');
    header.className = 'form-section-header';
    header.textContent = title;
    const body = document.createElement('div');
    body.className = 'form-section-body';
    section.appendChild(header);
    section.appendChild(body);
    return body;
}

function formatLabel(key) {
    return key.replace(/_/g, ' ').toUpperCase();
}

// ============================================
// SAVE HANDLER
// ============================================
function setupSaveHandler() {
    document.getElementById('save-btn').addEventListener('click', async function () {
        const btn = this;
        const originalText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-repeat me-2 spinning-icon"></i>Menyimpan...';

        try {
            const formData = collectFormData();
            const backendKey = getDataKey(currentDocType);

            console.group('💾 Save Action Initiated');
            console.log('Document Type:', backendKey);
            console.log('Payload Data:', formData);

            const response = await fetch(`/projess/validate-ground-truth/${TICKET_NUMBER}/save`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN
                },
                body: JSON.stringify({
                    doc_type: backendKey,
                    data: formData
                })
            });

            const result = await response.json();

            console.log('Response Status:', response.status);
            console.log('Response Body:', result);
            console.groupEnd();

            if (!response.ok) throw new Error(result.message || 'Gagal menyimpan data');

            GROUND_TRUTH_DATA[backendKey] = formData;
            ProgressTracker.markAsSaved(currentDocType);

            btn.innerHTML = '<i class="bi bi-check me-2"></i>Tersimpan!';
            btn.style.background = '#10b981';

            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.background = '';
                btn.disabled = false;
                moveToNextDocument();
            }, 1000);

        } catch (error) {
            console.error('❌ Error saving:', error);
            alert('❌ Gagal menyimpan data: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
}

// ============================================
// NAVIGATION & DATA COLLECTION
// ============================================
function moveToNextDocument() {
    const currentIndex = AVAILABLE_DOCUMENTS.findIndex(d => d.type === currentDocType);
    if (currentIndex === -1) return;

    if (currentIndex < AVAILABLE_DOCUMENTS.length - 1) {
        const nextDoc = AVAILABLE_DOCUMENTS[currentIndex + 1];
        switchDocument(nextDoc.type);
        document.getElementById('extraction-form').scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        if (ProgressTracker.isComplete()) {
            const reviewBtn = document.getElementById('review-ticket-btn');
            reviewBtn.classList.add('pulse-animation');
            setTimeout(() => reviewBtn.classList.remove('pulse-animation'), 2000);
        }
    }
}

function collectFormData() {
    const backendKey = getDataKey(currentDocType);
    const template = FORM_TEMPLATES[backendKey];

    if (template && template.collectData) {
        return template.collectData();
    } else {
        return collectFormDataGeneric();
    }
}

function collectFormDataGeneric() {
    const formContainer = document.getElementById('extraction-form');
    const inputs = formContainer.querySelectorAll('input, textarea');
    const data = {};

    inputs.forEach(input => {
        const key = input.id;
        let value = input.value;

        if (input.dataset.orderDateTable === '1') {
            try {
                data[key] = JSON.parse(value || '{}');
            } catch (e) {
                data[key] = {};
            }
            return;
        }

        if (value === '') return;

        if (input.type === 'date' && value) {
            value = convertDateFromInput(value);
        } else if (input.type === 'number' && value) {
            value = parseFloat(value);
        }

        if (key.includes('_')) {
            setNestedValue(data, key.split('_'), value);
        } else {
            data[key] = value;
        }
    });
    return data;
}

function setNestedValue(obj, keys, value) {
    const lastKey = keys.pop();
    const nested = keys.reduce((o, k) => o[k] = o[k] || {}, obj);
    nested[lastKey] = value;
}

// Expose rotate globally for onClick events
window.rotatePage = rotatePage;