/**
 * Open specific OpenAdmin menu items in new tab
 * This script finds menu links matching certain URIs and adds target="_blank"
 */
(function() {
    function setMenuTargetBlank() {
        // Find all menu links - try multiple selectors
        const selectors = ['#menu a', '.custom-menu a', 'aside nav a', '.sidebar-menu a'];
        let menuLinks = [];
        
        selectors.forEach(function(selector) {
            const links = document.querySelectorAll(selector);
            links.forEach(function(link) {
                if (menuLinks.indexOf(link) === -1) {
                    menuLinks.push(link);
                }
            });
        });
        
        menuLinks.forEach(function(link) {
            const href = link.getAttribute('href') || '';
            
            // Check if this link should open in a new tab
            // Match links containing 'validasi-dokumen' or 'test-hello' (backward compatibility route)
            if (href.includes('validasi-dokumen') || href.includes('test-hello')) {
                link.setAttribute('target', '_blank');
                // Also add a class to identify it
                link.classList.add('open-in-new-tab');
            }
        });
    }
    
    // Run multiple times with delays to catch menu when it's loaded
    function init() {
        setMenuTargetBlank();
        
        // Run again after a short delay to catch dynamically loaded menus
        setTimeout(setMenuTargetBlank, 100);
        setTimeout(setMenuTargetBlank, 500);
        setTimeout(setMenuTargetBlank, 1000);
    }
    
    // Run immediately if DOM is already loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Also run after OpenAdmin's menu initialization (in case menu is loaded via AJAX)
    // Use MutationObserver to watch for menu changes
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function(mutations) {
            setMenuTargetBlank();
        });
        
        const menuContainer = document.getElementById('menu');
        if (menuContainer) {
            observer.observe(menuContainer, {
                childList: true,
                subtree: true
            });
        }
        
        // Also observe the body for any menu elements
        if (document.body) {
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }
    
    // Intercept clicks as a fallback
    document.addEventListener('click', function(event) {
        const link = event.target.closest('a');
        if (link) {
            const href = link.getAttribute('href') || '';
            if ((href.includes('validasi-dokumen') || href.includes('test-hello')) && !link.hasAttribute('target')) {
                link.setAttribute('target', '_blank');
            }
        }
    }, true); // Use capture phase to intercept before OpenAdmin's handler
})();
