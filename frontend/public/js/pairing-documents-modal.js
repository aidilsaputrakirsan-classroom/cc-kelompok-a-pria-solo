/**
 * Pairing Documents Modal & Comparison JavaScript
 * Handles document selection modal and comparison page functionality
 */

// ========================================
// PAIRING DOCUMENTS MODAL
// ========================================

// Global state for pairing documents
let pairingDocumentsData = {
    documents: {},
    doc1Selected: null,
    doc2Selected: null,
    ticketNumber: null
};

// Initialize modal when it's shown
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('pairingDocsModal');
    if (modal) {
        modal.addEventListener('show.bs.modal', function () {
            console.log('Pairing Documents Modal opened');
            loadPairingDocuments();
        });
    }
});

/**
 * Load available documents from server
 */
function loadPairingDocuments() {
    const ticketNumber = getTicketNumberFromPage();
    if (!ticketNumber) {
        showPairingError('Could not determine ticket number');
        return;
    }

    pairingDocumentsData.ticketNumber = ticketNumber;

    // Show loading state
    document.getElementById('pairingDocsLoading').style.display = 'block';
    document.getElementById('pairingDocsList').style.display = 'none';
    document.getElementById('pairingDocsError').style.display = 'none';

    // Fetch available documents
    const apiUrl = `/projess/api/tickets/${ticketNumber}/pairing-documents/available`;
    console.log('Fetching from:', apiUrl);
    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.documents) {
                pairingDocumentsData.documents = data.documents;
                populateDocumentSelects(data.documents);
                renderDocumentsGrid(data.documents);

                // Show documents list
                document.getElementById('pairingDocsLoading').style.display = 'none';
                document.getElementById('pairingDocsList').style.display = 'block';
                document.getElementById('pairingDocsError').style.display = 'none';
            } else {
                throw new Error(data.message || 'Failed to load documents');
            }
        })
        .catch(error => {
            console.error('Error loading pairing documents:', error);
            showPairingError(error.message || 'Failed to load documents');
        });
}

/**
 * Populate document select dropdowns
 */
function populateDocumentSelects(documents) {
    const doc1Select = document.getElementById('doc1Select');
    const doc2Select = document.getElementById('doc2Select');

    // Clear existing options (except placeholder)
    doc1Select.innerHTML = '<option value="" disabled selected style="color: #9ca3af;">Select a document...</option>';
    doc2Select.innerHTML = '<option value="" disabled selected style="color: #9ca3af;">Select a document...</option>';

    // Add options for each document type group
    Object.keys(documents).sort().forEach(docType => {
        const documentsOfType = documents[docType];
        
        documentsOfType.forEach((doc, index) => {
            const optionValue = `${doc.path}`;
            const optionLabel = `${doc.type} - ${doc.filename} (${doc.size})`;
            
            const option1 = document.createElement('option');
            option1.value = optionValue;
            option1.textContent = optionLabel;
            option1.dataset.type = doc.type;
            option1.dataset.filename = doc.filename;
            doc1Select.appendChild(option1);

            const option2 = document.createElement('option');
            option2.value = optionValue;
            option2.textContent = optionLabel;
            option2.dataset.type = doc.type;
            option2.dataset.filename = doc.filename;
            doc2Select.appendChild(option2);
        });
    });
}

/**
 * Render documents grid display
 */
function renderDocumentsGrid(documents) {
    const grid = document.getElementById('documentsGrid');
    grid.innerHTML = '';

    const groupedByType = {};
    Object.keys(documents).forEach(docType => {
        groupedByType[docType] = documents[docType];
    });

    let html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">';
    
    Object.keys(groupedByType).sort().forEach(docType => {
        groupedByType[docType].forEach(doc => {
            html += `
                <div class="document-card" style="background: white; border: 2px solid #e5e7eb; border-radius: 12px; padding: 14px; cursor: pointer; transition: all 0.3s ease;"
                     onmouseover="this.style.borderColor='#667eea'; this.style.boxShadow='0 4px 12px rgba(102, 126, 234, 0.1)';"
                     onmouseout="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';"
                     onclick="selectDocumentFromGrid('${doc.path}')">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <svg width="16" height="16" fill="none" stroke="#667eea" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                        </svg>
                        <span style="font-weight: 600; color: #1f2937; font-size: 0.875rem;">${doc.type}</span>
                    </div>
                    <p style="margin: 0; color: #6b7280; font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.4;">
                        ${doc.filename}
                    </p>
                    <p style="margin: 8px 0 0 0; color: #9ca3af; font-size: 0.7rem;">
                        ${doc.size}
                    </p>
                </div>
            `;
        });
    });

    html += '</div>';
    grid.innerHTML = html;
}

/**
 * Select document from grid
 */
function selectDocumentFromGrid(docPath) {
    const doc1Select = document.getElementById('doc1Select');
    const doc2Select = document.getElementById('doc2Select');

    // Select in first empty select or alternate
    if (!doc1Select.value) {
        doc1Select.value = docPath;
    } else if (!doc2Select.value) {
        doc2Select.value = docPath;
    } else {
        // Both filled, alert user they need to deselect one
        alert('Please deselect one document first to select a different one');
        return;
    }

    updatePairingSelection();
}

/**
 * Update selection state and UI
 */
function updatePairingSelection() {
    const doc1Select = document.getElementById('doc1Select');
    const doc2Select = document.getElementById('doc2Select');
    const selectionInfo = document.getElementById('selectionInfo');
    const compareBtn = document.getElementById('compareBtnModal');

    pairingDocumentsData.doc1Selected = doc1Select.value;
    pairingDocumentsData.doc2Selected = doc2Select.value;

    // Check if both are selected
    if (pairingDocumentsData.doc1Selected && pairingDocumentsData.doc2Selected) {
        // Check if same document selected
        if (pairingDocumentsData.doc1Selected === pairingDocumentsData.doc2Selected) {
            selectionInfo.style.display = 'none';
            compareBtn.style.display = 'none';
            doc1Select.style.borderColor = '#fca5a5';
            doc2Select.style.borderColor = '#fca5a5';
            return;
        }

        selectionInfo.style.display = 'block';
        compareBtn.style.display = 'block';
        doc1Select.style.borderColor = '#e5e7eb';
        doc2Select.style.borderColor = '#e5e7eb';
    } else {
        selectionInfo.style.display = 'none';
        compareBtn.style.display = 'none';
    }
}

/**
 * Proceed with comparison
 */
function proceedWithComparison() {
    const doc1 = pairingDocumentsData.doc1Selected;
    const doc2 = pairingDocumentsData.doc2Selected;
    const ticketNumber = pairingDocumentsData.ticketNumber;

    if (!doc1 || !doc2 || doc1 === doc2) {
        alert('Please select two different documents');
        return;
    }

    // Navigate to comparison page
    const params = new URLSearchParams({
        doc1: doc1,
        doc2: doc2
    });

    window.location.href = `/projess/tickets/${ticketNumber}/pairing-documents/compare?${params.toString()}`;
}

/**
 * Show pairing error
 */
function showPairingError(message) {
    document.getElementById('pairingDocsLoading').style.display = 'none';
    document.getElementById('pairingDocsList').style.display = 'none';
    document.getElementById('pairingDocsError').style.display = 'block';
    document.getElementById('pairingDocsErrorText').textContent = message;
}

/**
 * Get ticket number from page
 */
function getTicketNumberFromPage() {
    // Try to get from data attribute
    const container = document.querySelector('[data-ticket-number]');
    if (container) {
        return container.dataset.ticketNumber;
    }

    // Try to get from URL
    const urlMatch = window.location.pathname.match(/tickets\/([^\/]+)/);
    if (urlMatch) {
        return urlMatch[1];
    }

    return null;
}
