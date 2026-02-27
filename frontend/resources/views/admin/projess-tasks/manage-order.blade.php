<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="icon-sort"></i> Manage Task Order
                    </h3>
                    <div class="card-tools">
                        <a href="{{ url(config('admin.route.prefix') . '/projess-tasks') }}" class="btn btn-sm btn-default">
                            <i class="icon-list"></i> Back to List
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="icon-info"></i> 
                        <strong>Petunjuk:</strong> Drag and drop task di bawah ini untuk mengatur urutan. Urutan akan otomatis tersimpan.
                    </div>

                    @if($tasks->isEmpty())
                        <div class="alert alert-warning">
                            <i class="icon-warning"></i> Belum ada task yang dibuat. 
                            <a href="{{ url(config('admin.route.prefix') . '/projess-tasks/create') }}" class="btn btn-sm btn-primary">Buat Task Baru</a>
                        </div>
                    @else
                        <div id="task-list" class="list-group">
                            @foreach($tasks as $task)
                                <div class="list-group-item task-item" data-id="{{ $task->id }}" data-order="{{ $task->task_order }}" style="cursor: move;">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center flex-grow-1">
                                            <div class="drag-handle me-3" style="cursor: grab;">
                                                <i class="icon-menu" style="font-size: 1.5rem; color: #6c757d;"></i>
                                            </div>
                                            <div class="task-info flex-grow-1">
                                                <div class="d-flex align-items-center mb-1">
                                                    <h5 class="mb-0 me-2">{{ $task->task_name }}</h5>
                                                    <span class="badge bg-secondary">Order: {{ $task->task_order }}</span>
                                                    @if($task->is_optional)
                                                        <span class="badge bg-info ms-1">Optional</span>
                                                    @endif
                                                    @if($task->task_parent > 0)
                                                        <span class="badge bg-warning ms-1">Sub-task</span>
                                                    @endif
                                                </div>
                                                <p class="text-muted mb-1 small">{{ Str::limit($task->task_description, 150) }}</p>
                                                @if($task->task_roles)
                                                    <div class="small">
                                                        <strong>Roles:</strong> {{ $task->task_roles }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="task-actions ms-3">
                                            <a href="{{ url(config('admin.route.prefix') . '/projess-tasks/' . $task->id . '/edit') }}" class="btn btn-sm btn-primary">
                                                <i class="icon-edit"></i> Edit
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3">
                            <button type="button" id="save-order-btn" class="btn btn-success" style="display: none;">
                                <i class="icon-save"></i> Simpan Urutan
                            </button>
                            <span id="save-status" class="ms-2"></span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SortableJS Library -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<style>
    .task-item {
        transition: all 0.3s ease;
        border-left: 4px solid #007bff;
        margin-bottom: 8px;
    }

    .task-item:hover {
        background-color: #f8f9fa;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transform: translateX(5px);
    }

    .task-item.sortable-ghost {
        opacity: 0.4;
        background-color: #e9ecef;
    }

    .task-item.sortable-drag {
        opacity: 0.8;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .drag-handle {
        user-select: none;
    }

    .drag-handle:active {
        cursor: grabbing !important;
    }

    #save-order-btn {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
        }
        50% {
            box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
        }
    }

    .alert {
        margin-bottom: 20px;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const taskList = document.getElementById('task-list');
    const saveBtn = document.getElementById('save-order-btn');
    const saveStatus = document.getElementById('save-status');
    let hasChanges = false;
    let saveTimeout;

    if (taskList) {
        // Initialize SortableJS
        const sortable = Sortable.create(taskList, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: function(evt) {
                hasChanges = true;
                updateOrderNumbers();
                showSaveButton();
                
                // Auto-save after 2 seconds of inactivity
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(function() {
                    saveOrder();
                }, 2000);
            }
        });

        // Update order numbers visually
        function updateOrderNumbers() {
            const items = taskList.querySelectorAll('.task-item');
            items.forEach((item, index) => {
                const badge = item.querySelector('.badge');
                if (badge && badge.textContent.includes('Order:')) {
                    badge.textContent = 'Order: ' + (index + 1);
                }
            });
        }

        // Show save button
        function showSaveButton() {
            saveBtn.style.display = 'inline-block';
            saveStatus.innerHTML = '<span class="text-warning"><i class="icon-warning"></i> Perubahan belum disimpan</span>';
        }

        // Save order to server
        function saveOrder() {
            const items = taskList.querySelectorAll('.task-item');
            const tasks = Array.from(items).map((item, index) => ({
                id: parseInt(item.dataset.id),
                order: index + 1
            }));

            saveStatus.innerHTML = '<span class="text-info"><i class="icon-spinner fa-spin"></i> Menyimpan...</span>';

            fetch('{{ $updateUrl }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ tasks: tasks })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    saveStatus.innerHTML = '<span class="text-success"><i class="icon-check"></i> Urutan berhasil disimpan!</span>';
                    saveBtn.style.display = 'none';
                    hasChanges = false;
                    
                    // Update data-order attributes
                    items.forEach((item, index) => {
                        item.dataset.order = index + 1;
                    });

                    // Hide success message after 3 seconds
                    setTimeout(function() {
                        saveStatus.innerHTML = '';
                    }, 3000);
                } else {
                    saveStatus.innerHTML = '<span class="text-danger"><i class="icon-close"></i> Error: ' + (data.message || 'Gagal menyimpan') + '</span>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                saveStatus.innerHTML = '<span class="text-danger"><i class="icon-close"></i> Error: Gagal menyimpan urutan</span>';
            });
        }

        // Manual save button click
        saveBtn.addEventListener('click', function() {
            clearTimeout(saveTimeout);
            saveOrder();
        });
    }
});
</script>
