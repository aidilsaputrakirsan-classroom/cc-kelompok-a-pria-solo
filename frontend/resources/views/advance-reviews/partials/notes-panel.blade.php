{{-- FAB Button --}}
<button id="fabNotes" class="fab-notes">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
    </svg>
    <span class="fab-text">Notes</span>
</button>

{{-- Notes Panel --}}
<div id="notesPanel" class="notes-panel">
    <div class="notes-panel-content">
        {{-- Header --}}
        <div class="notes-header">
            <h5 class="notes-title">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                Catatan Tiket
            </h5>
            <button class="notes-close-btn" id="closeNotesPanel">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        {{-- Auto-Save Status Indicator --}}
        <div class="auto-save-status" id="saveStatusIndicator">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <circle cx="12" cy="12" r="10" stroke="#d1d5db"></circle>
            </svg>
            <span style="color: #6b7280;">Auto-save aktif</span>
        </div>

        {{-- Info Box --}}
        <div class="notes-info-box">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <div>
                <strong>Cara Penggunaan:</strong> Klik <code>+ Tambah Poin</code> untuk menambahkan catatan baru.
                <br>
            </div>
        </div>

        {{-- Notes Cards --}}
        <div class="notes-cards-container">
            {{-- Card 1: Revisi Mitra --}}
            <div class="note-card">
                <div class="note-card-header">
                    <div class="note-card-icon" style="background: #8b5cf6;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white"
                            stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                        </svg>
                    </div>
                    <h6 class="note-card-title">Revisi Mitra</h6>
                    <span class="note-counter" data-category="mitra">0</span>
                </div>
                <div class="note-card-body">
                    <div class="dynamic-points-container" data-category="mitra">
                        <div class="empty-state-mini">
                            <p>Belum ada catatan</p>
                        </div>
                    </div>
                    <button class="add-point-btn-mini" data-category="mitra">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Tambah Poin
                    </button>
                </div>
            </div>

            {{-- Card 2: OBL --}}
            <div class="note-card">
                <div class="note-card-header">
                    <div class="note-card-icon" style="background: #3b82f6;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white"
                            stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                            <polyline points="14 2 14 8 20 8"></polyline>
                            <line x1="16" y1="13" x2="8" y2="13"></line>
                            <line x1="16" y1="17" x2="8" y2="17"></line>
                        </svg>
                    </div>
                    <h6 class="note-card-title">OBL</h6>
                    <span class="note-counter" data-category="obl">0</span>
                </div>
                <div class="note-card-body">
                    <div class="dynamic-points-container" data-category="obl">
                        <div class="empty-state-mini">
                            <p>Belum ada catatan</p>
                        </div>
                    </div>
                    <button class="add-point-btn-mini" data-category="obl">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Tambah Poin
                    </button>
                </div>
            </div>

            {{-- Card 3: Internal Telkom --}}
            <div class="note-card">
                <div class="note-card-header">
                    <div class="note-card-icon" style="background: #10b981;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white"
                            stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </div>
                    <h6 class="note-card-title">Internal Telkom</h6>
                    <span class="note-counter" data-category="internal_telkom">0</span>
                </div>
                <div class="note-card-body">
                    <div class="dynamic-points-container" data-category="internal_telkom">
                        <div class="empty-state-mini">
                            <p>Belum ada catatan</p>
                        </div>
                    </div>
                    <button class="add-point-btn-mini" data-category="internal_telkom">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Tambah Poin
                    </button>
                </div>
            </div>

            {{-- Card 4: Segmen / Witel --}}
            <div class="note-card">
                <div class="note-card-header">
                    <div class="note-card-icon" style="background: #f59e0b;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white"
                            stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="2" y1="12" x2="22" y2="12"></line>
                            <path
                                d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z">
                            </path>
                        </svg>
                    </div>
                    <h6 class="note-card-title">Segmen / Witel</h6>
                    <span class="note-counter" data-category="segmen_witel">0</span>
                </div>
                <div class="note-card-body">
                    <div class="dynamic-points-container" data-category="segmen_witel">
                        <div class="empty-state-mini">
                            <p>Belum ada catatan</p>
                        </div>
                    </div>
                    <button class="add-point-btn-mini" data-category="segmen_witel">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Tambah Poin
                    </button>
                </div>
            </div>

            {{-- Card 5: Revisi Precise --}}
            <div class="note-card">
                <div class="note-card-header">
                    <div class="note-card-icon" style="background: #ec4899;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white"
                            stroke-width="2">
                            <polygon
                                points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2">
                            </polygon>
                        </svg>
                    </div>
                    <h6 class="note-card-title">Revisi Precise</h6>
                    <span class="note-counter" data-category="revisi_precise">0</span>
                </div>
                <div class="note-card-body">
                    <div class="dynamic-points-container" data-category="revisi_precise">
                        <div class="empty-state-mini">
                            <p>Belum ada catatan</p>
                        </div>
                    </div>
                    <button class="add-point-btn-mini" data-category="revisi_precise">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Tambah Poin
                    </button>
                </div>
            </div>
        </div>

        {{-- Footer Buttons --}}
        <div class="notes-footer">
            <button class="notes-btn notes-btn-secondary" id="copyAllNotesBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
                Salin Semua
            </button>
            <button class="notes-btn notes-btn-primary" id="saveNotesBtn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Simpan Catatan
            </button>
        </div>
    </div>
</div>

{{-- Overlay --}}
<div id="notesOverlay" class="notes-overlay"></div>
