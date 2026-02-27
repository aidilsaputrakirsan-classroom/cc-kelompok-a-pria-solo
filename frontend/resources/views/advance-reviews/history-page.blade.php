@extends('advance-reviews.layouts.app')

{{-- CSS and JS are loaded via Admin::css() and Admin::js() in RiwayatController --}}

@section('content')
<div>
    @php
        use App\Services\LogoService;
    @endphp

    <div class="container-fluid" style="background: #f8f9fa; border-radius: 35px; padding: 30px;">

        <!-- Search Bar -->
        <div class="search-section d-flex justify-content-center mt-1">
            <div class="search-wrapper w-100" style="max-width: 900px;">
                <input type="text" id="searchInput" class="form-control form-control-lg rounded-pill shadow-sm py-3 px-4"
                    placeholder="Cari berdasarkan nomor tiket atau judul project atau nama mitra" value="{{ $search ?? '' }}"
                    data-search-url="{{ route('projess.ai.riwayat.index') }}">
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="loadingIndicator" class="text-center my-4" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Cards Container -->
        <div class="mt-5 row" id="ticketsContainer">
            @forelse ($tickets as $t)
                @php
                    $logo = LogoService::find($t->company_name);
                    $isValidation = $t->stage === 'Validation';
                    // Debug: Uncomment to see company names
                    // echo "Company: '{$t->company_name}' | Logo: '$logo'<br>";
                @endphp
                <div class="col-sm-6 col-lg-4 mb-4 ticket-card" data-ticket-id="{{ $t->id }}"
                    data-stage="{{ $t->stage }}">
                    <div class="card h-100 shadow-sm border-0 rounded-4">
                        <div class="card-body p-4 d-flex flex-column">
                            <!-- Header with Logo and Info -->
                            <div class="d-flex align-items-center mb-3">
                                <div class="profile-image-container me-3 flex-shrink-0">
                                    <img src="{{ $logo }}" alt="Logo Mitra" class="rounded-3"
                                        style="width: 56px; height: 56px; object-fit: contain; background: #f8f9fa; padding: 8px;">
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <h5 class="card-title mb-1 fw-bold text-truncate"
                                        style="color: #212529; font-size: 1.1rem;">
                                        {{ $t->ticket_number }}
                                    </h5>
                                    <p class="text-muted small mb-0 text-truncate" style="font-size: 0.85rem;">
                                        {{ $t->company_name }}
                                    </p>
                                </div>
                            </div>

                            <!-- Description -->
                            <p class="card-text text-muted mb-3"
                                style="font-size: 0.9rem; line-height: 1.5; height: 3em; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                {{ $t->project_title }}
                            </p>

                            <!-- Stats Section with Background Container -->
                            <div class="row g-2 text-center">
                                <div class="col-4">
                                    <div class="stat-item p-2 rounded-3" style="background-color: #ffffff;">
                                        <p class="text-muted small mb-1" style="font-size: 0.7rem; font-weight: 500;">
                                            Total Notes
                                        </p>
                                        <h5 class="fw-bold mb-0" style="color: #212529; font-size: 1.35rem;">
                                            {{ $t->total_notes ?? 'N/A' }}
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item p-2 rounded-3" style="background-color: #ffffff;">
                                        <p class="text-muted small mb-1" style="font-size: 0.7rem; font-weight: 500;">
                                            Total Error
                                        </p>
                                        <h5 class="fw-bold mb-0" style="color: #212529; font-size: 1.35rem;">
                                            {{ $t->total_errors ?? 'N/A' }}
                                        </h5>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-item p-2 rounded-3" style="background-color: #ffffff;">
                                        <p class="text-muted small mb-1" style="font-size: 0.7rem; font-weight: 500;">
                                            Total File
                                        </p>
                                        <h5 class="fw-bold mb-0" style="color: #212529; font-size: 1.35rem;">
                                            {{ $t->total_files ?? 'N/A' }}
                                        </h5>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="row g-2 mt-3">
                                <div class="col-6">
                                    @if ($isValidation)
                                        <a href="{{ route('projess.ai.validate-ground-truth', $t->ticket_number) }}"
                                            class="btn btn-dark w-100 rounded-pill fw-semibold d-flex align-items-center justify-content-center"
                                            style="padding: 10px 16px; font-size: 0.85rem; height: 44px;">
                                            Validation
                                        </a>
                                    @else
                                        <a href="{{ route('projess.tickets.advance-reviews', $t->ticket_number) }}"
                                            class="btn btn-dark w-100 rounded-pill fw-semibold d-flex align-items-center justify-content-center"
                                            style="padding: 10px 16px; font-size: 0.85rem; height: 44px;">
                                            Review
                                        </a>
                                    @endif
                                </div>
                                <div class="col-6">
                                    <button
                                        class="btn btn-outline-danger w-100 rounded-pill fw-semibold btn-hapus d-flex align-items-center justify-content-center"
                                        style="padding: 10px 16px; font-size: 0.85rem; height: 44px;"
                                        data-ticket-id="{{ $t->id }}" data-stage="{{ $t->stage }}">
                                        Hapus Tiket
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <img src="{{ asset('image/empty-state.svg') }}" alt="No data" style="width: 200px; opacity: 0.5;">
                    <h5 class="text-muted mt-3">Tidak ada data ditemukan</h5>
                    <p class="text-muted">Coba ubah kata kunci pencarian Anda</p>
                </div>
            @endforelse
        </div>

    </div>
</div>

{{-- Include Calculator Widget (Hidden by default on history page) --}}
@include('advance-reviews.partials.calculator', ['hiddenByDefault' => true])
@endsection

@push('scripts')
    <script src="{{ asset('js/history-page-handler.js') }}"></script>
@endpush