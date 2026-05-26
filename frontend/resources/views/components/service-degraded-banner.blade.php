{{-- Modul 13: banner when document-service is degraded or unavailable --}}
<div id="service-degraded-banner"
    class="alert alert-warning alert-dismissible fade show d-none mb-0 rounded-0 border-0"
    role="alert"
    style="position: sticky; top: 0; z-index: 1050;">
    <div class="container-fluid d-flex flex-wrap align-items-center justify-content-between gap-2">
        <span>
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Some features are temporarily unavailable.</strong>
            Document processing may be limited. Read-only features may still work.
        </span>
        <button type="button" class="btn btn-sm btn-outline-dark" id="service-retry-btn">
            <i class="fas fa-redo me-1"></i> Retry
        </button>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
