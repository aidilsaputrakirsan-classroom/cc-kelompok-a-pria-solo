/**
 * Landing Page - Review Type Selection
 * Handles interaction for Basic and Advanced Review cards
 */

// Move modals to body level IMMEDIATELY (before DOMContentLoaded)
// This ensures they're moved before Bootstrap initializes them
(function() {
    function moveModalsToBody() {
        const modalIds = ['uploadModalAdvanced', 'successModal', 'errorModal'];
        
        modalIds.forEach(function(modalId) {
            const modal = document.getElementById(modalId);
            if (modal && modal.parentElement !== document.body) {
                // Move directly (don't clone - preserves event listeners and data attributes)
                document.body.appendChild(modal);
            }
        });
    }

    // Try immediately
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', moveModalsToBody);
    } else {
        moveModalsToBody();
    }
    
    // Also try after a short delay as fallback
    setTimeout(moveModalsToBody, 50);
    setTimeout(moveModalsToBody, 200);
})();

document.addEventListener('DOMContentLoaded', function() {

    // ============================================
    // ADVANCED REVIEW CARD - Coming Soon Notification
    // ============================================
    const advancedReviewCards = document.querySelectorAll('.review-type-card.position-relative');
    
    advancedReviewCards.forEach(function(card) {
        card.addEventListener('click', function(e) {
            // Only show notification if not clicking the disabled button
            if (!e.target.closest('.card-footer button')) {
                showComingSoonNotification();
            }
        });
    });

    // ============================================
    // SHOW COMING SOON NOTIFICATION
    // ============================================
    function showComingSoonNotification() {
        // Check if toast already exists
        if (document.querySelector('.coming-soon-toast')) {
            return;
        }

        const toastHTML = `
            <div class="position-fixed top-0 end-0 p-3 coming-soon-toast" style="z-index: 11">
                <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header">
                        <i class="bi bi-info-circle-fill text-info me-2"></i>
                        <strong class="me-auto">Coming Soon</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        Fitur Advanced Review akan segera tersedia. Gunakan Basic Review untuk saat ini.
                    </div>
                </div>
            </div>
        `;
        
        // Insert toast into body
        document.body.insertAdjacentHTML('beforeend', toastHTML);
        
        // Auto remove after 3 seconds
        setTimeout(function() {
            const toastElement = document.querySelector('.coming-soon-toast');
            if (toastElement) {
                toastElement.remove();
            }
        }, 3000);

        // Handle manual close button
        const closeButton = document.querySelector('.coming-soon-toast .btn-close');
        if (closeButton) {
            closeButton.addEventListener('click', function() {
                const toastElement = document.querySelector('.coming-soon-toast');
                if (toastElement) {
                    toastElement.remove();
                }
            });
        }
    }

    // ============================================
    // OPTIONAL: Add hover effect tracking (for analytics)
    // ============================================
    const allReviewCards = document.querySelectorAll('.review-type-card');
    
    allReviewCards.forEach(function(card) {
        card.addEventListener('mouseenter', function() {
            const cardType = this.id === 'basicReviewCard' ? 'basic' : 'advanced';
            console.log(`User hovering over ${cardType} review card`);
            // You can send analytics here if needed
        });
    });

    // ============================================
    // OPTIONAL: Keyboard accessibility
    // ============================================
    const basicReviewCard = document.getElementById('basicReviewCard');
    if (basicReviewCard) {
        basicReviewCard.setAttribute('tabindex', '0');
        basicReviewCard.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    }
});