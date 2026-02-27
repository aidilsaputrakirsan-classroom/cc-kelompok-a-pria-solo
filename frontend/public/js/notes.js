document.addEventListener('DOMContentLoaded', function () {
    const fabBtn = document.getElementById('fabNotes');
    const notesPanel = document.getElementById('notesPanel');
    const overlay = document.getElementById('notesOverlay');
    const closeBtn = document.getElementById('closeNotesPanel');
    const saveBtn = document.getElementById('saveNotesBtn');
    const copyBtn = document.getElementById('copyAllNotesBtn');

    const ticketNumber = getTicketNumber();

    // ✅ DYNAMIC POINTS STATE
    let pointIdCounter = 0;
    const categoryPoints = {
        mitra: [],
        obl: [],
        internal_telkom: [],
        segmen_witel: [],
        revisi_precise: []
    };

    // ✅ AUTO-SAVE STATE
    let autoSaveTimeout = null;
    let isSaving = false;
    let lastSavedState = JSON.stringify(categoryPoints);

    // Open notes panel
    fabBtn.addEventListener('click', function () {
        notesPanel.classList.add('active');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        loadNotes();
    });

    // Close notes panel
    function closeNotesPanel() {
        notesPanel.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    closeBtn.addEventListener('click', closeNotesPanel);
    overlay.addEventListener('click', closeNotesPanel);

    // Manual save
    saveBtn.addEventListener('click', function () {
        saveNotes(true);
    });

    copyBtn.addEventListener('click', function () {
        copyAllNotes();
    });

    // Get ticket number from page
    function getTicketNumber() {
        const urlParts = window.location.pathname.split('/');
        const ticketIndex = urlParts.indexOf('tickets') + 1;
        if (ticketIndex > 0 && urlParts[ticketIndex]) {
            return urlParts[ticketIndex];
        }

        const ticketElement = document.querySelector('[data-ticket-number]');
        if (ticketElement) {
            return ticketElement.dataset.ticketNumber;
        }

        return null;
    }

    // ========================================
    // AUTO-RESIZE TEXTAREA FUNCTION
    // ========================================
    function autoResizeTextarea(textarea) {
        textarea.style.height = 'auto';
        const newHeight = Math.min(textarea.scrollHeight, 200);
        textarea.style.height = newHeight + 'px';
    }

    // ========================================
    // ✅ NEW: AUTO-DELETE EMPTY NOTES ON BLUR
    // ========================================
    function setupAutoDeleteOnBlur(textarea, category, pointId) {
        textarea.addEventListener('blur', function () {
            const trimmedValue = this.value.trim();

            // Jika kosong, hapus otomatis
            if (trimmedValue === '') {
                console.log(`🗑️ Auto-deleting empty note: ${category} - ${pointId}`);
                deletePoint(category, pointId);
            }
        });
    }

    // ========================================
    // DYNAMIC POINTS SYSTEM
    // ========================================

    // Initialize add point buttons
    document.querySelectorAll('.add-point-btn-mini').forEach(btn => {
        btn.addEventListener('click', function () {
            const category = this.dataset.category;
            addPoint(category);
        });
    });

    function addPoint(category, text = '') {
        const pointId = ++pointIdCounter;
        const container = document.querySelector(`.dynamic-points-container[data-category="${category}"]`);
        
        // Check if container exists (notes panel DOM must be ready)
        if (!container) {
            console.error(`❌ Container not found for category: ${category}. Notes panel may not be loaded.`);
            // Still add to state so it can be saved/loaded later
            if (!categoryPoints[category]) {
                categoryPoints[category] = [];
            }
            categoryPoints[category].push({ id: pointId, text: text });
            updateCounter(category);
            return;
        }
        
        const emptyState = container.querySelector('.empty-state-mini');

        if (emptyState) {
            emptyState.style.display = 'none';
        }

        // Ensure categoryPoints[category] exists
        if (!categoryPoints[category]) {
            categoryPoints[category] = [];
        }
        
        const currentPoints = categoryPoints[category];
        const pointNumber = currentPoints.length + 1;

        const pointItem = document.createElement('div');
        pointItem.className = 'point-item';
        pointItem.dataset.pointId = pointId;

        pointItem.innerHTML = `
            <div class="point-number">${pointNumber}</div>
            <div class="point-input-wrapper">
                <textarea 
                    class="point-input" 
                    placeholder="Contoh: Revisi tanggal BA di dokumen P8"
                    rows="1"
                    data-point-id="${pointId}"
                    data-category="${category}"
                >${text}</textarea>
                <button class="delete-point-btn" data-point-id="${pointId}" data-category="${category}">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                </button>
            </div>
        `;

        container.appendChild(pointItem);

        // Add to state
        categoryPoints[category].push({ id: pointId, text: text });

        const textarea = pointItem.querySelector('.point-input');

        // Input event for typing
        textarea.addEventListener('input', function () {
            autoResizeTextarea(this);
            updatePointText(category, pointId, this.value);
            triggerAutoSave();
        });

        // Handle paste event
        textarea.addEventListener('paste', function () {
            setTimeout(() => {
                autoResizeTextarea(this);
            }, 0);
        });

        // ✅ Setup auto-delete on blur
        setupAutoDeleteOnBlur(textarea, category, pointId);

        // Delete button
        const deleteBtn = pointItem.querySelector('.delete-point-btn');
        deleteBtn.addEventListener('click', () => {
            deletePoint(category, pointId);
        });

        textarea.focus();
        autoResizeTextarea(textarea);

        updateCounter(category);
    }

    function updatePointText(category, pointId, text) {
        const point = categoryPoints[category].find(p => p.id === pointId);
        if (point) {
            point.text = text;
        }
    }

    function deletePoint(category, pointId) {
        const pointElement = document.querySelector(`.point-item[data-point-id="${pointId}"]`);

        if (!pointElement) return;

        pointElement.style.animation = 'slideOut 0.3s ease';

        setTimeout(() => {
            pointElement.remove();

            // Remove from state
            const index = categoryPoints[category].findIndex(p => p.id === pointId);
            if (index > -1) {
                categoryPoints[category].splice(index, 1);
            }

            // Renumber remaining points
            const container = document.querySelector(`.dynamic-points-container[data-category="${category}"]`);
            const remainingPoints = container.querySelectorAll('.point-item');
            remainingPoints.forEach((item, idx) => {
                const numberDiv = item.querySelector('.point-number');
                numberDiv.textContent = idx + 1;
            });

            // Show empty state if no points
            if (categoryPoints[category].length === 0) {
                const emptyState = container.querySelector('.empty-state-mini');
                if (emptyState) {
                    emptyState.style.display = 'block';
                }
            }

            updateCounter(category);
            triggerAutoSave();
        }, 300);
    }

    function updateCounter(category) {
        const counter = document.querySelector(`.note-counter[data-category="${category}"]`);
        if (counter) {
            counter.textContent = categoryPoints[category].length;
        }
    }

    // ========================================
    // LOAD NOTES FROM SERVER
    // ========================================

    function loadNotes() {
        if (!ticketNumber) {
            console.warn('Ticket number not found');
            return;
        }

        fetch(`/projess/api/tickets/${ticketNumber}/notes`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.notes) {
                    Object.keys(data.notes).forEach(category => {
                        const serverValue = (data.notes[category] || '').trim();

                        if (serverValue) {
                            // Clear existing points in this category
                            categoryPoints[category] = [];
                            const container = document.querySelector(`.dynamic-points-container[data-category="${category}"]`);
                            container.querySelectorAll('.point-item').forEach(item => item.remove());

                            // Parse by semicolon or newline
                            const points = serverValue.split(/[;\n]/)
                                .map(p => p.trim())
                                .filter(p => p.length > 0);

                            // Add each point
                            points.forEach(text => {
                                addPoint(category, text);
                            });
                        }
                    });

                    // Update last saved state
                    lastSavedState = JSON.stringify(categoryPoints);
                }
            })
            .catch(error => {
                console.error('Error loading notes:', error);
            });
    }

    // ========================================
    // AUTO-SAVE SYSTEM
    // ========================================

    function triggerAutoSave() {
        if (autoSaveTimeout) {
            clearTimeout(autoSaveTimeout);
        }

        updateSaveStatus('typing');

        autoSaveTimeout = setTimeout(() => {
            const currentState = JSON.stringify(categoryPoints);

            if (currentState !== lastSavedState && !isSaving) {
                saveNotes(false);
            }
        }, 2000);
    }

    function updateSaveStatus(status) {
        const statusIndicator = document.getElementById('saveStatusIndicator');
        if (!statusIndicator) return;

        switch (status) {
            case 'typing':
                statusIndicator.innerHTML = `
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" stroke="#9ca3af"></circle>
                    </svg>
                    <span style="color: #9ca3af;">Mengetik...</span>
                `;
                break;
            case 'saving':
                statusIndicator.innerHTML = `
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spin-animation">
                        <circle cx="12" cy="12" r="10" stroke="#3b82f6"></circle>
                    </svg>
                    <span style="color: #3b82f6;">Menyimpan...</span>
                `;
                break;
            case 'saved':
                statusIndicator.innerHTML = `
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12" stroke="#10b981"></polyline>
                    </svg>
                    <span style="color: #10b981;">Tersimpan</span>
                `;
                setTimeout(() => {
                    statusIndicator.innerHTML = `
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" stroke="#d1d5db"></circle>
                        </svg>
                        <span style="color: #6b7280;">Auto-save aktif</span>
                    `;
                }, 3000);
                break;
            case 'error':
                statusIndicator.innerHTML = `
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" stroke="#ef4444"></circle>
                        <line x1="12" y1="8" x2="12" y2="12" stroke="#ef4444"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16" stroke="#ef4444"></line>
                    </svg>
                    <span style="color: #ef4444;">Gagal menyimpan</span>
                `;
                break;
        }
    }

    // ========================================
    // SAVE NOTES
    // ========================================

    function saveNotes(isManual = false) {
        if (!ticketNumber) {
            if (isManual) alert('Ticket number not found');
            return;
        }

        if (isSaving) {
            console.log('⏳ Save already in progress, skipping...');
            return;
        }

        // Convert points to semicolon-separated string
        const notes = {};
        Object.keys(categoryPoints).forEach(category => {
            const validPoints = categoryPoints[category]
                .map(p => p.text.trim())
                .filter(text => text.length > 0);

            notes[category] = validPoints.join('; ');
        });

        isSaving = true;

        if (isManual) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span>Menyimpan...</span>';
        } else {
            updateSaveStatus('saving');
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        console.log('📤 Sending save request:', { ticketNumber, notes });
        
        fetch(`/projess/api/tickets/${ticketNumber}/notes`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ notes })
        })
            .then(response => {
                console.log('📥 Save response status:', response.status);
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('❌ Save failed. Response:', text);
                        throw new Error(`Failed to save notes: ${response.status} ${text}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                console.log('✅ Notes saved successfully:', data);
                lastSavedState = JSON.stringify(categoryPoints);

                if (isManual) {
                    saveBtn.innerHTML = `
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Tersimpan!
                    `;

                    setTimeout(() => {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = `
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Simpan Catatan
                        `;
                    }, 2000);
                } else {
                    updateSaveStatus('saved');
                }
            })
            .catch(error => {
                console.error('❌ Error saving notes:', error);

                if (isManual) {
                    alert('Gagal menyimpan catatan');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = `
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        Simpan Catatan
                    `;
                } else {
                    updateSaveStatus('error');
                }
            })
            .finally(() => {
                isSaving = false;
            });
    }

    // ========================================
    // COPY ALL NOTES
    // ========================================

    function copyAllNotes() {
        const ticketNumber = document.querySelector('[data-ticket-number]')?.dataset.ticketNumber || 'N/A';
        const projectTitle = document.querySelector('[data-project-title]')?.dataset.projectTitle || 'N/A';
        const companyName = document.querySelector('[data-company-name]')?.dataset.companyName || 'N/A';
        const contractValue = document.querySelector('[data-contract-value]')?.dataset.contractValue || 'N/A';

        const categoryNames = {
            'mitra': '**Revisi Mitra**',
            'obl': '**Revisi OBL**',
            'internal_telkom': '**Revisi Internal Telkom**',
            'segmen_witel': '**Revisi Segmen**',
            'revisi_precise': '**Revisi Precise**'
        };

        const notesContent = [];

        Object.keys(categoryPoints).forEach(category => {
            const validPoints = categoryPoints[category]
                .map(p => p.text.trim())
                .filter(text => text.length > 0);

            if (validPoints.length > 0) {
                const categoryName = categoryNames[category] || category;
                const points = validPoints
                    .map((text, index) => `${index + 1}. ${text}`)
                    .join('\n');

                notesContent.push(`${categoryName}\n${points}`);
            }
        });

        if (notesContent.length === 0) {
            showCopyFeedback(copyBtn, 'Tidak ada catatan untuk disalin', false);
            return;
        }

        const formattedOutput = `**${ticketNumber}** 
**${projectTitle}** 
**${companyName}** 
**Nilai Kontrak:** ${contractValue} (Exc PPN) 

${notesContent.join('\n\n')}`;

        navigator.clipboard.writeText(formattedOutput)
            .then(() => {
                showCopyFeedback(copyBtn, 'Semua catatan tersalin!', true);
            })
            .catch(err => {
                console.error('Failed to copy:', err);
                showCopyFeedback(copyBtn, 'Gagal menyalin', false);
            });
    }

    function showCopyFeedback(button, message, success) {
        const originalHTML = button.innerHTML;

        button.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                ${success
                ? '<polyline points="20 6 9 17 4 12"></polyline>'
                : '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>'
            }
            </svg>
            ${message}
        `;

        button.disabled = true;

        setTimeout(() => {
            button.innerHTML = originalHTML;
            button.disabled = false;
        }, 2000);
    }

    // ========================================
    // ✅ CENTRALIZED DOCUMENT CATEGORY MAPPING
    // ========================================

    function getDocumentCategory(docType) {
        const categoryMap = {
            'PR': 'internal_telkom',
            'PO': 'internal_telkom',
            'GR': 'internal_telkom',
            'NPK': 'internal_telkom',
            'KB': 'segmen_witel',
            'BASO': 'segmen_witel',
            'BA SPLITTING': 'segmen_witel',
            'BA SPLIT': 'segmen_witel',
            'CHECKLIST OBL': 'mitra',
            'CL OBL': 'mitra',
            'KKP': 'mitra',
            'SPB': 'mitra',
            'INVOICE': 'mitra',
            'KUITANSI': 'mitra',
            'FP': 'mitra',
            'FAKTUR PAJAK': 'mitra',
            'ENOFA': 'mitra',
            'BEBAS PPH': 'mitra',
            'BAPLA': 'mitra',
            'BAPL': 'mitra',
            'BAST': 'mitra',
            'BAUT': 'mitra',
            'BARD': 'mitra',
            'BA REKON': 'mitra',
            'LPL': 'mitra',
            'WO': 'obl',
            'P8': 'obl',
            'SP': 'obl',
            'KL': 'obl',
            'PKS': 'obl',
            'NOPES': 'obl',
            'P7': 'obl',
            'SURAT KESANGGUPAN': 'obl',
            'BAKN': 'obl',
            'SK': 'obl'
        };

        return categoryMap[docType.toUpperCase().trim()] || 'revisi_precise';
    }

    // ========================================
    // ✅ PUBLIC API - EXPOSED TO WINDOW
    // ========================================

    window.addIssueToNotes = function (issue, location) {
        console.log('📝 addIssueToNotes called', { issue, location });
        
        const category = getDocumentCategory(location.docType);
        const noteText = generateNoteText(issue, location);
        
        console.log('📋 Category determined:', category, 'for docType:', location.docType);
        console.log('📄 Note text:', noteText);

        // Ensure categoryPoints[category] exists
        if (!categoryPoints[category]) {
            categoryPoints[category] = [];
        }

        addPoint(category, noteText);
        showToast('✓ Ditambahkan ke notes', 'success');
        highlightFAB();
        
        // ✅ Save immediately (with small delay to ensure state is updated)
        // Use setTimeout to ensure the state update from addPoint is complete
        setTimeout(() => {
            console.log('💾 Attempting to save notes to server...');
            console.log('📊 Current state:', JSON.stringify(categoryPoints));
            console.log('🎫 Ticket number:', ticketNumber);
            
            if (!ticketNumber) {
                console.error('❌ Ticket number is missing! Cannot save notes.');
                return;
            }
            
            saveNotes(false);
        }, 100);
    };

    window.addAdvanceIssueToNotes = function (issue, stageName) {
        console.log('📝 addAdvanceIssueToNotes called', { issue, stageName });
        
        const urlParts = window.location.pathname.split('/').filter(part => part.length > 0);
        const advanceIndex = urlParts.indexOf('advance-result');

        if (advanceIndex === -1) {
            console.error('❌ Not in advance-result page! URL:', window.location.pathname);
            return;
        }

        // Route structure: advance-result/{ticketNumber}/{docType}
        // So docType is at advanceIndex + 2 (skip 'advance-result' and ticketNumber)
        const docType = urlParts[advanceIndex + 2];
        
        if (!docType) {
            console.error('❌ DocType not found in URL! URL parts:', urlParts, 'Expected at index:', advanceIndex + 2);
            return;
        }

        const category = getDocumentCategory(docType);
        console.log('📋 Category determined:', category, 'for docType:', docType);
        
        // Use same field priority as advance-review-handler.js
        const description = issue.keterangan || issue.Description || issue.description || issue.notes || issue.label || 'Issue tidak teridentifikasi';
        const noteText = `${stageName}: ${description}`;
        console.log('📄 Note text:', noteText);

        // Ensure categoryPoints[category] exists
        if (!categoryPoints[category]) {
            categoryPoints[category] = [];
        }

        addPoint(category, noteText);
        console.log('✅ Point added to state. Category points:', categoryPoints[category]);
        
        showToast('✓ Ditambahkan ke notes', 'success');
        highlightFAB();
        
        // ✅ Save immediately (with small delay to ensure state is updated)
        // Use setTimeout to ensure the state update from addPoint is complete
        setTimeout(() => {
            console.log('💾 Attempting to save notes to server...');
            console.log('📊 Current state:', JSON.stringify(categoryPoints));
            console.log('🎫 Ticket number:', ticketNumber);
            
            if (!ticketNumber) {
                console.error('❌ Ticket number is missing! Cannot save notes.');
                return;
            }
            
            saveNotes(false);
        }, 100);
    };

    window.addMissingDocsToNotes = function (missingDocs) {
        console.log('📋 Adding missing docs from overview:', missingDocs);

        if (!missingDocs || missingDocs.length === 0) {
            alert('Tidak ada dokumen yang perlu dilengkapi');
            return;
        }

        const grouped = {
            internal_telkom: [],
            segmen_witel: [],
            mitra: [],
            obl: [],
            revisi_precise: []
        };

        missingDocs.forEach(doc => {
            const category = getDocumentCategory(doc);
            grouped[category].push(doc);
        });

        console.log('📊 Grouped by category:', grouped);

        let addedCount = 0;

        Object.keys(grouped).forEach(category => {
            const docs = grouped[category];

            if (docs.length > 0) {
                const noteText = `Mohon untuk melampirkan dokumen ${docs.join(', ')}`;
                console.log(`✅ Adding to ${category}:`, noteText);

                addPoint(category, noteText);
                addedCount++;
            }
        });

        if (addedCount > 0) {
            showToast(`✓ Dokumen belum lengkap ditambahkan ke ${addedCount} kategori`, 'success');
            highlightFAB();
            triggerAutoSave();
        } else {
            console.error('❌ No docs were added');
            alert('Gagal menambahkan ke notes');
        }
    };

    // ========================================
    // HELPER FUNCTIONS
    // ========================================

    function generateNoteText(issue, location) {
        const { text, correction } = issue;
        const { docType, pageInDoc } = location;

        const typoMatch = text.match(/^(.+?)\s*→\s*(.+)$/);

        if (typoMatch) {
            const typoWord = typoMatch[1].trim();
            const correctionWord = typoMatch[2].trim();
            return `Terdapat typo '${typoWord}' di ${docType} halaman ${pageInDoc}, seharusnya '${correctionWord}'`;
        }

        if (correction) {
            return `Terdapat inkonsistensi '${text}' di ${docType} halaman ${pageInDoc}, seharusnya '${correction}'`;
        }

        return `Terdapat issue '${text}' di ${docType} halaman ${pageInDoc}`;
    }

    function showToast(message, type = 'success') {
        const existingToast = document.querySelector('.issue-toast');
        if (existingToast) {
            existingToast.remove();
        }

        const toast = document.createElement('div');
        toast.className = `issue-toast issue-toast-${type}`;
        toast.textContent = message;

        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 10);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }

    function highlightFAB() {
        fabBtn.classList.add('fab-highlight');
        setTimeout(() => fabBtn.classList.remove('fab-highlight'), 1000);
    }

    window.showToast = showToast;
    window.getDocumentCategory = getDocumentCategory;
});