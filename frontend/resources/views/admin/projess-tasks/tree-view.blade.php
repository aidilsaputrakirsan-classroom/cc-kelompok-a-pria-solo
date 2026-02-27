<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="icon-tree"></i> Task Tree View
                    </h3>
                    <div class="card-tools">
                        <a href="{{ url(config('admin.route.prefix') . '/projess-tasks') }}" class="btn btn-sm btn-default">
                            <i class="icon-list"></i> Back to List
                        </a>
                        <button type="button" class="btn btn-sm btn-info" onclick="expandAll()">
                            <i class="icon-plus"></i> Expand All
                        </button>
                        <button type="button" class="btn btn-sm btn-info" onclick="collapseAll()">
                            <i class="icon-minus"></i> Collapse All
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="icon-info"></i> 
                        <strong>Tree View:</strong> Visualisasi hierarki task berdasarkan relasi parent-child. Klik untuk expand/collapse.
                    </div>

                    @if($rootTasks->isEmpty())
                        <div class="alert alert-warning">
                            <i class="icon-warning"></i> Belum ada task yang dibuat. 
                            <a href="{{ url(config('admin.route.prefix') . '/projess-tasks/create') }}" class="btn btn-sm btn-primary">Buat Task Baru</a>
                        </div>
                    @else
                        <div class="tree-container">
                            <ul class="tree">
                                @foreach($rootTasks as $rootTask)
                                    @include('admin.projess-tasks.tree-item', [
                                        'task' => $rootTask,
                                        'allTasks' => $allTasks,
                                        'level' => 0
                                    ])
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .tree-container {
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 8px;
        min-height: 400px;
    }

    .tree {
        list-style: none;
        padding-left: 0;
        margin: 0;
    }

    .tree ul {
        list-style: none;
        padding-left: 30px;
        margin: 5px 0;
        position: relative;
    }

    .tree ul::before {
        content: '';
        position: absolute;
        left: 15px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #dee2e6;
    }

    .tree-item {
        margin: 8px 0;
        position: relative;
    }

    .tree-item-content {
        display: flex;
        align-items: flex-start;
        padding: 12px 15px;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .tree-item-content:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        transform: translateX(5px);
        border-color: #007bff;
    }

    .tree-toggle {
        margin-right: 10px;
        width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #f0f0f0;
        cursor: pointer;
        flex-shrink: 0;
        transition: all 0.2s;
    }

    .tree-toggle:hover {
        background: #007bff;
        color: white;
    }

    .tree-toggle.expanded {
        background: #007bff;
        color: white;
    }

    .tree-toggle.collapsed {
        background: #f0f0f0;
        color: #6c757d;
    }

    .tree-item-body {
        flex: 1;
        min-width: 0;
    }

    .tree-item-header {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 5px;
    }

    .tree-item-title {
        font-weight: 600;
        font-size: 1rem;
        color: #212529;
        margin: 0;
    }

    .tree-item-badges {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .tree-item-description {
        color: #6c757d;
        font-size: 0.9rem;
        margin: 5px 0;
        line-height: 1.4;
    }

    .tree-item-meta {
        display: flex;
        gap: 15px;
        font-size: 0.85rem;
        color: #868e96;
        margin-top: 5px;
    }

    .tree-item-actions {
        margin-left: 10px;
        display: flex;
        gap: 5px;
    }

    .tree-item-actions .btn {
        padding: 4px 8px;
        font-size: 0.8rem;
    }

    .tree-children {
        display: none;
        margin-top: 8px;
    }

    .tree-children.expanded {
        display: block;
    }

    /* Level-based indentation colors */
    .tree-item[data-level="0"] .tree-item-content {
        border-left: 4px solid #007bff;
    }

    .tree-item[data-level="1"] .tree-item-content {
        border-left: 4px solid #28a745;
    }

    .tree-item[data-level="2"] .tree-item-content {
        border-left: 4px solid #ffc107;
    }

    .tree-item[data-level="3"] .tree-item-content {
        border-left: 4px solid #dc3545;
    }

    .tree-item[data-level="4"] .tree-item-content {
        border-left: 4px solid #6f42c1;
    }

    /* No children indicator */
    .tree-toggle.no-children {
        opacity: 0.3;
        cursor: default;
    }

    .tree-toggle.no-children:hover {
        background: #f0f0f0;
        color: #6c757d;
    }
</style>

<script>
(function() {
    'use strict';
    
    // Function to toggle tree node
    function toggleTree(element) {
        const treeItem = element.closest('.tree-item');
        if (!treeItem) return;
        
        const children = treeItem.querySelector('.tree-children');
        const toggle = treeItem.querySelector('.tree-toggle');
        
        if (children) {
            const isExpanded = children.classList.contains('expanded');
            
            if (isExpanded) {
                children.classList.remove('expanded');
                if (toggle && !toggle.classList.contains('no-children')) {
                    toggle.classList.remove('expanded');
                    toggle.classList.add('collapsed');
                    toggle.innerHTML = '<i class="icon-plus"></i>';
                }
            } else {
                children.classList.add('expanded');
                if (toggle && !toggle.classList.contains('no-children')) {
                    toggle.classList.remove('collapsed');
                    toggle.classList.add('expanded');
                    toggle.innerHTML = '<i class="icon-minus"></i>';
                }
            }
        }
    }

    // Make functions available globally
    window.toggleTree = toggleTree;

    window.expandAll = function() {
        document.querySelectorAll('.tree-children').forEach(function(children) {
            children.classList.add('expanded');
            const toggle = children.closest('.tree-item').querySelector('.tree-toggle');
            if (toggle && !toggle.classList.contains('no-children')) {
                toggle.classList.remove('collapsed');
                toggle.classList.add('expanded');
                toggle.innerHTML = '<i class="icon-minus"></i>';
            }
        });
    };

    window.collapseAll = function() {
        document.querySelectorAll('.tree-children').forEach(function(children) {
            children.classList.remove('expanded');
            const toggle = children.closest('.tree-item').querySelector('.tree-toggle');
            if (toggle && !toggle.classList.contains('no-children')) {
                toggle.classList.remove('expanded');
                toggle.classList.add('collapsed');
                toggle.innerHTML = '<i class="icon-plus"></i>';
            }
        });
    };

    // Initialize on DOM ready
    function init() {
        // Auto-expand first level on load
        document.querySelectorAll('.tree-item[data-level="0"] .tree-children').forEach(function(children) {
            children.classList.add('expanded');
            const toggle = children.closest('.tree-item').querySelector('.tree-toggle');
            if (toggle && !toggle.classList.contains('no-children')) {
                toggle.classList.add('expanded');
                toggle.innerHTML = '<i class="icon-minus"></i>';
            }
        });
    }

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
