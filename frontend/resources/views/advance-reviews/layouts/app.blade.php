@php
    // Detect if we're in OpenAdmin context
    $isOpenAdmin = isset($isOpenAdmin) ? $isOpenAdmin : false;
    $currentRoute = Request::path();
    $isResultPage = str_contains($currentRoute, '/result') || str_contains($currentRoute, '/advance-result/');
@endphp

@if (!$isOpenAdmin)
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Komo AI')</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Google Fonts Poppins --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Google Fonts Inter --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Bootstrap CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    {{-- CSS Files --}}
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/navbar.css') }}">

    {{-- Scoped styles for advance-review content to prevent OpenAdmin conflicts --}}
    <style>
        /* Scoped styles for advance-review main content */
        #advance-review-main-content.advance-review-main-content {
            /* Reset any OpenAdmin styles that might interfere */
            position: relative;
            z-index: 1;
        }
        
        /* Ensure container styles only apply within advance-review content */
        #advance-review-main-content .container {
            margin-left: auto;
            margin-right: auto;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        /* Prevent OpenAdmin padding from affecting our content */
        #advance-review-main-content {
            padding: 0 !important;
        }
    </style>

    @stack('styles')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>

</head>
@endif

{{-- Navbar Section -- Only show when NOT in OpenAdmin context (navbar is included in content when in OpenAdmin) --}}
@if (!$isOpenAdmin)
    @if ($isResultPage)
        {{-- Navbar Dinamis: Untuk halaman RESULT saja --}}
        @include('advance-reviews.partials.navbar', [
            'ticketNumber' => $ticketNumber ?? ($ticket->ticket_number ?? request()->route('ticket')),
        ])
    @else
        {{-- Navbar Default: Untuk Overview dan halaman lainnya --}}
        @include('advance-reviews.partials.navbar')
    @endif
@endif

{{-- Main Content --}}
@if (Request::is('projess/validasi-dokumen*') || Request::is('projess/ekstrak-dokumen*'))
    {{-- Halaman full background --}}
    <main id="advance-review-main-content" class="advance-review-main-content main-content">
        @yield('content')
    </main>
@else
    {{-- Halaman dengan container --}}
    <main id="advance-review-main-content" class="advance-review-main-content main-content">
        <div class="mx-6">
            @yield('content')
        </div>
    </main>
@endif

@if (!$isOpenAdmin)
<!-- Bootstrap JS Bundle (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

@stack('scripts')

</html>
@endif
