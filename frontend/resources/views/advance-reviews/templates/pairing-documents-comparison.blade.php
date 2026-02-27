@extends('advance-reviews.layouts.app')

@section('content')
<div class="pairing-documents-container" style="background: #f8f9fa; min-height: 100vh;">
    <!-- Header Section -->
    <div class="header-section" style="background: white; border-bottom: 1px solid #e5e7eb; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div class="container-fluid px-5">
            <div class="row align-items-center mb-4">
                <div class="col-auto">
                    <a href="{{ route('projess.tickets.advance-reviews', ['ticketNumber' => $ticket->ticket_number]) }}" 
                       class="btn btn-light" 
                       style="border-radius: 12px; padding: 10px 16px; border: 1px solid #e5e7eb; display: flex; align-items: center; gap: 8px; transition: all 0.3s ease;"
                       onmouseover="this.style.background='#f3f4f6'; this.style.borderColor='#d1d5db';"
                       onmouseout="this.style.background='white'; this.style.borderColor='#e5e7eb';">
                        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back to Overview
                    </a>
                </div>
                <div class="col">
                    <h1 class="mb-2 fw-bold" style="font-size: 1.75rem; color: #6b7280; letter-spacing: -0.5px;">
                        Document Comparison
                    </h1>
                    <p class="mb-0 text-muted" style="font-size: 0.95rem;">
                        <span class="badge bg-light text-dark" style="padding: 6px 12px; border-radius: 20px;">{{ $ticket->ticket_number }}</span>
                        <span class="ms-2">{{ $ticket->project_title }}</span>
                    </p>
                </div>
                <div class="col-auto text-end">
                    <div style="background: #f3f4f6; border-radius: 12px; padding: 16px; text-align: center;">
                        <p class="mb-1" style="font-size: 0.875rem; color: #6b7280; font-weight: 600;">Company</p>
                        <p class="mb-0" style="font-size: 1rem; color: #374151; font-weight: 600;">{{ $ticket->company->name }}</p>
                    </div>
                </div>
            </div>

            <!-- Documents Info Bar -->
            <div class="row g-3 align-items-center">
                <div class="col">
                    <div style="background: #e5e7eb; border-radius: 12px; padding: 16px; color: #1f2937;">
                        <p class="mb-1" style="font-size: 0.875rem; opacity: 0.9; font-weight: 500;">Document 1</p>
                        <h3 class="mb-0" style="font-size: 1.125rem; font-weight: 700;">{{ $doc1->type }}</h3>
                        <p class="mb-0 mt-1" style="font-size: 0.8rem; opacity: 0.85;">{{ $doc1->filename }}</p>
                    </div>
                </div>

                <div class="col-auto" style="text-align: center;">
                    <div style="width: 48px; height: 48px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <svg width="24" height="24" fill="none" stroke="#6b7280" stroke-width="2" viewBox="0 0 24 24">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </div>
                </div>

                <div class="col">
                    <div style="background: #d1d5db; border-radius: 12px; padding: 16px; color: #1f2937;">
                        <p class="mb-1" style="font-size: 0.875rem; opacity: 0.9; font-weight: 500;">Document 2</p>
                        <h3 class="mb-0" style="font-size: 1.125rem; font-weight: 700;">{{ $doc2->type }}</h3>
                        <p class="mb-0 mt-1" style="font-size: 0.8rem; opacity: 0.85;">{{ $doc2->filename }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" style="padding: 28px; max-width: 1800px; margin: 0 auto;">
        <!-- Controls Bar -->
        <div class="controls-bar" style="background: white; border-radius: 16px; padding: 16px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <button id="changeSizeDown" class="btn btn-sm" 
                    style="border: 1px solid #e5e7eb; background: white; color: #374151; border-radius: 8px; padding: 8px 12px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.3s ease;"
                    onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#d1d5db';"
                    onmouseout="this.style.background='white'; this.style.borderColor='#e5e7eb';">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                </svg>
                Zoom Out
            </button>

            <button id="changeSizeUp" class="btn btn-sm" 
                    style="border: 1px solid #e5e7eb; background: white; color: #374151; border-radius: 8px; padding: 8px 12px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.3s ease;"
                    onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#d1d5db';"
                    onmouseout="this.style.background='white'; this.style.borderColor='#e5e7eb';">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="8" y1="12" x2="16" y2="12"></line>
                    <line x1="12" y1="8" x2="12" y2="16"></line>
                </svg>
                Zoom In
            </button>

            <button id="resetZoom" class="btn btn-sm" 
                    style="border: 1px solid #e5e7eb; background: white; color: #374151; border-radius: 8px; padding: 8px 12px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: all 0.3s ease;"
                    onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#d1d5db';"
                    onmouseout="this.style.background='white'; this.style.borderColor='#e5e7eb';">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"></path>
                    <path d="M21 3v5h-5"></path>
                </svg>
                Reset
            </button>

            <div class="ms-auto" style="display: flex; align-items: center; gap: 16px;">
                <div style="font-size: 0.875rem; color: #6b7280;">
                    Zoom: <span id="zoomPercentage" style="font-weight: 600; color: #374151;">100%</span>
                </div>

                <button id="downloadBoth" class="btn btn-sm" 
                        style="border: none; background: #4b5563; color: white; border-radius: 8px; padding: 8px 16px; cursor: pointer; display: flex; align-items: center; gap: 6px; font-weight: 600; transition: all 0.3s ease;"
                        onmouseover="this.style.background='#3a4452'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(75, 85, 99, 0.4)';"
                        onmouseout="this.style.background='#4b5563'; this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="7 10 12 15 17 10"></polyline>
                        <line x1="12" y1="15" x2="12" y2="3"></line>
                    </svg>
                    Download Both
                </button>
            </div>
        </div>

        <!-- PDF Comparison Container -->
        <div class="pdf-comparison-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; height: calc(100vh - 380px); min-height: 600px;">
            <!-- Document 1 -->
            <div class="pdf-viewer-wrapper" 
                 style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.1); display: flex; flex-direction: column; border: 2px solid #f3f4f6;">
                
                <div style="background: #e5e7eb; color: #1f2937; padding: 16px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 class="mb-1 fw-bold" style="font-size: 1rem;">Document 1</h4>
                        <p class="mb-0" style="font-size: 0.8rem; opacity: 0.9;">{{ $doc1->filename }}</p>
                    </div>
                    <a href="{{ $doc1->url }}" download class="btn btn-light btn-sm" style="border-radius: 6px; padding: 6px 12px; display: flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Download
                    </a>
                </div>

                <div id="pdf-container-1" class="pdf-viewer-container" 
                     style="flex: 1; overflow: auto; background: #f3f4f6; display: flex; align-items: center; justify-content: center; position: relative;">
                    <!-- PDF will be rendered here by PDFjs -->
                </div>
            </div>

            <!-- Document 2 -->
            <div class="pdf-viewer-wrapper" 
                 style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.1); display: flex; flex-direction: column; border: 2px solid #f3f4f6;">
                
                <div style="background: #d1d5db; color: #1f2937; padding: 16px; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h4 class="mb-1 fw-bold" style="font-size: 1rem;">Document 2</h4>
                        <p class="mb-0" style="font-size: 0.8rem; opacity: 0.9;">{{ $doc2->filename }}</p>
                    </div>
                    <a href="{{ $doc2->url }}" download class="btn btn-light btn-sm" style="border-radius: 6px; padding: 6px 12px; display: flex; align-items: center; gap: 6px;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="7 10 12 15 17 10"></polyline>
                            <line x1="12" y1="15" x2="12" y2="3"></line>
                        </svg>
                        Download
                    </a>
                </div>

                <div id="pdf-container-2" class="pdf-viewer-container" 
                     style="flex: 1; overflow: auto; background: #f3f4f6; display: flex; align-items: center; justify-content: center; position: relative;">
                    <!-- PDF will be rendered here by PDFjs -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden data attributes for JS -->
<script type="application/json" id="pairingDocumentsData">
{
    "doc1_url": "{{ $doc1->url }}",
    "doc2_url": "{{ $doc2->url }}",
    "ticket_number": "{{ $ticket->ticket_number }}"
}
</script>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pairing-documents.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf_viewer.min.css">
@endpush

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf_viewer.min.js"></script>
    <script src="{{ asset('js/pairing-documents.js') }}"></script>
@endpush
