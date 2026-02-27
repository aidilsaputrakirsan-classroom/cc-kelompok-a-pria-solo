<style>
.summary-tree {
    width: 100%;
    margin: 0 auto;
}

.tree-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: inset 0 0 0 1px rgba(102, 126, 234, 0.08);
    margin-bottom: 24px;
}

.tree-table th,
.tree-table td {
    padding: 12px 16px;
    font-size: 14px;
    color: #2d3053;
    text-align: left;
    border-bottom: 1px solid rgba(102, 126, 234, 0.12);
}

.tree-table th {
    background: rgba(102, 126, 234, 0.05);
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 1;
}

.task-row {
    background: #f0f6ff;
    cursor: pointer;
}

.task-row .task-info {
    display: flex;
    flex-wrap: wrap;
    gap: 14px;
    align-items: center;
    font-weight: 600;
}

.task-row .task-info .badge {
    background: #667eea;
    color: #fff;
    border-radius: 999px;
    padding: 4px 12px;
    font-size: 12px;
    font-weight: 600;
}

.task-button {
    border: none;
    outline: none;
    background: #22254a;
    color: #fff;
    border-radius: 999px;
    padding: 6px 16px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s ease;
}

.task-button:hover {
    background: #10122c;
}

.subtask-row {
    color: #22254a;
}

.subtask-row td {
    background: #fff;
}

.subtask-row.hidden {
    display: none;
}

.subtask-label {
    display: flex;
    flex-direction: column;
    gap: 4px;
    position: relative;
    padding-left: 20px;
}

.subtask-label::before {
    content: "";
    position: absolute;
    left: 6px;
    top: 10px;
    width: 6px;
    height: 6px;
    background: #667eea;
    border-radius: 50%;
}

.subtask-label a {
    color: #22254a;
    font-weight: 600;
    text-decoration: none;
}

.progress-bar {
    width: 100%;
    height: 6px;
    border-radius: 4px;
    background: rgba(102, 126, 234, 0.2);
    overflow: hidden;
    margin-top: 4px;
}

.progress-bar-inner {
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    transition: width 0.4s ease;
}

.badge-tree {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    color: #fff;
}

.badge-win {
    background: linear-gradient(135deg, #32d89d 0%, #42a5f5 100%);
}

.badge-verified {
    background: linear-gradient(135deg, #f6c23e 0%, #f093fb 100%);
}

.badge-float {
    display: inline-block;
    margin-left: 10px;
    font-size: 11px;
    color: #1b1f3b;
}

.box.box-primary {
    width: 100%;
    margin-left: 0;
    margin-right: 0;
}

.box-body {
    width: 100%;
}
</style>

{!! $filterHtml !!}

@php
    $groupedTasks = collect($data)
        ->groupBy(function ($item) {
            return $item['task_name'] ?: 'Task Lainnya';
        });
@endphp

<div class="summary-tree">
    <table class="tree-table">
        <thead>
            <tr>
                <th>Task / Sub Task</th>
                <th>Projects</th>
                <th>Nilai Project (Juta)</th>
            </tr>
        </thead>
        <tbody>
                @forelse($groupedTasks as $taskName => $subTasks)
                @php
                    $taskKey = 'task-' . md5($taskName);
                    $totalProjects = $subTasks->sum('jumlah_project');
                    $totalWin = $subTasks->sum('jumlah_win');
                    $totalVerified = $subTasks->sum('jumlah_verified');
                    $totalNilai = $subTasks->sum('total_nilai_juta');
                        $taskUrl = optional($subTasks->first())['task_url'] ?? null;
                @endphp
                <tr class="task-row" data-task="{{ $taskKey }}">
                    <td colspan="5">
                        <div class="task-info">
                            @if($taskUrl)
                                <a href="{{ $taskUrl }}" class="task-button">List</a>
                            @endif
                            <span>{{ $taskName }}</span>
                            <span class="badge">Projects {{ number_format($totalProjects) }}</span>
                            <span class="badge" style="background:#37d8a7;">Win {{ number_format($totalWin) }}</span>
                            <span class="badge" style="background:#f6c23e;">Verified {{ number_format($totalVerified) }}</span>
                            <span class="badge" style="background:#5a5ae0;">Value Rp {{ number_format($totalNilai * 1000000, 0, ',', '.') }}</span>
                        </div>
                    </td>
                </tr>
                @foreach($subTasks as $row)
                    @php
                        $percentage = $row['jumlah_project'] > 0
                            ? round(($row['jumlah_verified'] / $row['jumlah_project']) * 100, 1)
                            : 0;
                    @endphp
                    <tr class="subtask-row" data-parent="{{ $taskKey }}">
                        <td>
                            <div class="subtask-label">
                                @if(!empty($row['sub_task_url']))
                                    <a href="{{ $row['sub_task_url'] }}">
                                        {{ $row['sub_task_name'] ?: 'Sub Task Tidak Tersedia' }}
                                    </a>
                                @else
                                    {{ $row['sub_task_name'] ?: 'Sub Task Tidak Tersedia' }}
                                @endif
                            </div>
                        </td>
                        <td>{{ number_format($row['jumlah_project']) }}</td>
                        <td>Rp {{ number_format($row['total_nilai_juta'] * 1000000, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding: 30px;">
                        Tidak ada data yang ditemukan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".task-row").forEach(function (taskRow) {
            taskRow.addEventListener("click", function () {
                const key = taskRow.dataset.task;
                const shouldExpand = !taskRow.classList.toggle("expanded");
                document.querySelectorAll('.subtask-row[data-parent="' + key + '"]').forEach(function (row) {
                    row.classList.toggle("hidden", shouldExpand);
                });
            });
        });
        document.querySelectorAll(".task-button").forEach(function (button) {
            button.addEventListener("click", function (event) {
                event.stopPropagation();
            });
        });
    });
</script>
