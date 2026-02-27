@extends('advance-reviews.layouts.app')

@section('content')
    {{-- Include Modals --}}
    @include('advance-reviews.partials.modal-upload-review')
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Ektraksi Informasi<span class="loading-dots"></span></div>
        <p class="text-muted">Mohon tunggu, proses ektraksi informasi sedang berlangsung</p>
    </div>

    <!-- Success Modal -->
    <div class="modal fade result-modal" id="successModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="result-icon success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h4 class="result-title text-success">Validasi Berhasil!</h4>
                    <p class="result-message">Dokumen invoice Anda telah berhasil divalidasi. Anda akan diarahkan ke halaman
                        hasil dalam beberapa detik.</p>
                </div>
            </div>
        </div>
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
                    <h4 class="result-title text-danger">Upload Gagal!</h4>
                    <p class="result-message" id="errorMessage">
                        Terjadi kesalahan saat memproses dokumen. Silakan coba lagi atau hubungi tim support jika masalah
                        berlanjut.
                    </p>
                    <p class="text-muted small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Form sudah di-reset. Silakan upload ulang dengan data baru.
                    </p>
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if(isset($isOpenAdmin) && $isOpenAdmin)
        {{-- OpenAdmin layout: improved card design with better spacing and visual hierarchy --}}
        <div class="container-fluid doc-review-openadmin">
            <div class="card doc-review-card">
                <div class="card-header doc-review-card-header">
                    <div class="d-flex align-items-center gap-3">
                        <div class="doc-review-icon-wrapper">
                            <i class="bi bi-file-earmark-check-fill"></i>
                        </div>
                        <div>
                            <h3 class="card-title mb-0 doc-review-title">Validasi Dokumen dengan Mudah!</h3>
                            <p class="text-muted small mb-0 mt-1">Review dokumen dengan cepat dan akurat</p>
                        </div>
                    </div>
                </div>
                <div class="card-body doc-review-card-body">
                    <div class="row align-items-center g-4">
                        {{-- Content Column --}}
                        <div class="col-lg-6 order-2 order-lg-1">
                            <div class="doc-review-content">
                                <p class="doc-review-description">
                                    Mulai proses review dokumen untuk memastikan kelengkapan, konsistensi, dan kepatuhan sebelum
                                    dokumen diproses lebih lanjut.
                                </p>
                                <div class="doc-review-features mb-4">
                                    <div class="feature-item">
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                        <span>Validasi otomatis</span>
                                    </div>
                                    <div class="feature-item">
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                        <span>Review cepat</span>
                                    </div>
                                    <div class="feature-item">
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                        <span>Hasil akurat</span>
                                    </div>
                                </div>
                                <button class="btn btn-primary btn-lg doc-review-cta-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#uploadModalAdvanced">
                                    <i class="bi bi-cloud-upload me-2"></i>
                                    <span>Mulai Review</span>
                                </button>
                            </div>
                        </div>
                        {{-- Image Column --}}
                        <div class="col-lg-6 order-1 order-lg-2">
                            <div class="doc-review-image-wrapper">
                                <img src="{{ asset('images/Web Images/homepage-image.webp') }}"
                                    alt="Document Validation" 
                                    class="doc-review-image"
                                    loading="lazy">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- Standalone layout: improved hero section with better spacing and visual hierarchy --}}
        <section class="doc-review-hero-section">
            <div class="container doc-review-hero-container">
                <div class="row align-items-center g-4 g-lg-5">
                    {{-- Content Column --}}
                    <div class="col-lg-6 order-2 order-lg-1">
                        <div class="doc-review-hero-content">
                            <div class="doc-review-badge mb-3">
                                <i class="bi bi-shield-check me-2"></i>
                                <span>Platform Terpercaya</span>
                            </div>
                            <h1 class="doc-review-hero-title">
                                Validasi Dokumen dengan Mudah!
                            </h1>
                            <p class="doc-review-hero-description">
                                Mulai proses review dokumen untuk memastikan kelengkapan, konsistensi, dan kepatuhan sebelum
                                dokumen diproses lebih lanjut.
                            </p>
                            <div class="doc-review-hero-features mb-4">
                                <div class="hero-feature-item">
                                    <i class="bi bi-lightning-charge-fill"></i>
                                    <span>Proses Cepat</span>
                                </div>
                                <div class="hero-feature-item">
                                    <i class="bi bi-shield-check-fill"></i>
                                    <span>Hasil Akurat</span>
                                </div>
                                <div class="hero-feature-item">
                                    <i class="bi bi-graph-up-arrow"></i>
                                    <span>Efisien</span>
                                </div>
                            </div>
                            <div class="d-flex flex-column flex-sm-row gap-3">
                                <button class="btn btn-primary btn-lg doc-review-hero-cta" 
                                    data-bs-toggle="modal"
                                    data-bs-target="#uploadModalAdvanced">
                                    <i class="bi bi-cloud-upload me-2"></i>
                                    <span>Mulai Review</span>
                                </button>
                                <button class="btn btn-outline-secondary btn-lg doc-review-hero-secondary">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <span>Pelajari Lebih Lanjut</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    {{-- Image Column --}}
                    <div class="col-lg-6 order-1 order-lg-2">
                        <div class="doc-review-hero-image-wrapper">
                            <div class="doc-review-hero-image-decoration"></div>
                            <img src="{{ asset('images/Web Images/homepage-image.webp') }}"
                                alt="Document Validation Illustration" 
                                class="doc-review-hero-image"
                                loading="lazy">
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection

{{-- CSS and JS are loaded via Admin::css() and Admin::js() in AiAdvanceReviewController --}}
    <style>
        /* Scoped styles for advance-review content - prevents OpenAdmin conflicts */
        #advance-review-main-content {
            width: 100%;
            margin: 0;
            padding: 0;
        }
        
        /* Navbar wrapper: breaks out of container padding to maintain navbar's own styling */
        #advance-review-main-content .navbar-wrapper {
            margin-left: -1.5rem;
            margin-right: -1.5rem;
            margin-bottom: 1rem;
            margin-top: 0;
        }
        @media (min-width: 768px) {
            #advance-review-main-content .navbar-wrapper {
                margin-left: -3rem;
                margin-right: -3rem;
            }
        }
        
        /* Ensure section takes full width and height */
        #advance-review-main-content section {
            width: 100%;
            min-height: 100vh;
            margin-top: 0;
            padding-top: 0;
        }
        
        /* Reduce top padding/margin of container inside section */
        #advance-review-main-content section .container {
            padding-top: 0;
            margin-top: 0;
        }
        
        /* Prevent OpenAdmin container styles from affecting our content */
        #advance-review-main-content .container {
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
    </style>

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/doc-review-landing-page.css') }}">
    <link rel="stylesheet" href="{{ asset('css/file-upload.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('js/file-upload-handler.js') }}"></script>
    <script src="{{ asset('js/doc-review-landing-page.js') }}"></script>
@endpush
