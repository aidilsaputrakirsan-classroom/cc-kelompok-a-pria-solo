@extends('advance-reviews.layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/advance-review-result.css') }}">
@endpush

@section('content')
<div>
    {{-- Loading Overlay --}}
    <div class="loading-overlay show" id="pageLoadingOverlay">
        <div class="loading-spinner-large"></div>
        <div class="loading-text">Sedang Memuat Data<span class="loading-dots"></span></div>
        <p class="text-muted">Mohon tunggu, data sedang dimuat...</p>
    </div>

    <div class="advance-review-wrapper" data-ticket-number="{{ $ticket->ticket_number }}"
        data-project-title="{{ $ticket->project_title ?? '' }}" data-company-name="{{ $ticket->company->name ?? '' }}"
        data-contract-value="{{ $ticket->groundTruth->dpp ?? '' }}">

        <div class="container-fluid">

            {{-- Main Content: Two Columns (Fixed Height) --}}
            <div class="review-content">

                {{-- LEFT COLUMN: Sidebar --}}
                <div class="sidebar-container">

                    {{-- Ground Truth Card (ALWAYS SHOWN, FIXED HEIGHT) --}}
                    <div class="ground-truth-card">
                        <div class="ground-truth-header">
                            <div class="ground-truth-title">
                                <i class="bi bi-database"></i>
                                <span>Ground Truth</span>
                            </div>
                        </div>
                        <p class="ground-truth-description">
                            Data acuan utama untuk validasi dokumen kontrak.
                        </p>
                        <div id="ground-truth-container" class="gt-content-container">
                            {{-- Ground truth fields akan di-render via JavaScript --}}
                            <div class="text-center py-3">
                                <div class="spinner-border spinner-border-sm text-primary"></div>
                                <p class="text-muted small mt-2">Memuat data...</p>
                            </div>
                        </div>
                    </div>

                    {{-- Stage Cards Wrapper (SCROLLABLE IF > 2 CARDS) --}}
                    <div class="stage-cards-wrapper" id="stage-cards-container">
                        {{-- Stage cards akan di-render via JavaScript --}}
                    </div>

                </div>

                {{-- RIGHT COLUMN: PDF Viewer --}}
                <div class="pdf-viewer-card">
                    <div class="pdf-viewer-header">
                        <div class="pdf-viewer-title">
                            <i class="bi bi-file-pdf"></i>
                            <span class="pdf-viewer-title-text">{{ $documentName ?? 'Dokumen PDF' }}</span>
                        </div>
                    </div>

                    <div class="pdf-viewer-body">
                        <div id="loading-indicator" class="text-center py-5">
                            <div class="loading-spinner mb-3"></div>
                            <p class="text-muted">Memuat PDF...</p>
                        </div>

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
<script>
    (function() {
        // Execute immediately for Pjax compatibility
        function init() {
            if (typeof initAdvanceResultViewer !== 'undefined') {
                initAdvanceResultViewer(
                    "{{ $pdfUrl }}",
                    "{{ $ticket->ticket_number }}",
                    "{{ $docType }}"
                );
            } else {
                // If scripts not loaded yet, wait a bit and retry
                setTimeout(init, 100);
            }
        }
        // Execute immediately (works for Pjax)
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        } else {
            init();
        }
    })();
</script>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/notes.css') }}">
    {{-- Template CSS untuk styling form --}}
    <link rel="stylesheet" href="{{ asset('css/templates/template-kontrak.css') }}">
    <link rel="stylesheet" href="{{ asset('css/templates/template-npk.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/notes.js') }}"></script>
    {{-- PDF.js Library  --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

    {{-- Read-Only Form Templates  --}}
    <script src="{{ asset('js/form-templates/form-kontrak-readonly.js') }}"></script>
    <script src="{{ asset('js/form-templates/form-npk-readonly.js') }}"></script>

    {{-- Main Advance Result JS --}}
    <script src="{{ asset('js/advance-review-handler.js') }}"></script>
@endpush