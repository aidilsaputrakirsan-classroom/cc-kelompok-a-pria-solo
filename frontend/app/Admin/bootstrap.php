<?php

/**
 * Open-admin - admin builder based on Laravel.
 * @author z-song <https://github.com/z-song>
 *
 * Bootstraper for Admin.
 *
 * Here you can remove builtin form field:
 * OpenAdmin\Admin\Form::forget(['map', 'editor']);
 *
 * Or extend custom form field:
 * OpenAdmin\Admin\Form::extend('php', PHPEditor::class);
 *
 * Or require js and css assets:
 * Admin::css('/packages/prettydocs/css/styles.css');
 * Admin::js('/packages/prettydocs/js/main.js');
 *
 */

OpenAdmin\Admin\Form::forget(['editor']);

// Force full page refresh for advance-reviews pages to ensure JS/CSS load properly
// This intercepts clicks on menu links containing 'validasi-dokumen' or 'test-hello'
// and forces a hard page refresh instead of opening in new tab
OpenAdmin\Admin\Admin::script("
(function() {
    /**
     * Check if a URL should trigger a forced refresh
     */
    function shouldForceRefresh(href) {
        if (!href) return false;
        const url = href.toLowerCase();
        return url.includes('validasi-dokumen') || 
               url.includes('test-hello') ||
               url.includes('advance-reviews') ||
               url.includes('advance-result') ||
               url.includes('basic-result') ||
               url.includes('validate-ground-truth') ||
               url.includes('riwayat-review');
    }
    
    /**
     * Force navigation with cache-busting to ensure fresh page load
     */
    function forcePageRefresh(url) {
        // Parse URL to add/update cache-busting parameter
        const urlObj = new URL(url, window.location.origin);
        urlObj.searchParams.set('_refresh', Date.now());
        
        // Force full page navigation (bypasses any AJAX/SPA navigation)
        window.location.href = urlObj.toString();
    }
    
    /**
     * Setup click interceptors for menu links
     */
    function setupClickInterceptors() {
        const menuLinks = document.querySelectorAll('#menu a, .custom-menu a, aside nav a');
        menuLinks.forEach(function(link) {
            const href = link.getAttribute('href') || '';
            if (shouldForceRefresh(href)) {
                link.removeAttribute('target');
                link.classList.add('force-refresh-link');
            }
        });
    }
    
    /**
     * Initialize interceptors
     */
    function init() {
        setupClickInterceptors();
        // Retry after delays to catch dynamically loaded menus
        setTimeout(setupClickInterceptors, 100);
        setTimeout(setupClickInterceptors, 500);
        setTimeout(setupClickInterceptors, 1000);
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Watch for dynamically added menu items
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function(mutations) {
            setupClickInterceptors();
        });
        const menuContainer = document.getElementById('menu');
        if (menuContainer) {
            observer.observe(menuContainer, { 
                childList: true, 
                subtree: true,
                attributes: true,
                attributeFilter: ['href']
            });
        }
    }
    
    // Intercept clicks on advance-reviews links
    document.addEventListener('click', function(event) {
        const link = event.target.closest('a');
        if (!link) return;
        
        const href = link.getAttribute('href');
        if (!shouldForceRefresh(href)) return;
        
        // Prevent default navigation
        event.preventDefault();
        event.stopPropagation();
        
        // Force full page refresh with cache-busting
        forcePageRefresh(href);
    }, true);
})();
");