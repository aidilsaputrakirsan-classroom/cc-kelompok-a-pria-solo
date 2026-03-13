@extends('advance-reviews.layouts.app')

@section('content')

<div>
    {{-- Loading Overlay - Same style as landing page --}}
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Review Tiket<span class="loading-dots"></span></div>
        <p class="text-muted">Mohon tunggu, proses review sedang berlangsung</p>
    </div>

    <!-- Error Modal -->
    <div class="modal fade result-modal" id="errorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="result-icon error">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <h4 class="result-title text-danger">Review Gagal!</h4>
                    <p class="result-message" id="errorMessage">
                        Terjadi kesalahan saat memproses review. Silakan coba lagi atau hubungi tim support jika masalah
                        berlanjut.
                    </p>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="ground-truth-wrapper">
        <div class="container-fluid px-3 py-4">
            <div class="row g-3">

                <!-- Left Side - Title Card + Form -->
                <div class="col-lg-4 order-lg-1">
                    <!-- Title Card -->
                    <div class="title-card mb-3">
                        <div class="card-content">
                            <h3 class="card-highlight">Validasi Hasil Ekstraksi</h3>
                            <h4 class="card-title">Pastikan semua hasil ekstraksi akurat sebelum melanjutkan ke tahap
                                berikutnya</h4>
                        </div>

                        <!-- ========== PROGRESS BAR SECTION ========== -->
                        <div class="progress-section">
                            <div class="progress-label">
                                <span class="progress-text">Progress Validasi</span>
                                <span class="progress-percentage" id="progress-percentage">0%</span>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" id="progress-bar-fill" style="width: 0%"></div>
                            </div>
                            <div class="progress-details">
                                <i class="bi bi-circle-fill"></i>
                                <span id="progress-text">0 dari {{ count($availableDocuments) }} dokumen telah
                                    direview</span>
                            </div>
                        </div>
                        <!-- ========== END PROGRESS BAR ========== -->

                        <button class="upload-btn" id="review-ticket-btn" disabled>
                            <span>Review Tiket</span>
                        </button>
                    </div>

                    <!-- Form Card (Scrollable) -->
                    <div class="form-card">
                        <div class="form-card-header">
                            <h5 class="form-card-title" id="form-title">Data Ekstraksi</h5>
                        </div>
                        <div class="form-card-body" id="extraction-form">
                            <!-- Form akan di-render via JavaScript -->
                            <div class="text-center py-4">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-muted small mt-2">Memuat data...</p>
                            </div>
                        </div>
                        <div class="form-card-footer">
                            <button type="button" class="btn-save" id="save-btn">
                                <i class="bi bi-save me-2"></i>
                                Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Side - PDF Viewer -->
                <div class="col-lg-8 order-lg-2">
                    <!-- Header with Steps -->
                    <div class="steps-wrapper mb-3">
                        @foreach ($availableDocuments as $index => $doc)
                            @if ($index > 0)
                                <div class="step-arrow">›</div>
                            @endif
                            <div class="step-item {{ $index === 0 ? 'active' : '' }}" data-doc-type="{{ $doc['type'] }}">
                                <div class="step-number">{{ $index + 1 }}</div>
                                <span class="step-text">{{ $doc['label'] }}</span>
                            </div>
                        @endforeach
                    </div>

                    <!-- PDF Viewer Card -->
                    <div class="pdf-viewer-card">
                        <div class="pdf-viewer-header">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-file-pdf me-2"></i>
                                <span class="fw-semibold fs-5" id="current-doc-name">
                                    {{ $availableDocuments[0]['label'] ?? 'Dokumen' }}
                                </span>
                            </div>
                        </div>

                        <div class="pdf-viewer-body">
                            <div id="pdf-loading" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="text-muted mt-2">Memuat PDF...</p>
                            </div>

                            <div id="pdf-container" class="pdf-images-container" style="display: none;">
                                <!-- PDF pages akan di-render di sini -->
                            </div>

                            <div id="pdf-error" style="display: none;" class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <span id="pdf-error-message">Gagal memuat PDF</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- Include Calculator Widget (Hidden by default) --}}
@include('advance-reviews.partials.calculator', ['hiddenByDefault' => true])

{{-- Config for validate-ground-truth.js: must be in body so it runs before OpenAdmin-injected scripts --}}
<script>
    window.GROUND_TRUTH_DATA = @json($groundTruthData ?? []);
    window.TICKET_NUMBER = "{{ $ticketNumber ?? '' }}";
    window.AVAILABLE_DOCUMENTS = @json($availableDocuments ?? []);
    window.CSRF_TOKEN = "{{ csrf_token() }}";
    window.REVIEW_SUBMIT_URL = "{{ url(config('admin.route.prefix') . '/api/review/submit') }}";
    window.REVIEW_STATUS_URL = "{{ url(config('admin.route.prefix') . '/api/review/status') }}";
</script>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/validate-ground-truth.css') }}">
    <!-- Load NPK Template CSS -->
    <link rel="stylesheet" href="{{ asset('css/templates/template-npk.css') }}">
    <link rel="stylesheet" href="{{ asset('css/templates/template-kontrak.css') }}">
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

    <!-- Load NPK Template JS BEFORE main JS -->
    <script src="{{ asset('js/form-templates/form-npk.js') }}"></script>
    <script src="{{ asset('js/form-templates/form-npk-readonly.js') }}"></script>
    <script src="{{ asset('js/form-templates/form-kontrak.js') }}"></script>
    <script src="{{ asset('js/form-templates/form-kontrak-readonly.js') }}"></script>
    <script src="{{ asset('js/form-templates/form-p7.js') }}"></script>

    <!-- Inline scripts with PHP data -->
    <script>
        const GROUND_TRUTH_DATA = @json($groundTruthData);
        const TICKET_NUMBER = "{{ $ticketNumber }}";
        const AVAILABLE_DOCUMENTS = @json($availableDocuments);
        const CSRF_TOKEN = "{{ csrf_token() }}";
        const REVIEW_SUBMIT_URL = "{{ url(config('admin.route.prefix') . '/api/review/submit') }}";
        const REVIEW_STATUS_URL = "{{ url(config('admin.route.prefix') . '/api/review/status') }}";
    </script>

    <!-- Main Ground Truth Validation JS -->
    <script src="{{ asset('js/validate-ground-truth.js') }}"></script>
@endpush
