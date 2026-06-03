@extends('layouts.app')


@section('content')
<style>
    .wm-kanban {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.75rem;
        align-items: stretch;
    }
    .wm-col {
        background: var(--wm-surface);
        border: 1px solid var(--wm-border);
        border-radius: 0.625rem;
        display: flex;
        flex-direction: column;
        min-height: 60vh;
    }
    .wm-col-header {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--wm-border);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        font-size: 0.8125rem;
    }
    .wm-col-header .dot { width: .55rem; height: .55rem; border-radius: 50%; display: inline-block; }
    .wm-col-body {
        flex-grow: 1;
        padding: 0.625rem;
        overflow-y: auto;
        min-height: 100px;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .wm-card {
        background: var(--wm-surface);
        border: 1px solid var(--wm-border);
        border-left: 4px solid var(--card-color, #71717A);
        border-radius: 0.5rem;
        padding: 0.625rem 0.75rem;
        cursor: grab;
        transition: box-shadow 0.12s ease, transform 0.12s ease;
    }
    .wm-card:hover { box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
    .wm-card:active { cursor: grabbing; }
    .wm-card-title {
        font-size: 0.8125rem;
        font-weight: 600;
        margin-bottom: 0.35rem;
        display: block;
        color: inherit;
        text-decoration: none;
    }
    .wm-card-meta {
        font-size: 0.7rem;
        color: var(--wm-muted);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .wm-card-meta .badge { font-size: 0.625rem; }

    /* Drag states */
    .wm-card.sortable-ghost { opacity: 0.35; background: #EEF2FF; }
    .wm-card.sortable-chosen { transform: scale(1.02); }
    .wm-col-body.wm-drag-over { background: rgba(79, 70, 229, 0.04); }

    /* Empty state */
    .wm-empty {
        text-align: center;
        color: var(--wm-muted);
        font-size: 0.75rem;
        font-style: italic;
        padding: 1rem;
    }

    /* Subtle "saving" pulse */
    .wm-saving { animation: wm-pulse 0.7s ease-in-out; }
    @keyframes wm-pulse {
        0%   { background: #FEF3C7; }
        100% { background: var(--wm-surface); }
    }
    .wm-save-error { border-left-color: #EF4444 !important; }

    /* Timeline urgency badges (same as dashboard) */
    .wm-overdue {
        color: #fff;
        background-color: #ef4444;
        animation: wm-blink 1.1s ease-in-out infinite;
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
        font-size: 0.65rem;
    }
    .wm-due-today {
        color: #fff;
        background-color: #f59e0b;
        font-weight: 700;
        animation: wm-blink 1.1s ease-in-out infinite;
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
        font-size: 0.65rem;
    }
    @keyframes wm-blink {
        0%, 100% { opacity: 1; }
        50%      { opacity: 0.45; }
    }

    /* ── On-hold panel above the main board ──────────────────────────── */
    .wm-hold-panel {
        background: #F5F3FF;
        border: 1px solid #DDD6FE;
        border-left: 4px solid #7C3AED;
        border-radius: 0.625rem;
        padding: 0.75rem 1rem;
        margin-bottom: 0.75rem;
    }
    [data-bs-theme="dark"] .wm-hold-panel {
        background: rgba(124, 58, 237, 0.10);
        border-color: rgba(124, 58, 237, 0.4);
    }
    .wm-hold-panel-header {
        font-size: 0.8rem;
        font-weight: 600;
        color: #5B21B6;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }
    [data-bs-theme="dark"] .wm-hold-panel-header { color: #C4B5FD; }
    .wm-hold-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .wm-hold-card {
        background: var(--wm-surface);
        border: 1px solid var(--wm-border);
        border-left: 3px solid #7C3AED;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        flex: 1 1 280px;
        max-width: 360px;
        cursor: grab;
        transition: box-shadow 0.12s ease, transform 0.12s ease;
    }
    .wm-hold-card:hover { box-shadow: 0 2px 6px rgba(124, 58, 237, 0.15); }
    .wm-hold-card:active { cursor: grabbing; }
    .wm-hold-card.sortable-ghost { opacity: 0.35; }
    .wm-hold-card.sortable-chosen { transform: scale(1.02); }
    .wm-hold-list.wm-drop-target { background: rgba(124, 58, 237, 0.08); border-radius: 0.5rem; }
    .wm-hold-card .title {
        font-size: 0.8125rem;
        font-weight: 600;
        text-decoration: none;
        color: inherit;
    }
    .wm-hold-card .reason {
        font-size: 0.7rem;
        color: #5B21B6;
        margin-top: 0.25rem;
    }
    [data-bs-theme="dark"] .wm-hold-card .reason { color: #C4B5FD; }
    .wm-hold-card .meta {
        font-size: 0.7rem;
        color: var(--wm-muted);
        display: flex;
        gap: 0.5rem;
        margin-top: 0.25rem;
    }

    /* ── Inline edit controls (priority/assignee/date/category) ──────── */
    .wm-priority-select {
        font-size: 0.65rem; font-weight: 600; border: 0; border-radius: 0.375rem;
        padding: 0.15rem 1.2rem 0.15rem 0.45rem; cursor: pointer;
        appearance: none;
        background-position: right 0.3rem center; background-repeat: no-repeat; background-size: 10px;
    }
    .wm-priority-low    { color:#fff; background-color:#71717A; background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 16 16'%3e%3cpath d='M3.204 5h9.592L8 10.481 3.204 5z'/%3e%3c/svg%3e"); }
    .wm-priority-medium { color:#fff; background-color:#F59E0B; background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 16 16'%3e%3cpath d='M3.204 5h9.592L8 10.481 3.204 5z'/%3e%3c/svg%3e"); }
    .wm-priority-high   { color:#fff; background-color:#DC2626; background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 16 16'%3e%3cpath d='M3.204 5h9.592L8 10.481 3.204 5z'/%3e%3c/svg%3e"); }

    .wm-bare-select, .wm-inline-date {
        border: 1px solid transparent; background: transparent;
        font-size: 0.7rem; color: var(--wm-muted);
        padding: 0.1rem 0.3rem; border-radius: 0.25rem; cursor: pointer;
        appearance: none; -webkit-appearance: none; font-family: inherit;
        max-width: 8.5rem;
    }
    .wm-bare-select:hover, .wm-inline-date:hover { border-color: var(--wm-border); background: var(--wm-surface); }
    .wm-bare-select:focus, .wm-inline-date:focus { border-color: var(--bs-primary); outline: 0; background: var(--wm-surface); }

    .wm-cat-pick { display: inline-flex; align-items: center; gap: 0.25rem; }
    .wm-cat-pick .dot { display: inline-block; width: .5rem; height: .5rem; border-radius: 50%; flex-shrink: 0; }
    .wm-cat-pick select {
        border: 1px dashed transparent; background: transparent;
        font-size: 0.65rem !important; font-weight: 400 !important;
        color: var(--wm-muted) !important;
        padding: 0 0.15rem; border-radius: 0.2rem;
        appearance: none; cursor: pointer; max-width: 6rem;
        line-height: 1.3;
    }
    .wm-cat-pick select:hover { border-color: var(--wm-border); }
    .wm-cat-pick select:focus { border-color: var(--bs-primary); outline: 0; }

    .wm-notes-btn {
        background: transparent; border: 0; color: var(--wm-muted);
        padding: 0 0.2rem; cursor: pointer; font-size: 0.7rem;
    }
    .wm-notes-btn:hover, .wm-notes-btn.has-notes { color: var(--bs-warning); }

    .wm-saved { animation: wm-flash 0.7s ease-in-out; }
    .wm-error { outline: 2px solid var(--bs-danger) !important; }
    @keyframes wm-flash {
        0%   { background: rgba(34, 197, 94, 0.25); }
        100% { background: transparent; }
    }

    /* Inline-date that lives inside an urgency badge keeps white text */
    .wm-overdue .wm-inline-date,
    .wm-due-today .wm-inline-date { color: #fff; }

    /* ── Mobile (< 768px): swipeable columns ──────────────────────────── */
    @media (max-width: 767.98px) {
        .wm-kanban {
            display: flex;
            grid-template-columns: none;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scroll-snap-type: x mandatory;
            gap: 0.75rem;
            padding-bottom: 0.5rem;
        }
        .wm-col {
            flex: 0 0 85vw;
            min-width: 85vw;
            scroll-snap-align: start;
            min-height: 50vh;
        }
        .wm-col-header { padding: 0.6rem 0.75rem; font-size: 0.8rem; }
        .wm-col-body  { padding: 0.5rem; }
        .wm-card      { padding: 0.55rem; }
        .wm-card-title { font-size: 0.85rem; }
    }
</style>

<div class="container-fluid px-3 px-md-4">

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0"><i class="fa-solid fa-table-columns text-primary me-2"></i>Kanban Board</h1>
        <a href="{{ route('tasks.create', ['category' => $categoryId ?: null]) }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i>New Task
        </a>
    </div>

    {{-- Preset chip row --}}
    @php
        $kPresets = [
            ['overdue',   'Overdue',          'fa-triangle-exclamation', '#EF4444'],
            ['due_today', 'Due today',        'fa-bolt',                 '#F59E0B'],
            ['high',      'High priority',    'fa-fire',                 '#DC2626'],
        ];
    @endphp
    <div class="d-flex flex-wrap gap-2 mb-2 align-items-center">
        <span class="text-secondary small me-1">Quick views:</span>
        @foreach ($kPresets as [$key, $label, $icon, $color])
            @php $isActive = $preset === $key; @endphp
            <a href="{{ route('kanban', array_filter(['preset' => $key, 'mine' => $onlyMine ? 1 : null])) }}"
               class="btn btn-sm {{ $isActive ? 'btn-primary' : 'btn-outline-secondary' }}">
                <i class="fa-solid {{ $icon }} me-1" @if(! $isActive) style="color:{{ $color }}" @endif></i>{{ $label }}
            </a>
        @endforeach
        @if ($preset)
            <a href="{{ route('kanban', array_filter(['category' => $categoryId ?: null, 'mine' => $onlyMine ? 1 : null])) }}"
               class="btn btn-sm btn-link text-secondary text-decoration-none">
                <i class="fa-solid fa-xmark me-1"></i>Clear preset
            </a>
        @endif
    </div>

    {{-- Filter bar --}}
    <form method="GET" action="{{ route('kanban') }}" class="row g-2 align-items-center mb-3">
        @if ($preset) <input type="hidden" name="preset" value="{{ $preset }}"> @endif
        <div class="col-md-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-secondary"></i></span>
                <input type="text" name="q" value="{{ $search }}"
                       class="form-control form-control-sm" placeholder="Search...">
            </div>
        </div>
        <div class="col-md-2">
            <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected($categoryId === $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="website" class="form-select form-select-sm">
                <option value="">All websites</option>
                @foreach ($priorWebsites as $w)
                    <option value="{{ $w }}" @selected($website === $w)>{{ Str::limit($w, 26) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="assignee" class="form-select form-select-sm">
                <option value="">All assignees</option>
                @foreach ($assignableUsers as $u)
                    <option value="{{ $u->id }}" @selected($assigneeId === $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        {{-- Done-tasks date window. Empty = this week (Mon–Fri). --}}
        <div class="col-auto">
            <div class="input-group input-group-sm" title="Done tasks date range (default = this week Mon–Fri)">
                <span class="input-group-text bg-white" style="font-size:.7rem;">
                    <i class="fa-regular fa-calendar text-secondary me-1"></i>Done
                </span>
                <input type="date" name="from" value="{{ $fromDate }}" class="form-control form-control-sm" style="max-width:9rem;" title="From">
                <input type="date" name="to"   value="{{ $toDate }}"   class="form-control form-control-sm" style="max-width:9rem;" title="To">
            </div>
        </div>
        @if ($isAdmin)
            <div class="col-auto">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" id="onlyMine" name="mine" value="1"
                           @checked($onlyMine) onchange="this.form.submit()">
                    <label class="form-check-label small" for="onlyMine">Only mine</label>
                </div>
            </div>
        @endif
        <div class="col-auto d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-filter me-1"></i>Filter
            </button>
            @if ($search || $website || $assigneeId || $categoryId || $fromDate || $toDate)
                <a href="{{ route('kanban', array_filter(['preset' => $preset ?: null, 'mine' => $onlyMine ? 1 : null])) }}" class="btn btn-sm btn-outline-secondary" title="Clear">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            @endif
        </div>
    </form>

    {{-- On Hold panel — separate from main columns. Always rendered so the
         list can act as a drag-drop target even when empty. --}}
    <div class="wm-hold-panel">
        <div class="wm-hold-panel-header">
            <i class="fa-solid fa-circle-pause"></i>
            On Hold ({{ $holdTasks->count() }})
            <span class="ms-auto text-secondary fw-normal" style="font-size:.7rem;">
                Drag a card here to pause it
            </span>
        </div>
        <div class="wm-hold-list" data-status="hold" id="col-hold">
            @forelse ($holdTasks as $task)
                <div class="wm-hold-card" data-task-id="{{ $task->id }}">
                    <a href="{{ route('tasks.show', $task) }}" class="title d-block">{{ $task->title }}</a>
                    @if ($task->hold_reason)
                        <div class="reason">
                            <i class="fa-solid fa-quote-left me-1"></i>{{ $task->hold_reason }}
                        </div>
                    @endif
                    <div class="meta">
                        <span class="badge text-bg-{{ $task->priority->color() }}" style="font-size:.625rem">{{ $task->priority->label() }}</span>
                        <span><i class="fa-solid fa-user me-1"></i>{{ $task->assignee?->name ?? 'Unassigned' }}</span>
                        <span class="ms-auto" style="font-size:.65rem;">{{ $task->category->name }}</span>
                    </div>
                </div>
            @empty
                <div class="wm-empty w-100" style="background: rgba(124, 58, 237, 0.04); border: 1px dashed #C4B5FD; border-radius: .5rem;">
                    Koi task on hold nahi — yahan drag karo to pause.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Board --}}
    <div class="wm-kanban">
        @foreach ($statuses as $status)
            @php $colTasks = $tasksByStatus->get($status->value, collect()); @endphp
            <div class="wm-col">
                <div class="wm-col-header">
                    <span class="dot" style="background: var(--bs-{{ $status->color() }})"></span>
                    <span>{{ $status->label() }}</span>
                    <span class="badge text-bg-light border ms-auto" data-col-count="{{ $status->value }}">{{ $colTasks->count() }}</span>
                </div>
                <div class="wm-col-body" data-status="{{ $status->value }}" id="col-{{ $status->value }}">
                    @forelse ($colTasks as $task)
                        <div class="wm-card" data-task-id="{{ $task->id }}" style="--card-color: {{ $task->category->color }}">
                            <div class="d-flex align-items-start gap-1">
                                <a href="{{ route('tasks.show', $task) }}" class="wm-card-title flex-grow-1" style="margin-bottom: 0.35rem;">
                                    {{ $task->title }}
                                </a>
                                @if ($task->final_url && $task->status->showsFinalUrl())
                                    <a href="{{ $task->final_url }}" target="_blank" rel="noopener"
                                       class="flex-shrink-0 text-decoration-none"
                                       title="Final URL: {{ $task->final_url }}"
                                       onclick="event.stopPropagation();">
                                        <i class="fa-solid fa-arrow-up-right-from-square text-primary small"></i>
                                    </a>
                                @endif
                            </div>
                            @if ($task->status->showsFinalUrl() && ! $task->final_url)
                                @can('update', $task)
                                    <button type="button"
                                            class="btn btn-sm btn-link p-0 mt-1 mb-1 text-primary wm-add-url"
                                            data-task-id="{{ $task->id }}"
                                            title="Add final URL"
                                            style="font-size: 0.7rem;">
                                        <i class="fa-solid fa-link me-1"></i>+ Add final URL
                                    </button>
                                @endcan
                            @endif
                            <div class="wm-card-meta">
                                @can('update', $task)
                                    <select class="wm-priority-select wm-priority-{{ $task->priority->value }} wm-inline"
                                            data-task-id="{{ $task->id }}" data-field="priority" title="Change priority">
                                        @foreach ($priorities as $value => $label)
                                            <option value="{{ $value }}" @selected($task->priority->value === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="badge text-bg-{{ $task->priority->color() }}">{{ $task->priority->label() }}</span>
                                @endcan

                                @can('update', $task)
                                    <span title="Reassign"><i class="fa-solid fa-user me-1"></i><select class="wm-bare-select wm-inline" data-task-id="{{ $task->id }}" data-field="assigned_to">
                                        <option value="">Unassigned</option>
                                        @foreach ($assignableUsers as $u)
                                            <option value="{{ $u->id }}" @selected($task->assigned_to === $u->id)>{{ $u->name }}</option>
                                        @endforeach
                                        @if ($task->assignee && ! $assignableUsers->contains('id', $task->assignee->id))
                                            <option value="{{ $task->assignee->id }}" selected>{{ $task->assignee->name }} (current)</option>
                                        @endif
                                    </select></span>
                                @else
                                    <span><i class="fa-solid fa-user me-1"></i>{{ $task->assignee?->name ?? 'Unassigned' }}</span>
                                @endcan

                                @can('update', $task)
                                    @php
                                        $dueClass = '';
                                        if ($task->due_date) {
                                            if ($task->isOverdue())      $dueClass = 'wm-overdue';
                                            elseif ($task->isDueToday()) $dueClass = 'wm-due-today';
                                        }
                                    @endphp
                                    <span class="{{ $dueClass }}" @if($dueClass) title="{{ $dueClass === 'wm-overdue' ? 'Overdue!' : 'Aaj last date hai!' }}" @endif>
                                        @if ($dueClass === 'wm-overdue')        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                        @elseif ($dueClass === 'wm-due-today')  <i class="fa-solid fa-bolt me-1"></i>
                                        @else                                   <i class="fa-regular fa-calendar me-1"></i>
                                        @endif
                                        <input type="date" class="wm-inline-date wm-inline"
                                               data-task-id="{{ $task->id }}" data-field="due_date" data-reload="1"
                                               value="{{ $task->due_date?->format('Y-m-d') }}" title="Change timeline">
                                    </span>
                                @elseif ($task->due_date)
                                    <span><i class="fa-regular fa-calendar me-1"></i>{{ $task->due_date->format('d M') }}</span>
                                @endcan

                                @can('update', $task)
                                    <button type="button"
                                            class="wm-notes-btn wm-no-panel {{ $task->delay_reason ? 'has-notes' : '' }}"
                                            data-task-id="{{ $task->id }}"
                                            data-current="{{ $task->delay_reason }}"
                                            title="{{ $task->delay_reason ? 'Notes: '.$task->delay_reason : 'Add note' }}">
                                        <i class="fa-solid fa-note-sticky"></i>
                                    </button>
                                @endcan

                                <span class="ms-auto wm-cat-pick">
                                    <span class="dot" style="background:{{ $task->category->color }}"></span>
                                    @can('update', $task)
                                        <select class="wm-inline" data-task-id="{{ $task->id }}" data-field="category_id" data-reload="1" title="Change category">
                                            @foreach ($categories as $cat)
                                                <option value="{{ $cat->id }}" @selected($task->category_id === $cat->id)>{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <span style="font-size:.65rem;color:var(--wm-muted);">{{ $task->category->name }}</span>
                                    @endcan
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="wm-empty">Koi task nahi</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>

<script>
    // The Vite directive outputs <script type="module"> which loads
    // async/deferred, so this inline script must wait for DOMContentLoaded
    // — by then the module has set window.Sortable.
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.Sortable === 'undefined') {
            console.error('SortableJS not loaded — drag-drop disabled.');
            return;
        }
        const Sortable = window.Sortable;
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const statusUrl = (taskId) => `{{ url('tasks') }}/${taskId}/status`;

        function refreshCounts() {
            document.querySelectorAll('.wm-col-body').forEach(col => {
                const status = col.dataset.status;
                const count = col.querySelectorAll('.wm-card').length;
                const badge = document.querySelector(`[data-col-count="${status}"]`);
                if (badge) badge.textContent = count;
                // Toggle empty placeholder
                const empty = col.querySelector('.wm-empty');
                if (count === 0 && !empty) {
                    const ph = document.createElement('div');
                    ph.className = 'wm-empty';
                    ph.textContent = 'Koi task nahi';
                    col.appendChild(ph);
                } else if (count > 0 && empty) {
                    empty.remove();
                }
            });
        }

        async function saveStatus(taskId, newStatus, card, opts = {}) {
            const { reloadAfter = false, holdReason = null } = opts;
            card.classList.add('wm-saving');
            try {
                const fd = new FormData();
                fd.append('_token', csrf);
                fd.append('_method', 'PATCH');
                fd.append('status', newStatus);
                if (holdReason !== null) fd.append('hold_reason', holdReason);
                const res = await fetch(statusUrl(taskId), {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                if (reloadAfter) {
                    // Hold uses a different card layout (purple panel above the
                    // board) — reload so the layout re-renders cleanly.
                    location.reload();
                }
            } catch (err) {
                console.error('Status update failed:', err);
                card.classList.add('wm-save-error');
                alert('Status update fail ho gaya — page refresh karke try karo.');
            } finally {
                setTimeout(() => card.classList.remove('wm-saving'), 700);
            }
        }

        document.querySelectorAll('.wm-col-body, .wm-hold-list').forEach(col => {
            new Sortable(col, {
                group: 'wm-board',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                draggable: '.wm-card, .wm-hold-card',
                // Stop drags from starting on inline-edit controls.
                filter: '.wm-empty, select, input, button, .wm-inline, .wm-no-panel',
                preventOnFilter: false,
                onAdd: (evt) => { col.classList.remove('wm-drag-over'); },
                onEnd: (evt) => {
                    const card = evt.item;
                    const targetStatus = evt.to.dataset.status;
                    const sourceStatus = evt.from.dataset.status;
                    const taskId = card.dataset.taskId;

                    if (evt.from === evt.to) { refreshCounts(); return; }

                    // Going INTO Hold requires a reason. If user cancels, undo.
                    if (targetStatus === 'hold') {
                        const reason = prompt('Why is this task on hold? (Reason zaroori hai)');
                        if (reason === null || reason.trim() === '') {
                            evt.from.insertBefore(card, evt.from.children[evt.oldIndex] || null);
                            refreshCounts();
                            return;
                        }
                        refreshCounts();
                        saveStatus(taskId, 'hold', card, { reloadAfter: true, holdReason: reason.trim() });
                        return;
                    }

                    refreshCounts();
                    const crossHold = (sourceStatus === 'hold');
                    saveStatus(taskId, targetStatus, card, { reloadAfter: crossHold });
                },
            });
        });

        // Inline "+ Add final URL" handler (same as dashboard).
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.wm-add-url');
            if (!btn) return;
            e.preventDefault();
            const url = prompt('Paste the final URL (live / draft page link):');
            if (url === null || url.trim() === '') return;
            const fd = new FormData();
            fd.append('_token', csrf);
            fd.append('_method', 'PATCH');
            fd.append('final_url', url.trim());
            fetch(`{{ url('tasks') }}/${btn.dataset.taskId}/final-url`, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
            .then(res => res.ok ? location.reload() : alert('Save failed — URL must start with http(s)://'))
            .catch(() => alert('Save failed.'));
        });

        // ── Inline field save (shared with dashboard) ─────────────────
        window.wmSaveInline = async function (taskId, field, value, opts = {}) {
            const { reload = false } = opts;
            const fd = new FormData();
            fd.append('_token', csrf);
            fd.append('_method', 'PATCH');
            fd.append('field', field);
            if (value !== null && value !== undefined) fd.append('value', value);
            try {
                const res = await fetch(`{{ url('tasks') }}/${taskId}/inline`, {
                    method: 'POST', body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                if (!res.ok) {
                    const body = await res.json().catch(() => ({}));
                    throw new Error(body.message || `HTTP ${res.status}`);
                }
                const json = await res.json();
                if (reload) location.reload();
                return json;
            } catch (err) {
                console.error('Inline save failed:', err);
                alert('Save fail ho gaya: ' + err.message);
                throw err;
            }
        };

        document.addEventListener('change', function (e) {
            const el = e.target.closest('.wm-inline');
            if (!el) return;
            const taskId = el.dataset.taskId, field = el.dataset.field;
            const reload = el.dataset.reload === '1';
            if (!taskId || !field) return;
            if (field === 'priority' && el.classList.contains('wm-priority-select')) {
                el.classList.remove('wm-priority-low','wm-priority-medium','wm-priority-high');
                el.classList.add('wm-priority-' + el.value);
            }
            wmSaveInline(taskId, field, el.value, { reload })
                .then(() => { el.classList.add('wm-saved'); setTimeout(() => el.classList.remove('wm-saved'), 700); })
                .catch(() => { el.classList.add('wm-error'); setTimeout(() => el.classList.remove('wm-error'), 1500); });
        });

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.wm-notes-btn');
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();
            const text = prompt('Notes / delay reason:', btn.dataset.current || '');
            if (text === null) return;
            wmSaveInline(btn.dataset.taskId, 'delay_reason', text, { reload: true });
        });
    });
</script>
@endsection
