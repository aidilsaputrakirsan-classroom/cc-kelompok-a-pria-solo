<nav id="ar-navbar" class="navbar navbar-expand-lg shadow-sm">
    <div class="container">
        <!-- Logo / Brand dengan Dark Pill Background -->
        <a id="navbar-brand-pill" class="fw-bold fs-4 d-flex align-items-center" href="{{ route('projess.ai.validasi-dokumen') }}">
            komo.AI
        </a>

        <!-- Toggler untuk mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar"
            aria-controls="offcanvasNavbar" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Offcanvas (Mobile) -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasNavbarLabel">Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">

                {{-- Cek apakah ini halaman Result --}}
                @php
                    $currentPath = Request::path();
                    $isResultPage =
                        str_contains($currentPath, '/result') || str_contains($currentPath, '/advance-result/') || str_contains($currentPath, '/basic-result/');
                @endphp

                @if ($isResultPage)
                    {{-- Menu untuk halaman Result: Tampilkan "Kembali ke Overview" --}}
                    <ul class="navbar-nav justify-content-start flex-grow-1 pe-3 gap-lg-3 fw-semibold">
                        <li class="nav-item">
                            <a class="nav-link fs-5"
                                href="{{ route('projess.tickets.advance-reviews', ['ticketNumber' => $ticketNumber ?? ($ticket->ticket_number ?? request()->route('ticket'))]) }}">
                                Kembali ke Overview
                            </a>
                        </li>
                    </ul>
                @else
                    {{-- Menu Default: Review Dokumen & Riwayat Review --}}
                    <ul class="navbar-nav justify-content-start flex-grow-1 pe-3 gap-lg-3 fw-semibold">
                        <li class="nav-item">
                            <a class="nav-link fs-5 {{ Request::is('projess/validasi-dokumen*') ? 'active' : '' }}"
                                href="{{ route('projess.ai.validasi-dokumen') }}">Review Dokumen</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fs-5 {{ Request::is('projess/riwayat-review*') ? 'active' : '' }}"
                                href="{{ route('projess.ai.riwayat.index') }}">Riwayat Review</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fs-5 {{ Request::is('projess/dashboard*') ? 'active' : '' }}"
                                href="{{ route('projess.home') }}">Projess Dashboard</a>
                        </li>
                    </ul>
                @endif

                <!-- Progress Bulat di Kanan -->
                <div class="d-flex align-items-center">
                    <div id="progress-circle" class="position-relative" style="--progress: 50%;">
                        <span>5</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</nav>
