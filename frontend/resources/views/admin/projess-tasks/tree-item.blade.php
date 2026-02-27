@php
    $children = $allTasks->where('task_parent', $task->id)->sortBy('task_order');
    $hasChildren = $children->count() > 0;
@endphp

<li class="tree-item" data-level="{{ $level }}" data-id="{{ $task->id }}">
    <div class="tree-item-content" onclick="if(typeof toggleTree === 'function') toggleTree(this);">
        @if($hasChildren)
            <span class="tree-toggle collapsed" onclick="event.stopPropagation(); if(typeof toggleTree === 'function') toggleTree(this.closest('.tree-item-content'));">
                <i class="icon-plus"></i>
            </span>
        @else
            <span class="tree-toggle no-children">
                <i class="icon-circle" style="font-size: 0.5rem;"></i>
            </span>
        @endif
        
        <div class="tree-item-body">
            <div class="tree-item-header">
                <h5 class="tree-item-title">{{ $task->task_name }}</h5>
                <div class="tree-item-badges">
                    <span class="badge bg-secondary">Order: {{ $task->task_order }}</span>
                    @if($task->is_optional)
                        <span class="badge bg-info">Optional</span>
                    @endif
                    @if($task->task_parent > 0)
                        <span class="badge bg-warning">Sub-task</span>
                    @endif
                    @if($level > 0)
                        <span class="badge bg-primary">Level {{ $level + 1 }}</span>
                    @endif
                </div>
            </div>
            
            @if($task->task_description)
                <div class="tree-item-description">
                    {{ Str::limit($task->task_description, 200) }}
                </div>
            @endif
            
            <div class="tree-item-meta">
                @if($task->task_roles)
                    <span><i class="icon-user"></i> Roles: {{ $task->task_roles }}</span>
                @endif
                @if($task->task_parent > 0)
                    <span><i class="icon-link"></i> Parent ID: {{ $task->task_parent }}</span>
                @endif
                @if($task->created_at)
                    <span><i class="icon-calendar"></i> Created: {{ $task->created_at->format('d M Y') }}</span>
                @endif
            </div>
        </div>
        
        <div class="tree-item-actions" onclick="event.stopPropagation();">
            <a href="{{ url(config('admin.route.prefix') . '/projess-tasks/' . $task->id) }}" 
               class="btn btn-sm btn-info" 
               title="View">
                <i class="icon-eye"></i>
            </a>
            <a href="{{ url(config('admin.route.prefix') . '/projess-tasks/' . $task->id . '/edit') }}" 
               class="btn btn-sm btn-primary" 
               title="Edit">
                <i class="icon-edit"></i>
            </a>
        </div>
    </div>
    
    @if($hasChildren)
        <ul class="tree-children">
            @foreach($children as $child)
                @include('admin.projess-tasks.tree-item', [
                    'task' => $child,
                    'allTasks' => $allTasks,
                    'level' => $level + 1
                ])
            @endforeach
        </ul>
    @endif
</li>
