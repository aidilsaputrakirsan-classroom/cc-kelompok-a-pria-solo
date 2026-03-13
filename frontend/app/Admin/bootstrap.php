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

OpenAdmin\Admin\Admin::script("
(function() {
    var advanceReviewPaths = [
        'validasi-dokumen',
        'riwayat-review',
        'advance-reviews',
        'advance-result',
        'basic-result',
        'validate-ground-truth',
        'pairing-documents'
    ];
    var path = window.location.pathname;
    var isAdvanceReviewPage = advanceReviewPaths.some(function(seg) { return path.indexOf(seg) !== -1; });

    // Force full browser refresh on every access to any advance-reviews page (each blade/URL).
    if (isAdvanceReviewPage) {
        var lastPath = sessionStorage.getItem('ar_last_path');
        if (lastPath !== path) {
            sessionStorage.setItem('ar_last_path', path);
            window.location.reload();
            return;
        }
    } else {
        sessionStorage.removeItem('ar_last_path');
    }

    function enableLinkContextMenu() {
        document.addEventListener('contextmenu', function(e) {
            var link = e.target && e.target.closest ? e.target.closest('a') : null;
            if (!link) return;
            var row = e.target.closest && e.target.closest('tr[data-key]');
            if (row) {
                e.stopPropagation();
            }
        }, true);
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', enableLinkContextMenu);
    } else {
        enableLinkContextMenu();
    }
})();
");
