pdfjsLib.GlobalWorkerOptions.workerSrc =
    'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

// ========================================
// CONFIGURATION
// ========================================
const CONFIG = {
    scale: 1.5,
    dpi: 72,
    renderTimeout: 60000,
    windowSize: 5,
    preloadDistance: 1,
    unloadDistance: 3,
    renderDelay: 30,
    scrollDebounce: 150,
    estimatedPageHeight: 1100,
    boundingBoxLineWidth: 2,
    issueColors: {
        typo: '#dc3545',
        date: '#ffc107',
        nominal: '#17a2b8'
    }
};

// ========================================
// STATE MANAGEMENT
// ========================================
const state = {
    ticket: null,
    apiUrl: null,
    documents: [],
    pageMapping: {},
    issues: {},
    issuesList: {},
    boundingBoxesMissing: false,
    pdfDocuments: {},
    loadedDocuments: new Set(),
    renderedPages: new Map(),
    renderQueue: [],
    isRendering: false,
    currentViewportPage: 1,
    intersectionObserver: null
};

// ========================================
// INITIALIZATION
// ========================================
function initPDFViewer(ticketNumber, apiUrl = null) {
    state.ticket = ticketNumber;
    state.apiUrl = apiUrl || `/projess/api/basic-result/${ticketNumber}/issues`;

    // Show loading overlay
    showPageLoadingOverlay();

    setupObservers();
    loadTicketData();
}

// ========================================
// DATA LOADING
// ========================================
async function loadTicketData() {
    try {
        console.log('📡 Fetching ticket data...');
        console.log('🔗 API URL:', state.apiUrl);

        const response = await fetch(state.apiUrl);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        console.log('🔵 Response:', response);

        const data = await response.json();

        state.documents = data.documents || [];
        state.pageMapping = data.pageMapping || {};
        state.issues = data.issues || {};
        state.issuesList = data.issuesList || { typo: [], price: [], date: [] };
        state.boundingBoxesMissing = data.boundingBoxesMissing === true;

        // ========================================
        // DETAILED LOGGING FOR DEBUGGING
        // ========================================
        console.log('📊 ========== DATA RECEIVED FROM API ==========');
        console.log('📄 Documents:', data.documents);
        console.log('🗺️ Page Mapping:', data.pageMapping);
        console.log('🐛 Issues by Page:', data.issues);
        console.log('📋 Issues List:', data.issuesList);
        
        // Log bounding boxes per issue
        console.log('🎯 ========== BOUNDING BOXES PER PAGE ==========');
        Object.keys(data.issues || {}).forEach(globalPage => {
            const pageIssues = data.issues[globalPage] || [];
            console.log(`📄 Page ${globalPage} - ${pageIssues.length} issue(s):`);
            pageIssues.forEach((issue, index) => {
                console.log(`  Issue ${index + 1}:`, {
                    type: issue.type,
                    id: issue.id,
                    text: issue.text,
                    bbox: issue.bbox,
                    bboxDetails: {
                        x: issue.bbox?.x,
                        y: issue.bbox?.y,
                        width: issue.bbox?.width,
                        height: issue.bbox?.height
                    }
                });
            });
        });
        
        // Log issues list with locations
        console.log('📍 ========== ISSUES LIST WITH LOCATIONS ==========');
        Object.keys(data.issuesList || {}).forEach(issueType => {
            const issues = data.issuesList[issueType] || [];
            console.log(`📝 ${issueType.toUpperCase()} Issues (${issues.length}):`);
            issues.forEach((issue, index) => {
                console.log(`  Issue ${index + 1}:`, {
                    id: issue.id,
                    text: issue.text,
                    locations: issue.locations,
                    locationsCount: issue.locations?.length || 0
                });
                if (issue.locations && issue.locations.length > 0) {
                    issue.locations.forEach((loc, locIndex) => {
                        console.log(`    Location ${locIndex + 1}:`, {
                            docType: loc.docType,
                            pageInDoc: loc.pageInDoc,
                            globalPageNum: loc.globalPageNum,
                            word: loc.word
                        });
                    });
                }
            });
        });
        
        console.log('✅ ========== SUMMARY ==========');
        console.log('✅ Data loaded:', {
            documents: state.documents.length,
            pages: Object.keys(state.pageMapping).length,
            issues: Object.keys(state.issues).length,
            totalIssuesByType: {
                typo: (data.issuesList?.typo || []).length,
                price: (data.issuesList?.price || []).length,
                date: (data.issuesList?.date || []).length
            }
        });

        if (data.summary) {
            updateSummaryStats(data.summary);
        }

        await buildViewer();
        renderIssueLists();
        showBoundingBoxesMissingBannerIfNeeded();

        // Hide loading overlay when everything is loaded
        hidePageLoadingOverlay();

    } catch (error) {
        console.error('❌ Load error:', error);
        hidePageLoadingOverlay();
        showError('Gagal memuat data: ' + error.message);
    }
}

/** Get issues for a global page; works with both string and number keys from API. */
function getIssuesForPage(globalPageNum) {
    const key = globalPageNum;
    const strKey = String(globalPageNum);
    return state.issues[key] || state.issues[strKey] || [];
}

/** Show banner when API returned issues but no bounding box data (e.g. QA DB missing bbox rows). */
function showBoundingBoxesMissingBannerIfNeeded() {
    if (!state.boundingBoxesMissing) return;
    const container = document.getElementById('pdf-container');
    if (!container || !container.parentElement) return;
    const banner = document.createElement('div');
    banner.className = 'alert alert-info d-flex align-items-center mb-3';
    banner.setAttribute('role', 'alert');
    banner.innerHTML = '<i class="bi bi-info-circle-fill me-2"></i><div>Daftar issue ditampilkan di panel kiri, tetapi <strong>lokasi di PDF tidak tersedia</strong> (data bounding box tidak ditemukan di server). Periksa lingkungan/server atau jalankan ulang basic review jika perlu.</div>';
    container.parentElement.insertBefore(banner, container);
}

// ========================================
// VIEWER BUILDING
// ========================================
async function buildViewer() {
    console.log('🏗️ ========== BUILDING VIEWER ==========');
    console.log('📊 State overview:', {
        documentsCount: state.documents.length,
        pageMappingCount: Object.keys(state.pageMapping).length,
        issuesCount: Object.keys(state.issues).length,
        documents: state.documents,
        pageMapping: state.pageMapping
    });

    const container = document.getElementById('pdf-container');
    if (!container) {
        console.error('❌ PDF container not found!');
        return;
    }

    container.innerHTML = '';
    container.style.display = 'block';

    const globalPages = Object.keys(state.pageMapping)
        .map(Number)
        .sort((a, b) => a - b);

    console.log(`📄 Creating ${globalPages.length} virtual page(s):`, globalPages);
    console.log('📋 Page mapping details:');
    globalPages.forEach(pageNum => {
        const mapping = state.pageMapping[pageNum];
        const pageIssues = getIssuesForPage(pageNum);
        console.log(`  Page ${pageNum}:`, {
            docType: mapping.docType,
            pageInDoc: mapping.pageInDoc,
            issuesCount: pageIssues.length,
            issues: pageIssues
        });
    });

    globalPages.forEach(pageNum => {
        const pageEl = createVirtualPage(pageNum);
        container.appendChild(pageEl);
    });

    console.log('🎨 Rendering all pages immediately...');
    for (const pageNum of globalPages) {
        await renderPage(pageNum);
    }

    console.log('✅ All pages rendered');
    console.log('🏁 ========== VIEWER BUILD COMPLETE ==========');
}

function createVirtualPage(globalPageNum) {
    const info = state.pageMapping[globalPageNum];
    
    // Calculate issue count: 
    // 1. If totalIssues is provided in pageMapping (for documents without bounding boxes), use that
    // 2. Otherwise, count from state.issues (for pages with bounding boxes)
    let issueCount = 0;
    if (info?.totalIssues !== null && info?.totalIssues !== undefined) {
        issueCount = info.totalIssues;
    } else {
        issueCount = getIssuesForPage(globalPageNum)?.length || 0;
    }
    
    console.log(`📄 Creating virtual page ${globalPageNum}:`, {
        docType: info?.docType,
        pageInDoc: info?.pageInDoc,
        issueCount: issueCount,
        totalIssuesFromMapping: info?.totalIssues,
        issuesFromState: getIssuesForPage(globalPageNum)?.length || 0,
        issues: getIssuesForPage(globalPageNum)
    });

    const page = document.createElement('div');
    page.id = `page-${globalPageNum}`;
    page.className = 'virtual-page';
    page.dataset.globalPage = globalPageNum;
    page.dataset.docType = info.docType;
    page.dataset.pageInDoc = info.pageInDoc;
    page.dataset.state = 'unloaded';

    page.style.minHeight = `${CONFIG.estimatedPageHeight}px`;

    page.innerHTML = `
        <div class="page-header">
            <div class="page-header-info">
                <i class="bi bi-file"></i>
                <span class="page-doc-badge">${info.docType}</span>
                <span class="page-number">Halaman ${info.pageInDoc}</span>
            </div>
            <span class="page-issues-badge">${issueCount} issue(s)</span>
        </div>
        <div class="page-content">
            <div class="page-loading">
                <div class="spinner-border text-primary mb-2"></div>
                <p class="text-muted small">Memuat halaman...</p>
            </div>
        </div>
    `;

    return page;
}

// ========================================
// INTERSECTION OBSERVER
// ========================================
function setupObservers() {
    console.log('👁️ Observers disabled - rendering all pages upfront');
}

function startObserving() { }

// ========================================
// PAGE RENDERING
// ========================================
async function renderPage(globalPageNum) {
    const pageEl = document.getElementById(`page-${globalPageNum}`);
    if (!pageEl || pageEl.dataset.state === 'loaded') return;

    console.log(`🎨 Rendering page ${globalPageNum}`);

    pageEl.dataset.state = 'loading';
    showLoadingInPage(pageEl);

    try {
        const info = state.pageMapping[globalPageNum];
        const docInfo = state.documents.find(d => d.docType === info.docType);

        if (!docInfo) throw new Error('Document info not found');

        const pdf = await loadPDF(info.docType, docInfo.pdfUrl);
        if (!pdf) throw new Error('Failed to load PDF');

        const page = await pdf.getPage(info.pageInDoc);
        const viewport = page.getViewport({ scale: CONFIG.scale });

        const canvas = document.createElement('canvas');
        canvas.className = 'pdf-canvas';
        canvas.width = viewport.width;
        canvas.height = viewport.height;

        const ctx = canvas.getContext('2d');

        await page.render({
            canvasContext: ctx,
            viewport: viewport
        }).promise;

        const issues = getIssuesForPage(globalPageNum) || [];
        
        console.log(`🎯 ========== RENDERING PAGE ${globalPageNum} ==========`);
        console.log(`📄 Page Info:`, info);
        console.log(`🐛 Issues for this page:`, issues);
        console.log(`📊 Issues count:`, issues.length);
        
        if (issues.length > 0) {
            console.log(`🎨 Drawing ${issues.length} bounding box(es) for page ${globalPageNum}:`);
            issues.forEach((issue, index) => {
                console.log(`  Bounding Box ${index + 1}:`, {
                    type: issue.type,
                    id: issue.id,
                    text: issue.text?.substring(0, 50),
                    bbox: issue.bbox,
                    calculatedPosition: {
                        x: issue.bbox?.x ? issue.bbox.x * CONFIG.dpi * viewport.scale : 'N/A',
                        y: issue.bbox?.y ? issue.bbox.y * CONFIG.dpi * viewport.scale : 'N/A',
                        width: issue.bbox?.width ? issue.bbox.width * CONFIG.dpi * viewport.scale : 'N/A',
                        height: issue.bbox?.height ? issue.bbox.height * CONFIG.dpi * viewport.scale : 'N/A'
                    }
                });
            });
        } else {
            console.warn(`⚠️ No issues found for page ${globalPageNum}`);
        }
        
        drawBoundingBoxes(ctx, issues, viewport.scale);

        const contentDiv = pageEl.querySelector('.page-content');
        contentDiv.innerHTML = '';
        contentDiv.appendChild(canvas);

        pageEl.style.minHeight = 'auto';
        pageEl.dataset.state = 'loaded';

        state.renderedPages.set(globalPageNum, {
            canvas: canvas,
            height: viewport.height
        });

        console.log(`✅ Page ${globalPageNum} rendered`);

    } catch (error) {
        console.error(`❌ Render error page ${globalPageNum}:`, error);
        pageEl.dataset.state = 'error';
        showErrorInPage(pageEl, error.message);
    }
}

function showLoadingInPage(pageEl) {
    const content = pageEl.querySelector('.page-content');
    content.innerHTML = `
        <div class="page-loading">
            <div class="spinner-border text-primary mb-2"></div>
            <p class="text-muted small">Memuat halaman...</p>
        </div>
    `;
}

function showErrorInPage(pageEl, message) {
    const content = pageEl.querySelector('.page-content');
    content.innerHTML = `
        <div class="page-error alert alert-danger m-3">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Error</strong>
            <p class="mb-0 small">${message}</p>
        </div>
    `;
}

// ========================================
// PDF LOADING
// ========================================
async function loadPDF(docType, pdfUrl, retries = 3) {
    if (state.pdfDocuments[docType]) {
        return state.pdfDocuments[docType];
    }

    for (let attempt = 1; attempt <= retries; attempt++) {
        try {
            console.log(`📥 Loading ${docType} (attempt ${attempt})`);
            console.log(`📎 URL: ${pdfUrl}`);

            const loadTask = pdfjsLib.getDocument({
                url: pdfUrl,
                cMapUrl: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/cmaps/',
                cMapPacked: true
            });

            const pdf = await Promise.race([
                loadTask.promise,
                new Promise((_, reject) =>
                    setTimeout(() => reject(new Error('Timeout')), CONFIG.renderTimeout)
                )
            ]);

            state.pdfDocuments[docType] = pdf;
            state.loadedDocuments.add(docType);

            console.log(`✅ ${docType} loaded (${pdf.numPages} pages)`);
            return pdf;

        } catch (error) {
            console.error(`❌ Load failed ${docType} (${attempt}/${retries}):`, error);
            if (attempt === retries) {
                throw error;
            }
            await new Promise(r => setTimeout(r, 1000 * attempt));
        }
    }
}

// ========================================
// BOUNDING BOXES
// ========================================
function drawBoundingBoxes(ctx, issues, scale) {
    console.log(`🎨 drawBoundingBoxes called with ${issues.length} issue(s), scale: ${scale}`);
    
    if (!issues || issues.length === 0) {
        console.warn('⚠️ drawBoundingBoxes: No issues to draw');
        return;
    }
    
    issues.forEach((issue, index) => {
        const bbox = issue.bbox;
        
        if (!bbox) {
            console.error(`❌ Issue ${index + 1} has no bbox:`, issue);
            return;
        }
        
        const color = CONFIG.issueColors[issue.type] || '#000';

        const x = bbox.x * CONFIG.dpi * scale;
        const y = bbox.y * CONFIG.dpi * scale;
        const w = bbox.width * CONFIG.dpi * scale;
        const h = bbox.height * CONFIG.dpi * scale;

        console.log(`  Drawing bbox ${index + 1}:`, {
            type: issue.type,
            id: issue.id,
            originalBbox: bbox,
            calculatedCoords: { x, y, w, h },
            color: color
        });

        ctx.strokeStyle = color;
        ctx.lineWidth = CONFIG.boundingBoxLineWidth;
        ctx.strokeRect(x, y, w, h);

        drawLabel(ctx, issue.type.toUpperCase(), x, y, color);
        
        console.log(`  ✅ Bounding box ${index + 1} drawn successfully`);
    });
    
    console.log(`✅ Finished drawing ${issues.length} bounding box(es)`);
}

function drawLabel(ctx, text, x, y, color) {
    const fontSize = 11;
    ctx.font = `bold ${fontSize}px Arial`;
    const metrics = ctx.measureText(text);
    const pad = 5;
    const lh = 18;
    const radius = 8;

    let ly = y - lh - 2;
    if (ly < 0) ly = y + 2;

    const labelWidth = metrics.width + pad * 2;

    ctx.fillStyle = color;
    ctx.beginPath();
    ctx.moveTo(x + radius, ly);
    ctx.lineTo(x + labelWidth - radius, ly);
    ctx.quadraticCurveTo(x + labelWidth, ly, x + labelWidth, ly + radius);
    ctx.lineTo(x + labelWidth, ly + lh - radius);
    ctx.quadraticCurveTo(x + labelWidth, ly + lh, x + labelWidth - radius, ly + lh);
    ctx.lineTo(x + radius, ly + lh);
    ctx.quadraticCurveTo(x, ly + lh, x, ly + lh - radius);
    ctx.lineTo(x, ly + radius);
    ctx.quadraticCurveTo(x, ly, x + radius, ly);
    ctx.closePath();
    ctx.fill();

    ctx.fillStyle = 'white';
    ctx.textBaseline = 'middle';
    ctx.fillText(text, x + pad, ly + lh / 2);
}

// ========================================
// SCROLL NAVIGATION
// ========================================
function scrollToGlobalPage(globalPage) {
    console.log(`🎯 Scrolling to page ${globalPage}`);

    const pageEl = document.getElementById(`page-${globalPage}`);
    if (!pageEl) return;

    pageEl.scrollIntoView({ behavior: 'smooth', block: 'center' });

    pageEl.classList.add('highlight-flash');
    setTimeout(() => pageEl.classList.remove('highlight-flash'), 2000);

    updateActiveButtons(globalPage);
}

function updateActiveButtons(globalPage) {
    document.querySelectorAll('.location-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    document.querySelectorAll(`.location-btn[data-global-page="${globalPage}"]`).forEach(btn => {
        btn.classList.add('active');
    });
}

// ========================================
// ISSUES LIST
// ========================================
function renderIssueLists() {
    renderIssueType('typo', state.issuesList.typo, 'typo-list', 'typo-count', 'typo-section');
    renderIssueType('price', state.issuesList.price, 'nominal-list', 'nominal-count', 'nominal-section');
    renderIssueType('date', state.issuesList.date, 'date-list', 'date-count', 'date-section');

    const loadingEl = document.getElementById('issues-loading');
    if (loadingEl) {
        loadingEl.style.display = 'none';
    }
}

function renderIssueType(type, issues, listId, countId, sectionId) {
    const listEl = document.getElementById(listId);
    const countEl = document.getElementById(countId);
    const sectionEl = document.getElementById(sectionId);

    if (!listEl || !countEl || !sectionEl) return;

    listEl.innerHTML = '';
    countEl.textContent = issues.length;

    sectionEl.style.display = 'flex';

    if (issues.length > 0) {
        issues.forEach(issue => {
            listEl.appendChild(createIssueCard(type, issue));
        });
    } else {
        listEl.appendChild(createEmptyState(type));
    }
}

function createIssueCard(type, issue) {
    const card = document.createElement('div');
    card.className = 'issue-card';

    const iconClass = type === 'typo' ? 'bi-spellcheck' : type === 'price' ? 'bi-currency-dollar' : 'bi-calendar';
    const typeClass = type === 'typo' ? 'typo' : type === 'price' ? 'nominal' : 'date';

    // Create header section
    const headerDiv = document.createElement('div');
    headerDiv.className = 'issue-card-header';
    
    const iconDiv = document.createElement('div');
    iconDiv.className = `issue-card-icon ${typeClass}`;
    const icon = document.createElement('i');
    icon.className = `bi ${iconClass}`;
    iconDiv.appendChild(icon);
    
    const contentDiv = document.createElement('div');
    contentDiv.className = 'issue-card-content';
    
    const textDiv = document.createElement('div');
    textDiv.className = 'issue-card-text';
    textDiv.textContent = issue.text;
    
    contentDiv.appendChild(textDiv);
    
    if (issue.correction) {
        const correctionDiv = document.createElement('div');
        correctionDiv.className = 'issue-card-correction';
        const checkIcon = document.createElement('i');
        checkIcon.className = 'bi bi-check-circle';
        correctionDiv.appendChild(checkIcon);
        correctionDiv.appendChild(document.createTextNode(' ' + issue.correction));
        contentDiv.appendChild(correctionDiv);
    }
    
    headerDiv.appendChild(iconDiv);
    headerDiv.appendChild(contentDiv);
    card.appendChild(headerDiv);

    // Create locations section if locations exist
    if (issue.locations?.length > 0) {
        const locationsDiv = document.createElement('div');
        locationsDiv.className = 'issue-card-locations';

        issue.locations.forEach((loc, index) => {
            const locationRow = document.createElement('div');
            locationRow.className = 'issue-card-location-row';
            
            // Location button
            const locationBtn = document.createElement('button');
            locationBtn.className = 'location-btn';
            locationBtn.dataset.globalPage = loc.globalPageNum;
            
            const locationIcon = document.createElement('i');
            locationIcon.className = 'bi bi-file-pdf';
            locationBtn.appendChild(locationIcon);
            
            const locationSpan = document.createElement('span');
            locationSpan.textContent = `${loc.docType} - Hal. ${loc.pageInDoc}`;
            locationBtn.appendChild(locationSpan);
            
            // Use event listener instead of inline onclick
            locationBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (typeof scrollToGlobalPage === 'function') {
                    scrollToGlobalPage(loc.globalPageNum);
                }
            });
            
            // Add note button
            const addNoteBtn = document.createElement('button');
            addNoteBtn.className = 'add-note-btn';
            addNoteBtn.title = 'Tambah ke Notes';
            
            const plusIcon = document.createElement('i');
            plusIcon.className = 'bi bi-plus';
            addNoteBtn.appendChild(plusIcon);
            
            // Use event listener with closure to capture issue and location data
            addNoteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Check if the function exists before calling
                if (typeof window.addIssueToNotes === 'function') {
                    window.addIssueToNotes(issue, loc);
                } else {
                    console.error('❌ addIssueToNotes function not found. Make sure notes.js is loaded.');
                }
            });
            
            locationRow.appendChild(locationBtn);
            locationRow.appendChild(addNoteBtn);
            locationsDiv.appendChild(locationRow);
        });

        card.appendChild(locationsDiv);
    }

    return card;
}

function createEmptyState(type) {
    const emptyState = document.createElement('div');
    emptyState.className = 'issue-empty-state';

    const messages = {
        typo: {
            icon: 'fa-check-circle',
            title: 'Tidak ada issue ditemukan',
            description: 'Semua pengetikan sudah sesuai'
        },
        price: {
            icon: 'fa-check-circle',
            title: 'Tidak ada issue ditemukan',
            description: 'Semua format nominal sudah konsisten'
        },
        date: {
            icon: 'fa-check-circle',
            title: 'Tidak ada issue ditemukan',
            description: 'Semua format tanggal sudah sesuai'
        }
    };

    const msg = messages[type];

    emptyState.innerHTML = `
        <i class="fas ${msg.icon}"></i>
        <p class="empty-state-title">${msg.title}</p>
        <p>${msg.description}</p>
    `;

    return emptyState;
}

// ========================================
// UTILITIES
// ========================================
function updateSummaryStats(summary) {
    const totalDocsEl = document.getElementById('total-documents');
    const totalPagesEl = document.getElementById('total-pages');
    const totalIssuesEl = document.getElementById('summary-issues');

    if (totalDocsEl) totalDocsEl.textContent = summary.totalDocuments || 0;
    if (totalPagesEl) totalPagesEl.textContent = summary.totalPagesWithIssues || 0;
    if (totalIssuesEl) totalIssuesEl.textContent = summary.totalIssues || 0;
}

function showError(message) {
    const container = document.getElementById('pdf-container');
    if (container) {
        container.innerHTML = `
            <div class="alert alert-danger m-4">
                <h4><i class="bi bi-exclamation-triangle"></i> Error</h4>
                <p>${escapeHtml(message)}</p>
            </div>
        `;
        container.style.display = 'block';
    }

    const issuesContainer = document.getElementById('issues-loading');
    if (issuesContainer) {
        issuesContainer.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i>
                ${escapeHtml(message)}
            </div>
        `;
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ========================================
// LOADING OVERLAY FUNCTIONS
// ========================================
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

// ========================================
// EXPOSE GLOBAL FUNCTIONS
// ========================================
window.initPDFViewer = initPDFViewer;
window.scrollToGlobalPage = scrollToGlobalPage;