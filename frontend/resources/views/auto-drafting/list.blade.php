<style>
    :root {
        --primary-color: #1d1d1f;
        --primary-hover: #2d2d2f;
        --border-color: #e5e7eb;
        --text-primary: #111827;
        --text-secondary: #6b7280;
        --bg-light: #f6f6f6;
        --bg-white: #ffffff;
        --danger-color: #dc2626;
        --danger-hover: #b91c1c;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .draft-management-wrapper {
        background: var(--bg-light);
        min-height: 100vh;
        border-radius: 35px;
        padding: 24px;
    }

    /* ============================================
       PAGE HEADER
    ============================================ */
    .draft-page-header {
        background: #f5f5f7;
        border-radius: 24px;
        padding: 32px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .draft-page-header:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    }

    .draft-header-content h1 {
        color: #1d1d1f;
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 8px 0;
        line-height: 1.3;
    }

    .draft-header-content p {
        color: #6e6e73;
        font-size: 15px;
        margin: 0;
        line-height: 1.6;
    }

    .btn-new-draft {
        background: #1d1d1f;
        color: white;
        border: none;
        border-radius: 24px;
        padding: 16px 32px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
    }

    .btn-new-draft:hover {
        background: #2d2d2f;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        color: white;
        text-decoration: none;
    }

    .btn-new-draft:active {
        transform: translateY(0);
    }

    /* ============================================
       MAIN CARD
    ============================================ */
    .draft-main-card {
        background: var(--bg-white);
        border-radius: 24px;
        box-shadow: var(--shadow-md);
        overflow: hidden;
    }

    .draft-card-header {
        padding: 24px 32px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--bg-white);
    }

    .draft-header-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .draft-header-icon {
        width: 40px;
        height: 40px;
        background: #1d1d1f;
        color: white;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .draft-header-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }

    .draft-count-badge {
        background: rgba(29, 29, 31, 0.06);
        color: #1d1d1f;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
    }

    /* ============================================
       TABLE
    ============================================ */
    .draft-card-body {
        padding: 0;
    }

    .draft-table-wrapper {
        overflow-x: auto;
    }

    .draft-table {
        width: 100%;
        border-collapse: collapse;
    }

    .draft-table thead {
        background: #f8fafc;
        border-bottom: 2px solid var(--border-color);
    }

    .draft-table th {
        color: var(--text-secondary);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 16px 32px;
        text-align: left;
    }

    .draft-table td {
        padding: 20px 32px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 14px;
        vertical-align: middle;
    }

    .draft-table tbody tr {
        transition: all 0.2s;
    }

    .draft-table tbody tr:hover {
        background-color: #f8fafc;
    }

    /* ============================================
       TABLE CONTENT STYLING
    ============================================ */
    .draft-id-badge {
        background: #1d1d1f;
        color: white;
        padding: 6px 14px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 13px;
        display: inline-block;
    }

    .draft-project-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .draft-project-title {
        font-weight: 600;
        color: var(--text-primary);
        font-size: 14px;
    }

    .draft-customer-name {
        color: var(--text-secondary);
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .draft-timestamp {
        color: var(--text-secondary);
        font-size: 13px;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .draft-obl-badge {
        background: rgba(29, 29, 31, 0.06);
        color: #1d1d1f;
        padding: 6px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 700;
        display: inline-block;
    }

    /* ============================================
       ACTION BUTTONS
    ============================================ */
    .draft-actions {
        display: flex;
        gap: 8px;
    }

    .draft-btn {
        padding: 8px 16px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        white-space: nowrap;
    }

    .draft-btn-edit {
        background: #1d1d1f;
        color: white;
    }

    .draft-btn-edit:hover {
        background: #2d2d2f;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(29, 29, 31, 0.3);
        text-decoration: none;
    }

    .draft-btn-delete {
        background: #fee2e2;
        color: var(--danger-color);
    }

    .draft-btn-delete:hover {
        background: var(--danger-color);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
    }

    .draft-btn:active {
        transform: translateY(0);
    }

    /* ============================================
       EMPTY STATE
    ============================================ */
    .draft-empty-state {
        padding: 80px 40px;
        text-align: center;
    }

    .draft-empty-icon {
        width: 80px;
        height: 80px;
        background: rgba(29, 29, 31, 0.06);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
    }

    .draft-empty-icon i {
        font-size: 32px;
        color: var(--text-secondary);
    }

    .draft-empty-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0 0 8px 0;
    }

    .draft-empty-text {
        font-size: 15px;
        color: var(--text-secondary);
        margin: 0 0 24px 0;
        line-height: 1.6;
    }

    .draft-btn-create-first {
        background: #1d1d1f;
        color: white;
        border: none;
        border-radius: 24px;
        padding: 16px 32px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .draft-btn-create-first:hover {
        background: #2d2d2f;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        text-decoration: none;
    }

    .draft-btn-create-first:active {
        transform: translateY(0);
    }

    /* ============================================
       SCROLLBAR
    ============================================ */
    .draft-table-wrapper::-webkit-scrollbar {
        height: 8px;
    }

    .draft-table-wrapper::-webkit-scrollbar-track {
        background: var(--bg-light);
        border-radius: 4px;
    }

    .draft-table-wrapper::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }

    .draft-table-wrapper::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* ============================================
       SEARCH BAR - ENHANCED UI/UX
    ============================================ */
    .draft-search-container {
        padding: 20px 32px;
        background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        gap: 16px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    .draft-search-wrapper {
        flex: 1;
        min-width: 280px;
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .draft-search-label {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: var(--text-secondary);
    }

    .draft-search-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
    }

    .draft-search-input-wrapper.active {
        transform: scale(1.02);
    }

    .draft-search-icon {
        position: absolute;
        left: 14px;
        color: var(--text-secondary);
        font-size: 16px;
        pointer-events: none;
        transition: color 0.2s ease;
    }

    .draft-search-input-wrapper.active .draft-search-icon {
        color: #1d1d1f;
    }

    .draft-search-input {
        width: 100%;
        padding: 12px 14px 12px 44px;
        border: 1.5px solid #e5e7eb;
        border-radius: 12px;
        font-size: 15px;
        color: var(--text-primary);
        transition: all 0.3s ease;
        background: white;
        font-weight: 500;
    }

    .draft-search-input::placeholder {
        color: #9ca3af;
        font-weight: 400;
    }

    .draft-search-input:focus {
        outline: none;
        border-color: #1d1d1f;
        box-shadow: 0 0 0 4px rgba(29, 29, 31, 0.08), inset 0 0 0 0.5px rgba(29, 29, 31, 0.05);
        background: white;
    }

    .draft-search-input:not(:placeholder-shown) {
        border-color: #cbd5e1;
    }

    .draft-search-actions {
        position: absolute;
        right: 12px;
        display: flex;
        gap: 6px;
        align-items: center;
    }

    .draft-search-indicator {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
        font-size: 14px;
        opacity: 0;
        transition: all 0.2s ease;
        pointer-events: none;
    }

    .draft-search-input:not(:placeholder-shown) ~ .draft-search-actions .draft-search-indicator {
        opacity: 1;
    }

    .draft-search-clear {
        background: transparent;
        border: none;
        color: var(--text-secondary);
        cursor: pointer;
        font-size: 16px;
        padding: 6px 8px;
        display: none;
        transition: all 0.2s ease;
        border-radius: 6px;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .draft-search-clear:hover {
        background: rgba(29, 29, 31, 0.08);
        color: var(--text-primary);
    }

    .draft-search-clear:active {
        background: rgba(29, 29, 31, 0.15);
    }

    .draft-search-input:not(:placeholder-shown) ~ .draft-search-actions .draft-search-clear {
        display: flex;
    }

    .draft-search-info {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 16px;
        background: white;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        font-size: 13px;
        color: var(--text-secondary);
        min-width: 180px;
        justify-content: center;
    }

    .draft-search-info.show {
        animation: slideInRight 0.3s ease;
    }

    .draft-search-info-badge {
        background: #1d1d1f;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 700;
        font-size: 12px;
    }

    .draft-search-info.no-results {
        background: #fef2f2;
        border-color: #fecaca;
        color: var(--danger-color);
    }

    .draft-search-info.no-results .draft-search-info-badge {
        background: var(--danger-color);
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(10px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .draft-search-hint {
        font-size: 11px;
        color: #9ca3af;
        display: flex;
        align-items: center;
        gap: 4px;
        transition: all 0.2s ease;
    }

    .draft-search-hint.active {
        color: #6b7280;
        font-weight: 500;
    }

    /* ============================================
       EMPTY STATE ENHANCEMENT
    ============================================ */
    .draft-empty-state {
        padding: 60px 40px;
        text-align: center;
    }

    .draft-empty-state.no-search-results {
        padding: 40px 20px;
    }

    .draft-empty-state.no-search-results .draft-empty-icon {
        width: 64px;
        height: 64px;
    }

    .draft-empty-state.no-search-results .draft-empty-title {
        font-size: 18px;
    }

    .draft-empty-state.no-search-results .draft-empty-text {
        font-size: 14px;
    }

    /* ============================================
       RESPONSIVE
    ============================================ */
    @media (max-width: 991px) {
        .draft-page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }

        .btn-new-draft {
            width: 100%;
            justify-content: center;
        }

        .draft-table th,
        .draft-table td {
            padding: 12px 16px;
        }
    }

    @media (max-width: 767px) {
        .draft-management-wrapper {
            padding: 16px;
            border-radius: 20px;
        }

        .draft-page-header {
            padding: 24px;
        }

        .draft-header-content h1 {
            font-size: 24px;
        }

        .draft-card-header {
            padding: 16px 20px;
            flex-direction: column;
            align-items: flex-start;
            gap: 12px;
        }

        .draft-search-container {
            padding: 16px 20px;
            flex-direction: column;
            gap: 12px;
        }

        .draft-search-wrapper {
            min-width: auto;
        }

        .draft-search-info {
            min-width: auto;
            width: 100%;
            justify-content: center;
        }

        .draft-table th,
        .draft-table td {
            padding: 12px 16px;
            font-size: 13px;
        }

        .draft-actions {
            flex-direction: column;
            width: 100%;
        }

        .draft-btn {
            width: 100%;
            justify-content: center;
        }

        .draft-empty-state {
            padding: 60px 20px;
        }

        .draft-empty-icon {
            width: 64px;
            height: 64px;
        }

        .draft-empty-icon i {
            font-size: 24px;
        }

        .draft-empty-title {
            font-size: 18px;
        }

        .draft-empty-text {
            font-size: 14px;
        }
    }

    /* ============================================
       MODAL STYLE
    ============================================ */
    .draft-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        animation: fadeIn 0.3s ease;
    }

    .draft-modal-overlay.show {
        display: flex;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .draft-modal {
        background: white;
        border-radius: 24px;
        width: 90%;
        max-width: 480px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        animation: slideUp 0.3s ease;
        overflow: hidden;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(50px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .draft-modal-header {
        padding: 24px 32px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .draft-modal-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-primary);
        margin: 0;
    }

    .draft-modal-close {
        background: transparent;
        border: none;
        color: var(--text-secondary);
        font-size: 24px;
        cursor: pointer;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        transition: all 0.2s;
    }

    .draft-modal-close:hover {
        background: var(--bg-light);
        color: var(--text-primary);
    }

    .draft-modal-body {
        padding: 32px;
    }

    .draft-form-group {
        margin-bottom: 24px;
    }

    .draft-form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: 8px;
    }

    .draft-form-input {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid var(--border-color);
        border-radius: 12px;
        font-size: 15px;
        color: var(--text-primary);
        transition: all 0.2s;
        background: var(--bg-white);
    }

    .draft-form-input:focus {
        outline: none;
        border-color: #1d1d1f;
        box-shadow: 0 0 0 3px rgba(29, 29, 31, 0.1);
    }

    .draft-form-input::placeholder {
        color: var(--text-secondary);
    }

    .draft-form-hint {
        font-size: 12px;
        color: var(--text-secondary);
        margin-top: 6px;
        display: block;
    }

    .draft-modal-footer {
        padding: 24px 32px;
        border-top: 1px solid var(--border-color);
        display: flex;
        gap: 12px;
        justify-content: flex-end;
        background: var(--bg-light);
    }

    .draft-modal-btn {
        padding: 12px 24px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .draft-modal-btn-cancel {
        background: white;
        color: var(--text-primary);
        border: 1px solid var(--border-color);
    }

    .draft-modal-btn-cancel:hover {
        background: var(--bg-light);
    }

    .draft-modal-btn-create {
        background: #1d1d1f;
        color: white;
    }

    .draft-modal-btn-create:hover {
        background: #2d2d2f;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(29, 29, 31, 0.3);
    }

    .draft-modal-btn-create:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    @media (max-width: 767px) {
        .draft-modal {
            width: 95%;
            max-width: none;
        }

        .draft-modal-header,
        .draft-modal-body,
        .draft-modal-footer {
            padding: 20px;
        }

        .draft-modal-footer {
            flex-direction: column-reverse;
        }

        .draft-modal-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<div class="draft-management-wrapper">
    <!-- Page Header -->
    <div class="draft-page-header">
        <div class="draft-header-content">
            <h1><i class="fa fa-file-text-o"></i> Draft Management</h1>
            <p>Kelola semua draft RSO yang tersimpan di database</p>
        </div>
        <button onclick="openCreateModal()" class="btn-new-draft">
            <i class="fa fa-plus-circle"></i> Buat Draft Baru
        </button>
    </div>

    <!-- Main Card -->
    <div class="draft-main-card">
        <!-- Card Header -->
        <div class="draft-card-header">
            <div class="draft-header-left">
                <div class="draft-header-icon">
                    <i class="fa fa-list"></i>
                </div>
                <h2 class="draft-header-title">Draft Tersimpan</h2>
            </div>
            <span class="draft-count-badge" id="draft-counter">0 Draft</span>
        </div>

        <!-- Search Bar -->
        <div class="draft-search-container">
            <div class="draft-search-wrapper">
                <label class="draft-search-label" for="draftSearchInput">
                    <i class="fa fa-search"></i> Cari Draft
                </label>
                <div class="draft-search-input-wrapper">
                    <i class="fa fa-search draft-search-icon"></i>
                    <input 
                        type="text" 
                        id="draftSearchInput" 
                        class="draft-search-input" 
                        placeholder="Cari berdasarkan ID RSO..."
                        autocomplete="off"
                        aria-label="Cari draft berdasarkan ID RSO"
                        aria-describedby="searchHint"
                    >
                    <div class="draft-search-actions">
                        <span class="draft-search-indicator" aria-hidden="true">
                            <i class="fa fa-check-circle" style="color: #10b981;"></i>
                        </span>
                        <button class="draft-search-clear" onclick="clearSearch()" title="Hapus pencarian" aria-label="Hapus pencarian">
                            <i class="fa fa-times-circle"></i>
                        </button>
                    </div>
                </div>
                <span class="draft-search-hint" id="searchHint">
                    <i class="fa fa-lightbulb-o"></i> Ketik untuk mencari secara real-time
                </span>
            </div>

            <div class="draft-search-info" id="searchResultsInfo" style="display: none;">
                <span>Ditemukan:</span>
                <span class="draft-search-info-badge" id="resultsBadge">0</span>
            </div>
        </div>

        <!-- Card Body -->
        <div class="draft-card-body">
            <div class="draft-table-wrapper">
                <table class="draft-table">
                    <thead>
                        <tr>
                            <th>ID RSO</th>
                            <th>PROJECT</th>
                            <th>TERAKHIR DISIMPAN</th>
                            <th>JUMLAH OBL</th>
                            <th style="width: 200px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody id="draft-list-body">
                        <!-- Data akan dimuat via JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div id="empty-msg" class="draft-empty-state" style="display: none;">
                <div class="draft-empty-icon">
                    <i class="fa fa-folder-open-o"></i>
                </div>
                <h3 class="draft-empty-title">Belum Ada Draft</h3>
                <p class="draft-empty-text">
                    Anda belum memiliki draft yang tersimpan di browser ini.<br>
                    Mulai buat draft RSO baru untuk memulai.
                </p>
                <button onclick="openCreateModal()" class="draft-btn-create-first">
                    <i class="fa fa-plus-circle"></i> Buat Draft Pertama
                </button>
            </div>
        </div>
    </div>

    <!-- Create Modal -->
    <div class="draft-modal-overlay" id="createModal" onclick="closeModalOnOverlay(event)">
        <div class="draft-modal">
            <div class="draft-modal-header">
                <h3 class="draft-modal-title">Buat Draft Baru</h3>
                <button class="draft-modal-close" onclick="closeCreateModal()">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <div class="draft-modal-body">
                <div class="draft-form-group">
                    <label class="draft-form-label">ID RSO *</label>
                    <input 
                        type="text" 
                        id="newRsoId" 
                        class="draft-form-input" 
                        placeholder="Contoh: RSO-2026-001"
                        autocomplete="off"
                    >
                    <span class="draft-form-hint">
                        <i class="fa fa-info-circle"></i> Masukkan ID RSO yang akan dibuat
                    </span>
                </div>
            </div>
            <div class="draft-modal-footer">
                <button class="draft-modal-btn draft-modal-btn-cancel" onclick="closeCreateModal()">
                    <i class="fa fa-times"></i> Batal
                </button>
                <button class="draft-modal-btn draft-modal-btn-create" id="createBtn" onclick="createNewDraft()">
                    <i class="fa fa-check"></i> Buat Draft
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    const apiListUrl = "{{ admin_url('api/autodraft/list') }}";
    const apiDeleteUrl = "{{ admin_url('api/autodraft') }}";
    const csrfToken = "{{ csrf_token() }}";
    
    // Store all drafts for search functionality
    let allDrafts = [];

    document.addEventListener("DOMContentLoaded", function() {
        loadDraftsFromDatabase();
        
        // Add search listener
        const searchInput = document.getElementById('draftSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                filterDrafts(this.value.trim());
            });
        }
    });
    
    function loadDraftsFromDatabase() {
        const tbody = document.getElementById('draft-list-body');
        const emptyMsg = document.getElementById('empty-msg');
        const draftCounter = document.getElementById('draft-counter');
        
        // Show loading state
        tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 40px;"><i class="fa fa-spinner fa-spin"></i> Memuat data...</td></tr>';
        
        fetch(apiListUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(response => response.json())
        .then(result => {
            tbody.innerHTML = '';
            
            if (!result.success) {
                console.error('API Error:', result.message);
                showEmptyState(tbody, emptyMsg, draftCounter);
                return;
            }
            
            const drafts = result.data || [];
            allDrafts = drafts; // Store all drafts
            
            if (drafts.length === 0) {
                showEmptyState(tbody, emptyMsg, draftCounter);
                return;
            }
            
            displayDrafts(drafts, tbody, emptyMsg, draftCounter);
            draftCounter.textContent = drafts.length + ' Draft';
            emptyMsg.style.display = 'none';
            
            // Enable search input
            document.getElementById('draftSearchInput').disabled = false;
        })
        .catch(error => {
            console.error('Error loading drafts:', error);
            tbody.innerHTML = '';
            showEmptyState(tbody, emptyMsg, draftCounter);
        });
    }
    
    function displayDrafts(drafts, tbody, emptyMsg, draftCounter) {
        tbody.innerHTML = '';
        
        if (drafts.length === 0) {
            showEmptyStateForSearch(tbody, emptyMsg, draftCounter);
            return;
        }
        
        drafts.forEach((draft, index) => {
            // Format Tanggal
            const dateObj = new Date(draft.updated_at);
            const dateStr = dateObj.toLocaleDateString('id-ID', { 
                day: 'numeric', 
                month: 'short', 
                year: 'numeric' 
            });
            const timeStr = dateObj.toLocaleTimeString('id-ID', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });

            // Buat URL untuk halaman autodraft
            const autoUrl = "{{ admin_url('rso') }}/" + draft.id_rso + "/autodraft";

            const row = document.createElement('tr');
            row.id = 'draft-row-' + draft.id_rso;
            row.style.animation = `fadeIn 0.3s ease ${index * 30}ms`;
            row.innerHTML = `
                <td>
                    <span class="draft-id-badge">${draft.id_rso}</span>
                </td>
                <td>
                    <div class="draft-project-info">
                        <div class="draft-project-title">${draft.judul_p1 || '<span style="color: #cbd5e1;">Tanpa Judul</span>'}</div>
                        <div class="draft-customer-name">
                            <i class="fa fa-building-o"></i>
                            ${draft.pelanggan || '<span style="color: #cbd5e1;">-</span>'}
                        </div>
                    </div>
                </td>
                <td>
                    <div class="draft-timestamp">
                        <span><i class="fa fa-calendar"></i> ${dateStr}</span>
                        <span><i class="fa fa-clock-o"></i> ${timeStr}</span>
                    </div>
                </td>
                <td>
                    <span class="draft-obl-badge">${draft.obl_count} OBL</span>
                </td>
                <td>
                    <div class="draft-actions">
                        <a href="${autoUrl}" class="draft-btn draft-btn-edit" title="Edit draft ini">
                            <i class="fa fa-pencil"></i> Edit Draft
                        </a>
                        <button onclick="deleteDraft('${draft.id_rso}')" class="draft-btn draft-btn-delete" title="Hapus draft ini secara permanen">
                            <i class="fa fa-trash"></i> Hapus
                        </button>
                    </div>
                </td>
            `;
            tbody.appendChild(row);
        });
        
        draftCounter.textContent = drafts.length + ' Draft';
        emptyMsg.style.display = 'none';
        tbody.style.display = '';
        
        // Update search results info
        updateSearchResultsInfo(drafts.length);
    }
    
    function filterDrafts(searchTerm) {
        const tbody = document.getElementById('draft-list-body');
        const emptyMsg = document.getElementById('empty-msg');
        const draftCounter = document.getElementById('draft-counter');
        const searchInput = document.getElementById('draftSearchInput');
        const searchWrapper = searchInput.closest('.draft-search-input-wrapper');
        
        // Add active class for visual feedback
        if (searchTerm) {
            searchWrapper.classList.add('active');
        } else {
            searchWrapper.classList.remove('active');
        }
        
        if (!searchTerm) {
            // If search is empty, show all drafts
            displayDrafts(allDrafts, tbody, emptyMsg, draftCounter);
            document.getElementById('searchResultsInfo').style.display = 'none';
            return;
        }
        
        // Filter drafts based on ID RSO (case-insensitive)
        const filteredDrafts = allDrafts.filter(draft => 
            draft.id_rso.toLowerCase().includes(searchTerm.toLowerCase())
        );
        
        displayDrafts(filteredDrafts, tbody, emptyMsg, draftCounter);
    }
    
    function updateSearchResultsInfo(count) {
        const searchInput = document.getElementById('draftSearchInput');
        const searchResultsInfo = document.getElementById('searchResultsInfo');
        const resultsBadge = document.getElementById('resultsBadge');
        
        if (searchInput.value.trim()) {
            if (count === 0) {
                searchResultsInfo.classList.add('no-results');
                searchResultsInfo.innerHTML = `
                    <i class="fa fa-search" style="opacity: 0.5;"></i>
                    <span>Tidak ada hasil untuk "<strong>${searchInput.value}</strong>"</span>
                `;
            } else {
                searchResultsInfo.classList.remove('no-results');
                resultsBadge.textContent = count;
            }
            searchResultsInfo.classList.add('show');
            searchResultsInfo.style.display = 'flex';
        } else {
            searchResultsInfo.style.display = 'none';
        }
    }
    
    function showEmptyStateForSearch(tbody, emptyMsg, draftCounter) {
        tbody.style.display = 'none';
        emptyMsg.classList.add('no-search-results');
        emptyMsg.style.display = 'block';
        draftCounter.textContent = '0 Draft';
    }
    
    function showEmptyState(tbody, emptyMsg, draftCounter) {
        tbody.style.display = 'none';
        emptyMsg.style.display = 'block';
        draftCounter.textContent = '0 Draft';
    }

    function clearSearch() {
        const searchInput = document.getElementById('draftSearchInput');
        const searchWrapper = searchInput.closest('.draft-search-input-wrapper');
        searchInput.value = '';
        searchInput.focus();
        searchWrapper.classList.remove('active');
        filterDrafts('');
    }

    // Fungsi untuk membuka modal create
    function openCreateModal() {
        const modal = document.getElementById('createModal');
        modal.classList.add('show');
        document.getElementById('newRsoId').focus();
        
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    // Fungsi untuk menutup modal
    function closeCreateModal() {
        const modal = document.getElementById('createModal');
        modal.classList.remove('show');
        document.getElementById('newRsoId').value = '';
        
        // Restore body scroll
        document.body.style.overflow = '';
    }

    // Tutup modal jika klik di overlay
    function closeModalOnOverlay(event) {
        if (event.target.id === 'createModal') {
            closeCreateModal();
        }
    }

    // Handle ESC key untuk tutup modal
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeCreateModal();
        }
    });

    // Handle Enter key di input untuk submit
    document.getElementById('newRsoId').addEventListener('keypress', function(event) {
        if (event.key === 'Enter') {
            createNewDraft();
        }
    });

    // Fungsi untuk membuat draft baru
    function createNewDraft() {
        const rsoId = document.getElementById('newRsoId').value.trim();
        const createBtn = document.getElementById('createBtn');
        
        if (!rsoId) {
            alert('⚠️ ID RSO tidak boleh kosong!');
            document.getElementById('newRsoId').focus();
            return;
        }

        // Validasi format ID RSO (opsional)
        if (rsoId.length < 3) {
            alert('⚠️ ID RSO terlalu pendek! Minimal 3 karakter.');
            document.getElementById('newRsoId').focus();
            return;
        }

        // Cek apakah ID sudah ada
        const existingKey = 'draft_rso_' + rsoId;
        if (localStorage.getItem(existingKey)) {
            if (!confirm('⚠️ ID RSO ini sudah ada!\n\nApakah Anda ingin melanjutkan edit draft yang sudah ada?')) {
                return;
            }
        }

        // Disable button saat proses
        createBtn.disabled = true;
        createBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Membuat...';

        // Redirect ke halaman autodraft
        const autoUrl = "{{ admin_url('rso') }}/" + rsoId + "/autodraft";
        
        setTimeout(() => {
            window.location.href = autoUrl;
        }, 300);
    }

    // Fungsi untuk menghapus draft dari database
    function deleteDraft(idRso) {
        if (confirm('⚠️ Hapus Draft?\n\nApakah Anda yakin ingin menghapus draft ini? Data yang dihapus tidak dapat dikembalikan.')) {
            fetch(apiDeleteUrl + '/' + idRso, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Also remove from localStorage for cleanup
                    localStorage.removeItem('draft_rso_' + idRso);
                    
                    // Tampilkan notifikasi sukses
                    const notification = document.createElement('div');
                    notification.style.cssText = `
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #10b981;
                        color: white;
                        padding: 16px 24px;
                        border-radius: 12px;
                        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
                        font-weight: 600;
                        z-index: 9999;
                        animation: slideIn 0.3s ease;
                    `;
                    notification.innerHTML = '<i class="fa fa-check-circle"></i> Draft berhasil dihapus';
                    document.body.appendChild(notification);
                    
                    setTimeout(() => {
                        notification.remove();
                        // Reload the list
                        loadDraftsFromDatabase();
                    }, 1500);
                } else {
                    alert('Gagal menghapus: ' + (result.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error deleting draft:', error);
                alert('Terjadi kesalahan saat menghapus draft. Silakan coba lagi.');
            });
        }
    }
</script>

<style>
@keyframes slideIn {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>