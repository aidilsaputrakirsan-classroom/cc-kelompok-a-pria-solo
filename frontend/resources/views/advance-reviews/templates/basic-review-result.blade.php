@extends('advance-reviews.layouts.app')

@section('content')
<div>
    {{-- Loading Overlay --}}
    <div class="loading-overlay show" id="pageLoadingOverlay">
        <div class="loading-spinner-large"></div>
        <div class="loading-text">Sedang Memuat Data<span class="loading-dots"></span></div>
        <p class="text-muted">Mohon tunggu, data sedang dimuat...</p>
    </div>

    <div class="basic-review-wrapper" data-ticket-number="{{ $ticket->ticket_number }}"
        data-project-title="{{ $ticket->project_title ?? '' }}" data-company-name="{{ $ticket->company->name ?? '' }}"
        data-contract-value="{{ $ticket->groundTruth->dpp ?? '' }}">

        <div class="container-fluid">

            {{-- Main Content: Two Columns (Fixed Height) --}}
            <div class="review-content">

                {{-- LEFT COLUMN: Issues List (3 Separate Cards) --}}
                <div class="issues-list-card">
                    <div class="issues-list-body" id="issues-list-container">

                        {{-- Loading State --}}
                        <div class="text-center py-5" id="issues-loading">
                            <div class="spinner-border text-primary mb-3"></div>
                            <p class="text-muted">Memuat data issues...</p>
                        </div>

                        {{-- CARD 1: Typo Section (ALWAYS SHOWN) --}}
                        <div class="issue-section" id="typo-section" style="display: flex;">
                            <div class="issue-section-header">
                                <div class="issue-section-title">
                                    <i class="bi bi-spellcheck"></i>
                                    <span>Daftar Typo</span>
                                </div>
                                <span class="issue-section-badge" id="typo-count">0</span>
                            </div>
                            <p class="issue-section-description">
                                Sistem Pengecekan Typo akan memeriksa kesalahan pengetikan dalam dokumen.
                            </p>
                            <div id="typo-list">
                                {{-- Typo cards atau empty state akan di-render via JavaScript --}}
                            </div>
                        </div>

                        {{-- CARD 2: Nominal Section (ALWAYS SHOWN) --}}
                        <div class="issue-section" id="nominal-section" style="display: flex;">
                            <div class="issue-section-header">
                                <div class="issue-section-title">
                                    <i class="bi bi-currency-dollar"></i>
                                    <span>Validasi Format Nominal</span>
                                </div>
                                <span class="issue-section-badge" id="nominal-count">0</span>
                            </div>
                            <p class="issue-section-description">
                                Sistem Validasi Format Nominal akan memeriksa konsistensi nominal dan terbilangnya.
                            </p>
                            <div id="nominal-list">
                                {{-- Nominal cards atau empty state akan di-render via JavaScript --}}
                            </div>
                        </div>

                        {{-- CARD 3: Date Section (ALWAYS SHOWN) --}}
                        <div class="issue-section" id="date-section" style="display: flex;">
                            <div class="issue-section-header">
                                <div class="issue-section-title">
                                    <i class="bi bi-calendar"></i>
                                    <span>Validasi Format Tanggal</span>
                                </div>
                                <span class="issue-section-badge" id="date-count">0</span>
                            </div>
                            <p class="issue-section-description">
                                Sistem Validasi Format Tanggal akan memeriksa konsistensi format tanggal.
                            </p>
                            <div id="date-list">
                                {{-- Date cards atau empty state akan di-render via JavaScript --}}
                            </div>
                        </div>

                    </div>
                </div>

                {{-- RIGHT COLUMN: PDF Viewer --}}
                <div class="pdf-viewer-card">
                    <div class="pdf-viewer-header">
                        <div class="pdf-viewer-title">
                            <i class="bi bi-file-pdf"></i>
                            <span class="pdf-viewer-title-text">Review Result - {{ $ticket->ticket_number }}</span>
                        </div>
                    </div>

                    <div class="pdf-viewer-body">
                        <div id="pdf-container" class="pdf-images-container" style="display: none;">
                            {{-- PDF pages akan di-render di sini via JavaScript --}}
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
    @include('advance-reviews.partials.notes-panel')
</div>

{{-- Init for basic-review-handler.js: must be in body so it runs when using OpenAdmin Content (no @stack('scripts')) --}}
<script>
(function() {
    var ticketNumber = "{{ $ticket->ticket_number ?? '' }}";
    var apiUrl = "{{ route(config('admin.route.prefix') . '.api.basic-result.issues', ['ticket' => $ticket->ticket_number ?? '']) }}";
    function init() {
        if (typeof initPDFViewer !== 'undefined') {
            initPDFViewer(ticketNumber, apiUrl);
        } else {
            setTimeout(init, 100);
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/basic-review-result.css') }}">
    <link rel="stylesheet" href="{{ asset('css/notes.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/notes.js') }}"></script>
    <!-- PDF.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

    <!-- Custom PDF Annotator Script (init is in body for OpenAdmin compatibility) -->
    <script src="{{ asset('js/basic-review-handler.js') }}"></script>
@endpush