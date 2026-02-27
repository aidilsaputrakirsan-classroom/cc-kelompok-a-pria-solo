let searchTimeout;
let searchInput;
let ticketsContainer;
let loadingIndicator;
let searchUrl;

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    searchInput = document.getElementById('searchInput');
    ticketsContainer = document.getElementById('ticketsContainer');
    loadingIndicator = document.getElementById('loadingIndicator');

    // Only initialize if elements exist
    if (searchInput && ticketsContainer && loadingIndicator) {
        searchUrl = searchInput.dataset.searchUrl;
        initializeSearch();
        initializeDeleteHandlers();
    }
});

function initializeSearch() {
searchInput.addEventListener('input', function (e) {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        performSearch(e.target.value);
    }, 500);
});
};

function performSearch(query) {
    loadingIndicator.style.display = 'block';
    ticketsContainer.style.opacity = '0.5';

    fetch(`${searchUrl}?search=${encodeURIComponent(query)}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            updateCards(data.tickets);
            loadingIndicator.style.display = 'none';
            ticketsContainer.style.opacity = '1';
        })
        .catch(error => {
            console.error('Error:', error);
            loadingIndicator.style.display = 'none';
            ticketsContainer.style.opacity = '1';
        });
}

function updateCards(tickets) {
    if (tickets.length === 0) {
        ticketsContainer.innerHTML = `
            <div class="col-12 text-center py-5">
                <h5 class="text-muted mt-3">Tidak ada data ditemukan</h5>
                <p class="text-muted">Coba ubah kata kunci pencarian Anda</p>
            </div>
        `;
        return;
    }

    ticketsContainer.innerHTML = tickets.map(ticket => {
        const isValidation = ticket.stage === 'Validation';

        // URL detail berdasarkan stage
        const detailUrl = isValidation
            ? `/validate-ground-truth/${ticket.ticket_number}`
            : `/tickets/${ticket.ticket_number}/advance-reviews`;

        // Button text berdasarkan stage
        const buttonText = isValidation ? 'Validation' : 'Review';

        // Stats values
        const totalNotes = ticket.total_notes !== null && ticket.total_notes !== undefined ? ticket.total_notes : 'N/A';
        const totalError = ticket.total_errors !== null && ticket.total_errors !== undefined ? ticket.total_errors : 'N/A';
        const totalFiles = ticket.total_files || 'N/A';

        const logoUrl = ticket.logo_url || `/images/LOGO MITRA/${ticket.company_name.trim()}.jpg`;

        return `
            <div class="col-sm-6 col-lg-4 mb-4 ticket-card" 
                 data-ticket-id="${ticket.id}"
                 data-stage="${ticket.stage}">
                <div class="card h-100 shadow-sm border-0 rounded-4">
                    <div class="card-body p-4 d-flex flex-column">
                        <!-- Header with Logo and Info -->
                        <div class="d-flex align-items-center mb-3">
                            <div class="profile-image-container me-3 flex-shrink-0">
                                <img 
                                    src="${logoUrl}" 
                                    alt="Logo Mitra"
                                    class="rounded-3"
                                    style="width: 56px; height: 56px; object-fit: contain; background: #f8f9fa; padding: 8px;"
                                    onerror="this.onerror=null; this.src='/images/default-logo.png';"
                                >
                            </div>
                            <div class="flex-grow-1 overflow-hidden">
                                <h5 class="card-title mb-1 fw-bold text-truncate" style="color: #212529; font-size: 1.1rem;">
                                    ${ticket.ticket_number}
                                </h5>
                                <p class="text-muted small mb-0 text-truncate" style="font-size: 0.85rem;">
                                    ${ticket.company_name}
                                </p>
                            </div>
                        </div>

                        <!-- Description -->
                        <p class="card-text text-muted mb-3" 
                           style="font-size: 0.9rem; line-height: 1.5; height: 3em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                            ${ticket.project_title || 'Tidak ada judul project'}
                        </p>

                        <!-- Stats Section with Background Container -->
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="stat-item p-2 rounded-3" style="background-color: #ffffff;">
                                    <p class="text-muted small mb-1" style="font-size: 0.7rem; font-weight: 500;">
                                        Total Notes
                                    </p>
                                    <h5 class="fw-bold mb-0" style="color: #212529; font-size: 1.35rem;">
                                        ${totalNotes}
                                    </h5>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item p-2 rounded-3" style="background-color: #ffffff;">
                                    <p class="text-muted small mb-1" style="font-size: 0.7rem; font-weight: 500;">
                                        Total Error
                                    </p>
                                    <h5 class="fw-bold mb-0" style="color: #212529; font-size: 1.35rem;">
                                        ${totalError}
                                    </h5>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-item p-2 rounded-3" style="background-color: #ffffff;">
                                    <p class="text-muted small mb-1" style="font-size: 0.7rem; font-weight: 500;">
                                        Total File
                                    </p>
                                    <h5 class="fw-bold mb-0" style="color: #212529; font-size: 1.35rem;">
                                        ${totalFiles}
                                    </h5>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row mt-3 g-2">
                            <div class="col-6">
                                <a href="${detailUrl}"
                                    class="btn btn-dark w-100 rounded-pill fw-semibold d-flex align-items-center justify-content-center"
                                    style="padding: 10px 16px; font-size: 0.85rem; height: 44px;">
                                    ${buttonText}
                                </a>
                            </div>
                            <div class="col-6">
                                <button 
                                    class="btn btn-outline-danger w-100 rounded-pill fw-semibold btn-hapus d-flex align-items-center justify-content-center"
                                    style="padding: 10px 16px; font-size: 0.85rem; height: 44px;"
                                    data-ticket-id="${ticket.id}"
                                    data-stage="${ticket.stage}">
                                    Hapus Tiket
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function initializeDeleteHandlers() {
    if (!ticketsContainer) {
        console.error('ticketsContainer not found');
        return;
    }

    ticketsContainer.addEventListener('click', function (e) {
        const deleteButton = e.target.closest('.btn-hapus');

        if (deleteButton) {
            e.preventDefault();
            e.stopPropagation();

            const ticketId = deleteButton.getAttribute('data-ticket-id');
            const stage = deleteButton.getAttribute('data-stage');
            const ticketCard = deleteButton.closest('.ticket-card');

            console.log('Delete button clicked', { ticketId, stage });

            if (ticketId && ticketCard) {
                showConfirmModal(ticketId, stage, ticketCard);
            } else {
                console.error('Ticket ID or Card not found');
            }
        }
    });
}

function showConfirmModal(ticketId, stage, ticketCard) {
    const modalTitle = stage === 'Validation' ? 'Hapus Tiket Validation?' : 'Hapus Tiket Review?';
    const modalMessage = stage === 'Validation'
        ? 'Apakah Anda yakin ingin menghapus tiket validation ini? Semua data ground truth yang belum direview akan terhapus.'
        : 'Apakah Anda yakin ingin menghapus tiket review ini? Semua data ground truth dan hasil review akan terhapus.';

    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'custom-modal-overlay';
    modalOverlay.innerHTML = `
        <div class="custom-modal-card">
            <div class="modal-icon-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
            </div>
            <h4 class="modal-title">${modalTitle}</h4>
            <p class="modal-message">${modalMessage}</p>
            <div class="modal-buttons">
                <button type="button" class="modal-btn modal-btn-cancel">Batal</button>
                <button type="button" class="modal-btn modal-btn-confirm">Ya, Hapus</button>
            </div>
        </div>
    `;

    addModalStyles();
    document.body.appendChild(modalOverlay);

    setTimeout(() => {
        modalOverlay.classList.add('show');
    }, 10);

    const btnCancel = modalOverlay.querySelector('.modal-btn-cancel');
    const btnConfirm = modalOverlay.querySelector('.modal-btn-confirm');

    btnCancel.addEventListener('click', () => {
        closeModal(modalOverlay);
    });

    btnConfirm.addEventListener('click', () => {
        closeModal(modalOverlay);
        deleteTicket(ticketId, stage, ticketCard);
    });

    modalOverlay.addEventListener('click', (e) => {
        if (e.target === modalOverlay) {
            closeModal(modalOverlay);
        }
    });
}

function showNotificationModal(type, message) {
    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'custom-modal-overlay';

    const icon = type === 'success'
        ? '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>'
        : '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>';

    modalOverlay.innerHTML = `
        <div class="custom-modal-card">
            <div class="modal-icon-wrapper ${type === 'success' ? 'success' : 'error'}">
                ${icon}
            </div>
            <h4 class="modal-title">${type === 'success' ? 'Berhasil!' : 'Terjadi Kesalahan'}</h4>
            <p class="modal-message">${message}</p>
            <div class="modal-buttons">
                <button type="button" class="modal-btn modal-btn-confirm single">OK</button>
            </div>
        </div>
    `;

    document.body.appendChild(modalOverlay);

    setTimeout(() => {
        modalOverlay.classList.add('show');
    }, 10);

    const btnOk = modalOverlay.querySelector('.modal-btn-confirm');
    btnOk.addEventListener('click', () => {
        closeModal(modalOverlay);
    });

    if (type === 'success') {
        setTimeout(() => {
            if (document.body.contains(modalOverlay)) {
                closeModal(modalOverlay);
            }
        }, 3000);
    }
}

function closeModal(modalElement) {
    modalElement.classList.remove('show');
    setTimeout(() => {
        modalElement.remove();
    }, 300);
}

function addModalStyles() {
    if (document.getElementById('customModalStyles')) return;

    const styles = document.createElement('style');
    styles.id = 'customModalStyles';
    styles.textContent = `
        .custom-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .custom-modal-overlay.show {
            opacity: 1;
        }
        .custom-modal-card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }
        .custom-modal-overlay.show .custom-modal-card {
            transform: scale(1);
        }
        .modal-icon-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: #6c757d;
        }
        .modal-icon-wrapper.success {
            background-color: #d4edda;
            color: #28a745;
        }
        .modal-icon-wrapper.error {
            background-color: #f8d7da;
            color: #dc3545;
        }
        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #212529;
            margin-bottom: 12px;
        }
        .modal-message {
            font-size: 0.95rem;
            color: #6c757d;
            line-height: 1.5;
            margin-bottom: 24px;
        }
        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        .modal-btn {
            flex: 1;
            padding: 12px 24px;
            border-radius: 50px;
            border: none;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .modal-btn.single {
            flex: 0 1 auto;
            min-width: 120px;
        }
        .modal-btn-cancel {
            background-color: #f8f9fa;
            color: #212529;
        }
        .modal-btn-cancel:hover {
            background-color: #e9ecef;
            transform: translateY(-1px);
        }
        .modal-btn-confirm {
            background-color: #212529;
            color: white;
        }
        .modal-btn-confirm:hover {
            background-color: #000000;
            transform: translateY(-1px);
        }
    `;
    document.head.appendChild(styles);
}

function deleteTicket(ticketId, stage, ticketCard) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]');

    if (!csrfToken) {
        console.error('CSRF token not found');
        showNotificationModal('error', 'CSRF token tidak ditemukan. Refresh halaman dan coba lagi.');
        return;
    }

    // Endpoint berdasarkan stage
    const endpoint = stage === 'Validation'
        ? `/projess/riwayat-review/${ticketId}`
        : `/projess/riwayat-review/review/${ticketId}`;

    ticketCard.style.opacity = '0.5';
    ticketCard.style.pointerEvents = 'none';

    fetch(endpoint, {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken.getAttribute('content')
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                ticketCard.style.transition = 'opacity 0.3s ease';
                ticketCard.style.opacity = '0';

                setTimeout(() => {
                    ticketCard.remove();

                    const remainingCards = document.querySelectorAll('.ticket-card');
                    if (remainingCards.length === 0) {
                        ticketsContainer.innerHTML = `
                            <div class="col-12 text-center py-5">
                                <h5 class="text-muted mt-3">Tidak ada data ditemukan</h5>
                                <p class="text-muted">Semua tiket telah dihapus</p>
                            </div>
                        `;
                    }

                    showNotificationModal('success', data.message || 'Tiket berhasil dihapus');
                }, 300);
            } else {
                ticketCard.style.opacity = '1';
                ticketCard.style.pointerEvents = 'auto';
                showNotificationModal('error', data.message || 'Gagal menghapus tiket');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            ticketCard.style.opacity = '1';
            ticketCard.style.pointerEvents = 'auto';
            showNotificationModal('error', 'Gagal menghapus tiket. Silakan coba lagi.');
        });
}