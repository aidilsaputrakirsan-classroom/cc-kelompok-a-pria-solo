@extends('advance-reviews.layouts.app')

@section('content')
<div>
    {{-- Container dengan data attributes untuk notes --}}
    <div class="container-fluid px-5 py-5 mb-5" style="background: #f8f9fa; border-radius: 35px;"
        data-ticket-number="{{ $ticket->ticket_number }}" data-project-title="{{ $ticket->project_title ?? '' }}"
        data-company-name="{{ $ticket->company->name ?? '' }}" data-contract-value="{{ $ticket->groundTruth->dpp ?? '' }}">

        <!-- Header -->
        <div class="mb-5" style="padding: 30px; background: #ffffff; border-radius: 30px;">
            <h1 class="display-5 fw-bold text-dark mb-3">{{ $ticket->ticket_number }}</h1>
            <p class="text-muted fs-5">
                @if ($ticket->project_title)
                    <span class="text-dark">{{ $ticket->project_title }}</span>
                @endif
            </p>
            <div class="d-flex gap-3">
                <button data-bs-toggle="modal" data-bs-target="#missingDocsModal"
                    style="padding: 12px 28px;
                    border: none;
                    border-radius: 50px;
                    font-weight: 600;
                    font-size: 0.95rem;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    letter-spacing: 0.3px;
                    background: white;
                    color: #1f2937;"
                    onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#d1d5db'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.15)';"
                    onmouseout="this.style.background='white'; this.style.borderColor='#e5e7eb'; this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.1)';"
                    onmousedown="this.style.transform='translateY(0)';">
                    Dokumen Belum Lengkap
                </button>
                <button id="pairingDocsBtn" data-bs-toggle="modal" data-bs-target="#pairingDocsModal"
                    style="padding: 12px 28px;
                    border: none;
                    border-radius: 50px;
                    font-weight: 600;
                    font-size: 0.95rem;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                    letter-spacing: 0.3px;
                    background: white;
                    color: #1f2937;"
                    onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#d1d5db'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.15)';"
                    onmouseout="this.style.background='white'; this.style.borderColor='#e5e7eb'; this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.1)';"
                    onmousedown="this.style.transform='translateY(0)';">
                    Pairing Documents
                </button>
            </div>
        </div>

        <!-- Cards Grid -->
        @if ($reviewResults->count() > 0)
            <div class="row g-4">
                @foreach ($reviewResults as $index => $review)
                    @php
                        // Check if this is Basic Review card
                        $isBasicReview = isset($review->is_basic_review) && $review->is_basic_review;

                        // Helper function to count issues from review_data
                        $countIssues = function($reviewData) {
                            if (empty($reviewData) || (!is_array($reviewData) && !is_object($reviewData))) {
                                return 0;
                            }
                            $totalIssues = 0;
                            foreach ($reviewData as $stageName => $stageData) {
                                if (isset($stageData['issues']) && (is_array($stageData['issues']) || is_object($stageData['issues']))) {
                                    $totalIssues += count((array)$stageData['issues']);
                                }
                            }
                            return $totalIssues;
                        };

                        // Helper function to count stages from review_data
                        $countStages = function($reviewData) {
                            if (empty($reviewData) || (!is_array($reviewData) && !is_object($reviewData))) {
                                return 0;
                            }
                            return count((array)$reviewData);
                        };

                        // Calculate total issues from typo, date, and price counts
                        $typoCount = $review->typo_count ?? 0;
                        $dateCount = $review->date_count ?? 0;
                        $priceCount = $review->price_count ?? 0;
                        $totalIssuesFromCounts = $typoCount + $dateCount + $priceCount;

                        if ($isBasicReview) {
                            // Basic Review card
                            $detailUrl = route('projess.ai.basic-result.show', ['ticket' => $ticket->ticket_number]);
                            $cardTitle = 'Basic Review';
                            $cardSubtitle = sprintf(
                                '%d Typo · %d Date · %d Price',
                                $typoCount,
                                $dateCount,
                                $priceCount,
                            );
                            $cardIcon = 'basic'; // Flag untuk icon
                            $issueCount = $totalIssuesFromCounts; // Use total from typo, date, price counts
                            $stageCount = 0; // Basic review doesn't have stages
                        } else {
                            // Advance Review card
                            $detailUrl = route('projess.ai.advance-result.show', [
                                'ticketNumber' => $ticket->ticket_number,
                                'docType' => $review->doc_type,
                            ]);
                            $cardTitle = $review->doc_type;
                            
                            // Extract errors_count from review_data - query from database
                            $reviewData = null;
                            $errorsCount = 0;
                            try {
                                // Try to get from object first (if it's an Eloquent model)
                                if (isset($review->review_data)) {
                                    $reviewData = $review->review_data;
                                } elseif (is_object($review) && method_exists($review, 'getAttribute')) {
                                    $reviewData = $review->getAttribute('review_data');
                                }
                                
                                // If still null, query database directly using doc_type (which corresponds to pdfFilename)
                                if ($reviewData === null && isset($ticket->ticket_number)) {
                                    $reviewResult = \App\Models\AdvanceReviewResult::whereHas('groundTruth', function ($query) use ($ticket) {
                                        $query->whereHas('ticket', function ($q) use ($ticket) {
                                            $q->where('ticket_number', $ticket->ticket_number);
                                        });
                                    })
                                    ->where('doc_type', $review->doc_type)
                                    ->first();
                                    $reviewData = $reviewResult ? $reviewResult->review_data : null;
                                }
                                
                                // Extract errors_count from review_data structure
                                if (!empty($reviewData) && (is_array($reviewData) || is_object($reviewData))) {
                                    // Check if review_data contains advance_review structure
                                    if (isset($reviewData['advance_review']) && is_array($reviewData['advance_review'])) {
                                        $advanceReview = $reviewData['advance_review'];
                                        $docTypeUpper = strtoupper($review->doc_type);
                                        
                                        // Try uppercase doc_type first
                                        if (isset($advanceReview[$docTypeUpper]) && is_array($advanceReview[$docTypeUpper])) {
                                            $docReview = $advanceReview[$docTypeUpper];
                                            if (isset($docReview['review_data']['errors_count'])) {
                                                $errorsCount = (int) $docReview['review_data']['errors_count'];
                                            } elseif (isset($docReview['review_result']['errors_count'])) {
                                                $errorsCount = (int) $docReview['review_result']['errors_count'];
                                            }
                                        }
                                        // Try original case doc_type
                                        if ($errorsCount === 0 && isset($advanceReview[$review->doc_type]) && is_array($advanceReview[$review->doc_type])) {
                                            $docReview = $advanceReview[$review->doc_type];
                                            if (isset($docReview['review_data']['errors_count'])) {
                                                $errorsCount = (int) $docReview['review_data']['errors_count'];
                                            } elseif (isset($docReview['review_result']['errors_count'])) {
                                                $errorsCount = (int) $docReview['review_result']['errors_count'];
                                            }
                                        }
                                    }
                                    // Check if review_data has nested review_data or review_result
                                    elseif (isset($reviewData['review_data']) && is_array($reviewData['review_data']) && isset($reviewData['review_data']['errors_count'])) {
                                        $errorsCount = (int) $reviewData['review_data']['errors_count'];
                                    } elseif (isset($reviewData['review_result']) && is_array($reviewData['review_result']) && isset($reviewData['review_result']['errors_count'])) {
                                        $errorsCount = (int) $reviewData['review_result']['errors_count'];
                                    }
                                    // Check if review_data is the issues structure directly (with errors_count at root level)
                                    elseif (isset($reviewData['errors_count'])) {
                                        $errorsCount = (int) $reviewData['errors_count'];
                                    }
                                }
                            } catch (\Exception $e) {
                                // If query fails, reviewData remains null and errorsCount stays 0
                                $reviewData = null;
                                $errorsCount = 0;
                            }
                            
                            // Build subtitle with errors_count
                            $cardSubtitle = sprintf(
                                '%d Notes',
                                $errorsCount
                            );
                            $cardIcon = 'document';
                            // Use errors_count from review_data
                            $issueCount = $errorsCount;
                        }
                    @endphp

                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <a href="{{ $detailUrl }}" style="text-decoration: none;">
                            <div class="modern-doc-card"
                                style="background: #1a1a1a; 
                                    border-radius: 24px; 
                                    padding: 32px 28px; 
                                    position: relative;
                                    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
                                    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                                    min-height: 240px;
                                    display: flex;
                                    flex-direction: column;
                                    justify-content: space-between;
                                    cursor: pointer;
                                    overflow: hidden;"
                                onmouseover="this.style.transform='translateY(-8px) scale(1.02)'; this.style.boxShadow='0 20px 40px rgba(0,0,0,0.25)';"
                                onmouseout="this.style.transform='translateY(0) scale(1)'; this.style.boxShadow='0 4px 16px rgba(0,0,0,0.12)';">

                                <!-- Background decoration -->
                                <div
                                    style="position: absolute; top: -20px; right: -20px; width: 120px; height: 120px; background: rgba(255,255,255,0.03); border-radius: 50%; z-index: 0;">
                                </div>

                                <!-- Icon -->
                                <div style="position: relative; z-index: 1; margin-bottom: 24px;">
                                    <div
                                        style="width: 48px; height: 48px; background: rgba(255,255,255,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px);">
                                        @if ($cardIcon === 'basic')
                                            {{-- Icon untuk Basic Review --}}
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="#ffffff" stroke-width="2">
                                                <path d="M9 11l3 3L22 4"></path>
                                                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
                                            </svg>
                                        @else
                                            {{-- Icon untuk Advance Review --}}
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                stroke="#ffffff" stroke-width="2">
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                                <polyline points="14 2 14 8 20 8"></polyline>
                                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                                <polyline points="10 9 9 9 8 9"></polyline>
                                            </svg>
                                        @endif
                                    </div>
                                </div>

                                <!-- Title & Subtitle -->
                                <div style="position: relative; z-index: 1;">
                                    <h3 class="fw-bold mb-3"
                                        style="font-size: 1.5rem; color: #ffffff; line-height: 1.3; letter-spacing: -0.5px;">
                                        {{ $cardTitle }}
                                    </h3>
                                    <p class="mb-0"
                                        style="font-size: 0.875rem; color: rgba(255,255,255,0.6); font-weight: 500;">
                                        {{ $cardSubtitle }}
                                    </p>
                                </div>

                                <!-- Date & Arrow -->
                                <div
                                    style="position: relative; z-index: 1; margin-top: 28px; display: flex; justify-content: space-between; align-items: center;">
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="rgba(255,255,255,0.5)" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2">
                                            </rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                        <span style="font-size: 0.8rem; color: rgba(255,255,255,0.7); font-weight: 500;">
                                            {{ $review->created_at->format('d M Y') }}
                                        </span>
                                    </div>
                                    <div class="arrow-icon"
                                        style="width: 32px; height: 32px; background: rgba(255,255,255,0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                            stroke="#ffffff" stroke-width="2">
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                            <polyline points="12 5 19 12 12 19"></polyline>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <div class="position-absolute" style="top: 20px; right: 20px; z-index: 2;">
                                    @if ($issueCount > 0)
                                        <span class="badge"
                                            style="background: #ef4444; color: white; font-size: 0.7rem; padding: 6px 12px; border-radius: 20px; font-weight: 600; letter-spacing: 0.3px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                            {{ $issueCount }} Issue{{ $issueCount > 1 ? 's' : '' }}
                                        </span>
                                    @else
                                        {{-- If no issues (no review_data/stages), always show Completed regardless of status field --}}
                                        <span class="badge"
                                            style="background: #10b981; color: white; font-size: 0.7rem; padding: 6px 12px; border-radius: 20px; font-weight: 600; letter-spacing: 0.3px; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
                                            Completed
                                        </span>
                                    @endif
                                </div>

                                <!-- Progress bar -->
                                <div class="progress-bar"
                                    style="position: absolute; bottom: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); transform: scaleX(0); transition: transform 0.4s ease; transform-origin: left;">
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <div class="mb-4">
                    <svg class="mx-auto" width="120" height="120" fill="none" stroke="currentColor"
                        stroke-width="2" viewBox="0 0 24 24" style="color: #cbd5e0;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="fw-bold text-dark mb-2" style="font-size: 1.5rem;">No Review Results Found</h3>
                <p class="text-muted" style="font-size: 1rem;">
                    There are no advance review results for this ticket yet.
                </p>
            </div>
        @endif
    </div>

    <!-- Modal for Pairing Documents Selection -->
    <div class="modal fade" id="pairingDocsModal" tabindex="-1" aria-labelledby="pairingDocsModalLabel"
        aria-hidden="true" data-bs-backdrop="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content"
                style="border-radius: 16px; border: 1px solid #e5e7eb; overflow: hidden; background: white; box-shadow: 0 4px 16px rgba(0,0,0,0.12);">

                <!-- Header -->
                <div class="modal-header"
                    style="background: white; border-bottom: 1px solid #e5e7eb; padding: 28px 24px;">
                    <div>
                        <h5 class="modal-title fw-bold mb-1" id="pairingDocsModalLabel"
                            style="font-size: 1.25rem; color: #374151; letter-spacing: -0.3px; display: flex; align-items: center; gap: 12px;">
                            <svg width="24" height="24" fill="none" stroke="#6b7280" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2" />
                            </svg>
                            Select Documents for Comparison
                        </h5>
                        <p class="mb-0" style="color: #9ca3af; font-size: 0.875rem;">Choose two documents to compare side-by-side</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        style="background: #f3f4f6; border: none; border-radius: 12px; width: 40px; height: 40px; opacity: 1; transition: all 0.3s ease;"
                        onmouseover="this.style.background='#e5e7eb'; this.style.transform='rotate(90deg)';"
                        onmouseout="this.style.background='#f3f4f6'; this.style.transform='rotate(0deg)';">
                        <svg width="16" height="16" fill="none" stroke="#6b7280" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="modal-body" style="padding: 24px; background: #fafafa;">
                    <!-- Loading State -->
                    <div id="pairingDocsLoading" style="display: none; text-align: center; padding: 32px;">
                        <div style="display: inline-block;">
                            <div class="spinner-border" role="status" style="color: #9ca3af;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <p class="mt-3" style="color: #6b7280; font-size: 0.95rem;">Loading available documents...</p>
                    </div>

                    <!-- Documents List (Hidden by default) -->
                    <div id="pairingDocsList" style="display: none;">
                        <!-- Selection UI -->
                        <div style="margin-bottom: 24px;">
                            <div class="row g-3">
                                <!-- Document 1 Selection -->
                                <div class="col-md-6">
                                    <label style="display: block; font-weight: 700; color: #374151; font-size: 0.875rem; margin-bottom: 12px;">
                                        Document 1
                                        <span style="color: #ef4444;">*</span>
                                    </label>
                                    <select id="doc1Select" class="form-select"
                                        style="border-radius: 10px; border: 2px solid #e5e7eb; padding: 10px 12px; font-size: 0.95rem; transition: all 0.3s ease; color: #374151; background-color: white;"
                                        onchange="updatePairingSelection()"
                                        onfocus="this.style.borderColor='#9ca3af'; this.style.boxShadow='0 0 0 3px rgba(156, 163, 175, 0.1)';"
                                        onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                                        <option value="" disabled selected style="color: #d1d5db;">Select a document...</option>
                                    </select>
                                </div>

                                <!-- Document 2 Selection -->
                                <div class="col-md-6">
                                    <label style="display: block; font-weight: 700; color: #374151; font-size: 0.875rem; margin-bottom: 12px;">
                                        Document 2
                                        <span style="color: #ef4444;">*</span>
                                    </label>
                                    <select id="doc2Select" class="form-select"
                                        style="border-radius: 10px; border: 2px solid #e5e7eb; padding: 10px 12px; font-size: 0.95rem; transition: all 0.3s ease; color: #374151; background-color: white;"
                                        onchange="updatePairingSelection()"
                                        onfocus="this.style.borderColor='#9ca3af'; this.style.boxShadow='0 0 0 3px rgba(156, 163, 175, 0.1)';"
                                        onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none';">
                                        <option value="" disabled selected style="color: #d1d5db;">Select a document...</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Selection Info -->
                            <div id="selectionInfo" style="display: none; margin-top: 16px; background: white; border-radius: 12px; padding: 14px; border: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <svg width="18" height="18" fill="none" stroke="#10b981" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span style="color: #10b981; font-weight: 600; font-size: 0.875rem;">Both documents selected. Ready to compare!</span>
                                </div>
                            </div>
                        </div>

                        <!-- Documents Grid -->
                        <div id="documentsGrid" style="margin-bottom: 24px;">
                            <!-- Documents will be loaded here dynamically -->
                        </div>
                    </div>

                    <!-- Error State -->
                    <div id="pairingDocsError" style="display: none; background: #fee2e2; border: 1px solid #fecaca; border-radius: 12px; padding: 16px; text-align: center; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
                        <svg width="24" height="24" fill="none" stroke="#dc2626" stroke-width="2" 
                             viewBox="0 0 24 24" style="margin: 0 auto 8px; display: block;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <p id="pairingDocsErrorText" style="color: #dc2626; margin: 0; font-size: 0.875rem;">
                            Failed to load documents
                        </p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer" style="background: white; border-top: 1px solid #e5e7eb; padding: 16px 24px; display: flex; gap: 12px;">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"
                        style="border-radius: 10px; padding: 10px 20px; border: 1px solid #e5e7eb; background: white; color: #374151; transition: all 0.3s ease;"
                        onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#d1d5db';"
                        onmouseout="this.style.background='white'; this.style.borderColor='#e5e7eb';">
                        Cancel
                    </button>
                    <button type="button" id="compareBtnModal" class="btn btn-primary" 
                        style="border-radius: 10px; padding: 10px 20px; background: #4b5563; border: none; color: white; font-weight: 600; cursor: pointer; display: none; transition: all 0.3s ease;"
                        onmouseover="this.style.background='#3a4452'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(75, 85, 99, 0.4)';"
                        onmouseout="this.style.background='#4b5563'; this.style.transform='translateY(0)'; this.style.boxShadow='none';"
                        onclick="proceedWithComparison()">
                        Compare Documents
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Missing Documents -->
    <div class="modal fade" id="missingDocsModal" tabindex="-1" aria-labelledby="missingDocsModalLabel"
        aria-hidden="true" data-bs-backdrop="false">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content"
                style="border-radius: 20px; border: 1px solid #e5e7eb; overflow: hidden; background: white; box-shadow: 0 4px 16px rgba(0,0,0,0.12);">

                <!-- Header -->
                <div class="modal-header"
                    style="background: white; border-bottom: 1px solid #e5e7eb; padding: 28px 24px;">
                    <div>
                        <h5 class="modal-title fw-bold mb-1" id="missingDocsModalLabel"
                            style="font-size: 1.25rem; color: #1a1a1a; letter-spacing: -0.3px; display: flex; align-items: center; gap: 12px;">
                            <svg width="24" height="24" fill="none" stroke="#1a1a1a" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Dokumen Belum Lengkap
                        </h5>
                        <p class="mb-0" style="color: #6b7280; font-size: 0.875rem;">Daftar dokumen yang masih
                            perlu dilengkapi</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        style="background: #f3f4f6; border: none; border-radius: 12px; width: 40px; height: 40px; opacity: 1; transition: all 0.3s ease;"
                        onmouseover="this.style.background='#e5e7eb'; this.style.transform='rotate(90deg)';"
                        onmouseout="this.style.background='#f3f4f6'; this.style.transform='rotate(0deg)';">
                        <svg width="16" height="16" fill="none" stroke="#6b7280" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"> <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="modal-body" style="padding: 24px; background: #fafafa;">

                    @php
                        $requiredDocs = [
                            'PR',
                            'PO',
                            'GR',
                            'NPK',
                            'KB',
                            'BASO',
                            'BA SPLITTING',
                            'CHECKLIST OBL',
                            'KKP',
                            'SPB',
                            'INVOICE',
                            'KUITANSI',
                            'FAKTUR PAJAK',
                            'ENOFA',
                            'BEBAS PPH',
                            'BAPLA',
                            'BAST',
                            'BAUT',
                            'BARD',
                            'LPL',
                            'WO',
                            'P8',
                            'SP',
                            'KL',
                        ];

                        $existingDocs = $reviewResults
                            ->filter(function ($review) {
                                return !isset($review->is_basic_review) || !$review->is_basic_review;
                            })
                            ->pluck('doc_type')
                            ->toArray();

                        $missingDocs = array_diff($requiredDocs, $existingDocs);
                        $messageText = 'Mohon untuk melengkapi dokumen ' . implode(', ', $missingDocs);
                    @endphp

                    @if (count($missingDocs) > 0)
                        <!-- Alert Warning -->
                        <div class="d-flex align-items-start mb-4"
                            style="background: white; border: 1px solid #e5e7eb; border-radius: 16px; padding: 16px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
                            <svg width="24" height="24" fill="none" stroke="#ef4444" stroke-width="2"
                                viewBox="0 0 24 24" style="flex-shrink: 0; margin-right: 12px; margin-top: 2px;">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div style="color: #4b5563; font-size: 0.875rem; line-height: 1.6;">
                                <strong style="color: #1f2937; font-weight: 600;">Perhatian!</strong> Terdapat
                                <strong style="color: #1f2937; font-weight: 600;">{{ count($missingDocs) }}</strong>
                                dokumen yang belum lengkap.
                            </div>
                        </div>

                        <!-- Textarea Container -->
                        <div
                            style="background: white; border-radius: 16px; border: 1px solid #e5e7eb; padding: 20px; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
                            <label class="form-label mb-3"
                                style="color: #1f2937; font-size: 0.875rem; font-weight: 700; margin: 0;">
                                Pesan untuk dikopi:
                            </label>

                            <div style="position: relative;">
                                <textarea id="missingDocsMessage" class="form-control" rows="3" readonly
                                    style="background: #fafafa; border: 2px solid #e5e7eb; border-radius: 10px; padding: 12px; font-size: 0.875rem; color: #1f2937; resize: none; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.5; transition: all 0.3s ease;">{{ $messageText }}</textarea>

                                <div class="d-flex mt-3" style="gap: 12px;">
                                    <!-- Tombol Salin Pesan -->
                                    <button onclick="copyToClipboard()"
                                        style="flex: 1; padding: 14px 20px; border: none; border-radius: 14px; font-weight: 600; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); letter-spacing: 0.2px; background: #1a1a1a; color: white; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);"
                                        onmouseover="this.style.background='#2a2a2a'; this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.2)';"
                                        onmouseout="this.style.background='#1a1a1a'; this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0, 0, 0, 0.15)';"
                                        onmousedown="this.style.transform='translateY(0)';">
                                        <svg width="16" height="16" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24" style="flex-shrink: 0;">
                                            <rect x="9" y="9" width="13" height="13" rx="2"
                                                ry="2"></rect>
                                            <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"></path>
                                        </svg>
                                        Salin Pesan
                                    </button>

                                    <!-- Tombol Tambah ke Notes -->
                                    <button onclick="handleAddMissingDocsToNotes()"
                                        style="flex: 1; padding: 14px 20px; border: 2px solid #e5e7eb; border-radius: 14px; font-weight: 600; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); letter-spacing: 0.2px; background: white; color: #1a1a1a;"
                                        onmouseover="this.style.background='#f9fafb'; this.style.borderColor='#d1d5db'; this.style.transform='translateY(-2px)';"
                                        onmouseout="this.style.background='white'; this.style.borderColor='#e5e7eb'; this.style.transform='translateY(0)';"
                                        onmousedown="this.style.transform='translateY(0)';">
                                        <svg width="16" height="16" fill="none" stroke="currentColor"
                                            stroke-width="2" viewBox="0 0 24 24" style="flex-shrink: 0;">
                                            <path d="M12 4v16m8-8H4"></path>
                                        </svg>
                                        Tambah ke Notes
                                    </button>
                                </div>

                                <small id="copyFeedback" class="fw-bold ms-3 align-self-center"
                                    style="display: none; color: #10b981; margin-top: 8px;">✓ Tersalin!</small>
                            </div>
                        </div>

                        <!-- Hidden JSON Data -->
                        <script type="application/json" id="missingDocsData">{!! json_encode(array_values($missingDocs)) !!}</script>
                        </script>
                    @else
                        <!-- Empty State -->
                        <div class="text-center py-4">
                            <div class="mb-3"
                                style="width: 80px; height: 80px; background: #d1fae5; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                <svg width="40" height="40" fill="none" stroke="#10b981" stroke-width="3"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h5 class="fw-bold mb-2" style="color: #1f2937; font-size: 1.125rem;">Semua Dokumen Lengkap!
                            </h5>
                            <p class="mb-0" style="color: #6b7280; font-size: 0.875rem;">Semua dokumen yang diperlukan
                                sudah tersedia.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Include Calculator Widget (Hidden by default) --}}
    @include('advance-reviews.partials.calculator', ['hiddenByDefault' => true])
    
    {{-- Include Notes Panel --}}
    @include('advance-reviews.partials.notes-panel')
</div>
<script>
    function copyToClipboard() {
        const textarea = document.getElementById('missingDocsMessage');
        const feedback = document.getElementById('copyFeedback');

        textarea.select();
        textarea.setSelectionRange(0, 99999);

        navigator.clipboard.writeText(textarea.value)
            .then(function() {
                feedback.style.display = 'inline';
                setTimeout(function() {
                    feedback.style.display = 'none';
                }, 2000);
            })
            .catch(function(err) {
                console.error('Gagal menyalin: ', err);
            });
    }

    // ✅ RENAME FUNCTION untuk menghindari conflict
    function handleAddMissingDocsToNotes() {
        console.log('🔍 [Blade] Starting handleAddMissingDocsToNotes');

        // 1. Get missing docs data
        const dataElement = document.getElementById('missingDocsData');
        if (!dataElement) {
            console.error('❌ [Blade] Data element not found!');
            alert('Data dokumen tidak ditemukan');
            return;
        }

        // 2. Parse JSON
        let missingDocs;
        try {
            const rawText = dataElement.textContent.trim();
            console.log('📄 [Blade] Raw text:', rawText);

            missingDocs = JSON.parse(rawText);
            console.log('✅ [Blade] Parsed data:', missingDocs);
            console.log('📊 [Blade] Array length:', missingDocs.length);
        } catch (error) {
            console.error('❌ [Blade] Parse error:', error);
            console.error('Raw content was:', dataElement.textContent);
            alert('Gagal membaca data dokumen: ' + error.message);
            return;
        }

        // 3. Validate
        if (!missingDocs) {
            console.error('❌ [Blade] missingDocs is null/undefined');
            alert('Data dokumen kosong');
            return;
        }

        if (!Array.isArray(missingDocs)) {
            console.error('❌ [Blade] Not an array:', typeof missingDocs);
            alert('Format data dokumen tidak valid');
            return;
        }

        if (missingDocs.length === 0) {
            console.warn('⚠️ [Blade] Array is empty');
            alert('Tidak ada dokumen yang perlu dilengkapi');
            return;
        }

        console.log('✅ [Blade] Validation passed!');
        console.log('📦 [Blade] Calling notes.js function with:', missingDocs);

        // 4. ✅ Call the CORRECT function from notes.js
        if (typeof window.addMissingDocsToNotes === 'function') {
            console.log('✅ [Blade] Function found, calling...');

            try {
                window.addMissingDocsToNotes(missingDocs);
                console.log('✅ [Blade] Function called successfully');

                // 5. Close modal after success
                setTimeout(() => {
                    const modalElement = document.getElementById('missingDocsModal');
                    if (modalElement) {
                        const modal = bootstrap.Modal.getInstance(modalElement);
                        if (modal) {
                            modal.hide();
                            console.log('✅ [Blade] Modal closed');
                        }
                    }
                }, 800);
            } catch (error) {
                console.error('❌ [Blade] Error calling function:', error);
                alert('Gagal menambahkan ke notes: ' + error.message);
            }
        } else {
            console.error('❌ [Blade] window.addMissingDocsToNotes not found!');
            console.log('Available window functions:', Object.keys(window).filter(k => k.includes('add')));
            alert('Fungsi notes tidak tersedia');
        }
    }

    // ========================================
    // ENHANCED CARD HOVER EFFECTS
    // ========================================
    // Use immediate execution for Pjax compatibility
    (function() {
        function init() {
            console.log('🔍 [Overview] Initializing card effects');

            // Card hover effects
            document.querySelectorAll('.modern-doc-card').forEach(card => {
                const arrow = card.querySelector('.arrow-icon');
                const progressBar = card.querySelector('.progress-bar');

                card.addEventListener('mouseenter', () => {
                    if (arrow) {
                        arrow.style.background = 'rgba(255,255,255,0.2)';
                        arrow.style.transform = 'translateX(4px)';
                    }
                    if (progressBar) {
                        progressBar.style.transform = 'scaleX(1)';
                    }
                });

                card.addEventListener('mouseleave', () => {
                    if (arrow) {
                        arrow.style.background = 'rgba(255,255,255,0.1)';
                        arrow.style.transform = 'translateX(0)';
                    }
                    if (progressBar) {
                        progressBar.style.transform = 'scaleX(0)';
                    }
                });
            });

            // Check hidden data
            const dataElement = document.getElementById('missingDocsData');
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
@endpush

@push('scripts')
    <script src="{{ asset('js/notes.js') }}"></script>
    <script src="{{ asset('js/pairing-documents-modal.js') }}"></script>
@endpush