/**
 * Pairing Documents Comparison Page JavaScript
 * Displays all PDF pages vertically without pagination
 */

// ========================================
// PDF VIEWER CONFIGURATION
// ========================================

// Set PDF.js worker
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

// Global state
const comparisonState = {
    pdf1: null,
    pdf2: null,
    numPages1: 0,
    numPages2: 0,
    zoom: 3.0,  // Increased from 2.5 for better default quality
    zoomStep: 0.1,
    maxZoom: 5,
    minZoom: 0.5,
    isLoading: true,
    containerWidth: 0
};

// ========================================
// INITIALIZATION
// ========================================

document.addEventListener('DOMContentLoaded', function () {
    console.log('Pairing Documents Comparison Page Loaded');
    initializeComparison();
});

async function initializeComparison() {
    try {
        // Get document URLs from data
        const dataEl = document.getElementById('pairingDocumentsData');
        if (!dataEl) {
            console.error('Document data not found');
            return;
        }

        const data = JSON.parse(dataEl.textContent);
        const doc1Url = data.doc1_url;
        const doc2Url = data.doc2_url;

        console.log('Loading PDFs:', doc1Url, doc2Url);

        // Load both PDFs
        await Promise.all([
            loadPDF(doc1Url, 'pdf1'),
            loadPDF(doc2Url, 'pdf2')
        ]);

        comparisonState.isLoading = false;

        // Render all pages
        await renderAllPages();

        // Setup event listeners
        setupEventListeners();

        console.log('Comparison initialized successfully');
    } catch (error) {
        console.error('Error initializing comparison:', error);
        showError('Failed to load documents for comparison');
    }
}

/**
 * Load PDF document by fetching as ArrayBuffer then passing to PDF.js.
 * This avoids MissingPDFException caused by PDF.js internal fetch (CORS/credentials).
 */
async function loadPDF(url, pdfId) {
    try {
        console.log(`Loading PDF ${pdfId} from URL:`, url);

        const response = await fetch(url, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { Accept: 'application/pdf' }
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const arrayBuffer = await response.arrayBuffer();
        if (!arrayBuffer || arrayBuffer.byteLength === 0) {
            throw new Error('Empty response');
        }

        const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;

        comparisonState[pdfId] = pdf;

        if (pdfId === 'pdf1') {
            comparisonState.numPages1 = pdf.numPages;
        } else {
            comparisonState.numPages2 = pdf.numPages;
        }

        console.log(`PDF ${pdfId} loaded successfully with ${pdf.numPages} pages`);
    } catch (error) {
        console.error(`Error loading PDF ${pdfId}:`, error);
        console.error('URL was:', url);
        throw error;
    }
}

/**
 * Render all pages for both PDFs vertically
 */
async function renderAllPages() {
    try {
        // Clear containers
        const container1 = document.getElementById('pdf-container-1');
        const container2 = document.getElementById('pdf-container-2');
        
        container1.innerHTML = '';
        container2.innerHTML = '';
        
        // Adjust container styling for full page display
        container1.style.display = 'flex';
        container1.style.flexDirection = 'column';
        container1.style.alignItems = 'center';
        container1.style.justifyContent = 'flex-start';
        container1.style.padding = '16px';
        
        container2.style.display = 'flex';
        container2.style.flexDirection = 'column';
        container2.style.alignItems = 'center';
        container2.style.justifyContent = 'flex-start';
        container2.style.padding = '16px';

        // Render all pages for both PDFs
        const maxPages = Math.max(comparisonState.numPages1, comparisonState.numPages2);
        
        for (let pageNum = 1; pageNum <= maxPages; pageNum++) {
            // Render page for PDF1 if it exists
            if (pageNum <= comparisonState.numPages1) {
                await renderPageToPdf(comparisonState.pdf1, pageNum, 'pdf-container-1');
            }

            // Render page for PDF2 if it exists
            if (pageNum <= comparisonState.numPages2) {
                await renderPageToPdf(comparisonState.pdf2, pageNum, 'pdf-container-2');
            }
        }

        console.log('All pages rendered successfully');
    } catch (error) {
        console.error('Error rendering all pages:', error);
        showError('Error rendering pages');
    }
}

/**
 * Render single PDF page to canvas with maximum quality
 * Uses higher pixel density for crisp text and images
 */
async function renderPageToPdf(pdf, pageNum, containerId) {
    const container = document.getElementById(containerId);
    if (!pdf) return;

    try {
        const page = await pdf.getPage(pageNum);
        
        // Use higher device pixel ratio and quality multiplier for better quality
        // Multiply by 2 for high-DPI displays, capped at 3 for performance
        const devicePixelRatio = Math.min((window.devicePixelRatio || 1) * 1.5, 3);
        const scale = comparisonState.zoom * devicePixelRatio;
        
        const viewport = page.getViewport({ scale });

        // Create canvas with maximum DPI support
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d', { 
            alpha: false,
            willReadFrequently: true 
        });
        
        // Set canvas size for high DPI rendering
        canvas.width = viewport.width;
        canvas.height = viewport.height;
        
        // Set CSS size to maintain proper display (CSS pixels)
        const cssScale = devicePixelRatio;
        canvas.style.width = (viewport.width / cssScale) + 'px';
        canvas.style.height = (viewport.height / cssScale) + 'px';
        canvas.style.display = 'block';
        canvas.style.marginBottom = '16px';
        canvas.style.borderRadius = '8px';
        canvas.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.1)';
        canvas.style.maxWidth = '100%';

        // Enable maximum quality rendering
        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = 'high';

        // Render with high-quality intent
        const renderTask = page.render({ 
            canvasContext: context, 
            viewport,
            intent: 'display',
            canvasFactory: null
        });
        
        await renderTask.promise;

        console.log(`✅ Page ${pageNum} rendered at ${Math.round(scale * 100)}% scale for high quality`);

        // Append to container
        container.appendChild(canvas);

    } catch (error) {
        console.error('Error rendering page to canvas:', error);
        throw error;
    }
}

/**
 * Setup event listeners
 */
function setupEventListeners() {
    // Zoom controls
    document.getElementById('changeSizeUp').addEventListener('click', zoomIn);
    document.getElementById('changeSizeDown').addEventListener('click', zoomOut);
    document.getElementById('resetZoom').addEventListener('click', resetZoom);

    // Download
    document.getElementById('downloadBoth').addEventListener('click', downloadBothPDFs);

    // Keyboard shortcuts
    document.addEventListener('keydown', handleKeyPress);
}

// ========================================
// ZOOM CONTROLS
// ========================================

async function zoomIn() {
    if (comparisonState.zoom < comparisonState.maxZoom) {
        comparisonState.zoom = Math.min(
            comparisonState.zoom + comparisonState.zoomStep,
            comparisonState.maxZoom
        );
        updateZoomDisplay();
        await renderAllPages();
    }
}

async function zoomOut() {
    if (comparisonState.zoom > comparisonState.minZoom) {
        comparisonState.zoom = Math.max(
            comparisonState.zoom - comparisonState.zoomStep,
            comparisonState.minZoom
        );
        updateZoomDisplay();
        await renderAllPages();
    }
}

async function resetZoom() {
    comparisonState.zoom = 1;
    updateZoomDisplay();
    await renderAllPages();
}

function updateZoomDisplay() {
    const percentage = Math.round(comparisonState.zoom * 100);
    const zoomElement = document.getElementById('zoomPercentage');
    if (zoomElement) {
        zoomElement.textContent = percentage + '%';
    }
}

// ========================================
// KEYBOARD SHORTCUTS
// ========================================

function handleKeyPress(event) {
    switch (event.key) {
        case '+':
        case '=':
            zoomIn();
            break;
        case '-':
            zoomOut();
            break;
        case '0':
            if (event.ctrlKey || event.metaKey) {
                resetZoom();
                event.preventDefault();
            }
            break;
    }
}

// ========================================
// DOWNLOAD
// ========================================

async function downloadBothPDFs() {
    try {
        const dataEl = document.getElementById('pairingDocumentsData');
        const data = JSON.parse(dataEl.textContent);

        const doc1Url = data.doc1_url;
        const doc2Url = data.doc2_url;

        // Extract filenames from URLs (rough estimation)
        const timestamp = new Date().getTime();
        
        // Download both files
        downloadFile(doc1Url, `document1_${timestamp}.pdf`);
        downloadFile(doc2Url, `document2_${timestamp}.pdf`);

    } catch (error) {
        console.error('Error downloading PDFs:', error);
        alert('Failed to download documents');
    }
}

function downloadFile(url, filename) {
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// ========================================
// ERROR HANDLING
// ========================================

function showError(message) {
    console.error('Comparison Error:', message);
    alert(message);
}
