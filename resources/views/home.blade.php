@extends('layouts.app')

@section('content')
<style>
    /* Timeline urgency cues */
    .wm-overdue {
        color: #fff;
        background-color: #ef4444;
        animation: wm-blink 1.1s ease-in-out infinite;
    }
    .wm-due-today {
        color: #fff;
        background-color: #f59e0b;
        font-weight: 700;
        animation: wm-blink 1.1s ease-in-out infinite;
    }
    @keyframes wm-blink {
        0%, 100% { opacity: 1; }
        50%      { opacity: 0.45; }
    }

    /* ── Mobile polish ──────────────────────────────────────────────────── */
    @media (max-width: 767.98px) {
        .wm-cat-sidebar .card-body {
            display: flex;
            flex-wrap: nowrap;
            gap: 0.25rem;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding: 0.5rem !important;
            scrollbar-width: thin;
        }
        .wm-cat-sidebar h2.cat-heading { display: none; }
        .wm-cat-sidebar a {
            white-space: nowrap;
            flex-shrink: 0;
        }
        .wm-task-table .col-hide-mobile { display: none !important; }
        .wm-task-table th, .wm-task-table td { padding: 0.5rem 0.4rem; font-size: 0.8125rem; }
    }

    /* Priority pill (same shape as status, different colors) */
    .wm-priority-select {
        font-size: 0.7rem;
        font-weight: 600;
        border: 0;
        border-radius: 0.375rem;
        padding: 0.25rem 1.4rem 0.25rem 0.55rem;
        cursor: pointer;
        appearance: none;
        background-position: right 0.35rem center;
        background-repeat: no-repeat;
        background-size: 12px;
    }
    .wm-priority-select:focus { outline: 2px solid rgba(79, 70, 229, 0.3); }
    .wm-priority-low    { color:#fff; background-color:#71717A; background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 16 16'%3e%3cpath d='M3.204 5h9.592L8 10.481 3.204 5z'/%3e%3c/svg%3e"); }
    .wm-priority-medium { color:#fff; background-color:#F59E0B; background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 16 16'%3e%3cpath d='M3.204 5h9.592L8 10.481 3.204 5z'/%3e%3c/svg%3e"); }
    .wm-priority-high   { color:#fff; background-color:#DC2626; background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 16 16'%3e%3cpath d='M3.204 5h9.592L8 10.481 3.204 5z'/%3e%3c/svg%3e"); }

    /* Bare inline select (assignee, category) — looks like text until focused */
    .wm-bare-select {
        border: 1px solid transparent;
        background: transparent;
        font-size: inherit;
        color: inherit;
        padding: 0.15rem 0.35rem;
        border-radius: 0.25rem;
        max-width: 100%;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
    }
    .wm-bare-select:hover  { border-color: var(--wm-border); background: var(--wm-surface); }
    .wm-bare-select:focus  { border-color: var(--bs-primary); outline: 0; background: var(--wm-surface); }

    /* Inline date input — same look as bare text, becomes editable on hover/click */
    .wm-inline-date {
        border: 1px solid transparent;
        background: transparent;
        font-size: inherit;
        color: inherit;
        padding: 0.1rem 0.35rem;
        border-radius: 0.25rem;
        cursor: pointer;
        font-family: inherit;
        max-width: 7.5rem;
    }
    .wm-inline-date:hover { border-color: var(--wm-border); background: var(--wm-surface); }
    .wm-inline-date:focus { border-color: var(--bs-primary); outline: 0; background: var(--wm-surface); }

    /* Category-dot dropdown — coloured dot stays visible left of the select */
    .wm-cat-pick {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        position: relative;
    }
    .wm-cat-pick .dot {
        display: inline-block;
        width: .55rem; height: .55rem;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .wm-cat-pick select {
        border: 1px dashed transparent;
        background: transparent;
        font-size: 0.7rem !important;
        font-weight: 400 !important;
        color: var(--wm-muted) !important;
        padding: 0 0.15rem;
        border-radius: 0.2rem;
        appearance: none;
        cursor: pointer;
        max-width: 5.5rem;
        line-height: 1.3;
    }
    .wm-cat-pick select:hover { border-color: var(--wm-border); }
    .wm-cat-pick select:focus { border-color: var(--bs-primary); outline: 0; }

    /* Task title — reduce weight a notch so the cell doesn't feel heavier
       than the rest of the row when other inline controls sit beside it. */
    .wm-task-table td a.fw-semibold { font-weight: 500 !important; }

    /* Notes pencil */
    .wm-notes-btn {
        background: transparent;
        border: 0;
        color: var(--wm-muted);
        padding: 0 0.25rem;
        cursor: pointer;
        font-size: 0.75rem;
    }
    .wm-notes-btn:hover { color: var(--bs-warning); }
    .wm-notes-btn.has-notes { color: var(--bs-warning); }

    /* Saving / error feedback */
    .wm-saved { animation: wm-flash 0.7s ease-in-out; }
    .wm-error { outline: 2px solid var(--bs-danger) !important; }
    @keyframes wm-flash {
        0%   { background: #DCFCE7; }
        100% { background: transparent; }
    }

    /* Inline status pill */
    .wm-status-select {
        font-size: 0.7rem;
        font-weight: 500;
        border: 0;
        border-radius: 0.375rem;
        padding: 0.25rem 1.5rem 0.25rem 0.6rem;
        cursor: pointer;
        appearance: none;
        background-position: right 0.4rem center;
        background-repeat: no-repeat;
        background-size: 14px;
    }
    .wm-status-select:focus { outline: 2px solid rgba(79, 70, 229, 0.3); }
    .wm-status-todo        { color:#fff; background-color:#71717A; background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 16 16'%3e%3cpath d='M3.204 5h9.592L8 10.481 3.204 5z'/%3e%3c/svg%3e"); }
    .wm-status-wip         { color:#fff; background-color:#3b82f6; background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 16 16'%3e%3cpath d='M3.204 5h9.592L8 10.481 3.204 5z'/%3e%3c/svg%3e"); }
    .wm-status-first_draft { color:#fff; background-color:#f59e0b; background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 16 16'%3e%3cpath d='M3.204 5h9.592L8 10.481 3.204 5z'/%3e%3c/svg%3e"); }
    .wm-status-done        { color:#fff; background-color:#10b981; background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 16 16'%3e%3cpath d='M3.204 5h9.592L8 10.481 3.204 5z'/%3e%3c/svg%3e"); }
    .wm-status-hold        { color:#fff; background-color:#7c3aed; background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='white' viewBox='0 0 16 16'%3e%3cpath d='M3.204 5h9.592L8 10.481 3.204 5z'/%3e%3c/svg%3e"); }

    /* Hold banner under task headline */
    .wm-hold-banner {
        background: #F5F3FF;
        border-left: 3px solid #7C3AED;
        color: #5B21B6;
        font-size: 0.7rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        margin-top: 0.25rem;
        display: inline-block;
        max-width: 100%;
    }
    [data-bs-theme="dark"] .wm-hold-banner {
        background: rgba(124, 58, 237, 0.15);
        color: #C4B5FD;
    }
    /* Hold-row subtle tint */
    .wm-task-table tr.wm-row-hold { background: rgba(124, 58, 237, 0.04); }
    [data-bs-theme="dark"] .wm-task-table tr.wm-row-hold { background: rgba(124, 58, 237, 0.1); }

    /* Clickable rows → open detail panel */
    .wm-task-table tr[data-task-id] { cursor: pointer; }
    .wm-task-table tr.wm-row-selected {
        background: rgba(79, 70, 229, 0.06) !important;
        box-shadow: inset 3px 0 0 var(--bs-primary);
    }
    [data-bs-theme="dark"] .wm-task-table tr.wm-row-selected {
        background: rgba(99, 102, 241, 0.12) !important;
    }

    /* Right detail panel */
    .wm-task-panel-col .card { border: 1px solid var(--wm-border); }
    .wm-task-panel-inner {
        max-height: calc(100vh - 7rem);
        overflow-y: auto;
        padding: 1rem;
    }
    .wm-task-panel-inner.wm-task-panel-loading { opacity: 0.4; pointer-events: none; }
    .wm-task-panel-inner .card { background: transparent; }
    /* Sticky positioning so it stays in view while scrolling the list */
    @media (min-width: 992px) {
        .wm-task-panel-col > .card { position: sticky; top: 5rem; }

        /* Custom column widths: narrower sidebar → wider list/panel.
           Bootstrap's col-lg-3 (25%) was too wide for what the sidebar holds. */
        .wm-cat-sidebar             { flex: 0 0 19%; max-width: 19%; }
        #wm-list-col.col-lg-9       { flex: 0 0 81%; max-width: 81%; }   /* panel closed */
        #wm-list-col.col-lg-5       { flex: 0 0 47%; max-width: 47%; }   /* panel open */
        .wm-task-panel-col.col-lg-4 { flex: 0 0 34%; max-width: 34%; }
    }

    /* Sidebar date inputs were getting cramped — tighten font/padding so
       they fit comfortably in the narrower column. */
    .wm-cat-sidebar input[type="date"] {
        font-size: 0.75rem;
        padding: 0.25rem 0.4rem;
    }
</style>

<div class="container-fluid px-3 px-md-4">

    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0"><i class="fa-solid fa-gauge-high text-primary me-2"></i>Dashboard</h1>
        <a href="{{ route('tasks.create', ['category' => $selected?->id]) }}" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus me-1"></i>New Task
        </a>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-3" id="wm-dash-row">

        {{-- LEFT 25% — All Tasks + categories sidebar --}}
        <aside class="col-12 col-lg-3 wm-cat-sidebar">
            <div class="card border-0">
                <div class="card-body p-2">
                    <h2 class="h6 text-secondary text-uppercase small px-2 pt-2 pb-1 mb-1 cat-heading">View</h2>

                    {{-- All Tasks pseudo-entry --}}
                    @php $allParams = array_filter(['mine' => $onlyMine ? 1 : null, 'q' => $search ?: null, 'status' => $statusValue ?: null]); @endphp
                    <a href="{{ route('home', $allParams) }}"
                       class="d-flex align-items-center gap-2 px-2 py-2 rounded text-decoration-none {{ $isAllView ? 'bg-primary-subtle text-primary-emphasis' : 'text-reset' }}">
                        <i class="fa-solid fa-list text-secondary"></i>
                        <span class="flex-grow-1 fw-semibold small">All Tasks</span>
                        <span class="badge text-bg-light border">{{ $allCount }}</span>
                    </a>

                    {{-- On Hold pseudo-entry --}}
                    @php $holdParams = array_filter(['view' => 'hold', 'mine' => $onlyMine ? 1 : null]); @endphp
                    <a href="{{ route('home', $holdParams) }}"
                       class="d-flex align-items-center gap-2 px-2 py-2 rounded text-decoration-none {{ $isHoldView ? 'bg-primary-subtle text-primary-emphasis' : 'text-reset' }}">
                        <i class="fa-solid fa-circle-pause" style="color:#7C3AED"></i>
                        <span class="flex-grow-1 fw-semibold small">On Hold</span>
                        <span class="badge {{ $holdCount > 0 ? 'text-bg-light border' : 'text-bg-light border opacity-50' }}">{{ $holdCount }}</span>
                    </a>

                    {{-- Quick presets --}}
                    @php
                        $presetDefs = [
                            ['overdue',   '🔥 Overdue',          'fa-triangle-exclamation', '#EF4444'],
                            ['due_today', '⚡ Due today',         'fa-bolt',                 '#F59E0B'],
                            ['high',      '🔥 High priority',    'fa-fire',                 '#DC2626'],
                        ];
                    @endphp
                    @foreach ($presetDefs as [$key, $label, $icon, $color])
                        @php $isActive = $preset === $key; @endphp
                        <a href="{{ route('home', array_filter(['preset' => $key, 'mine' => $onlyMine ? 1 : null])) }}"
                           class="d-flex align-items-center gap-2 px-2 py-2 rounded text-decoration-none {{ $isActive ? 'bg-primary-subtle text-primary-emphasis' : 'text-reset' }}">
                            <i class="fa-solid {{ $icon }}" style="color:{{ $color }}"></i>
                            <span class="flex-grow-1 fw-semibold small">{{ Str::after($label, ' ') }}</span>
                            <span class="badge {{ ($presets[$key] ?? 0) > 0 ? 'text-bg-light border' : 'text-bg-light border opacity-50' }}">{{ $presets[$key] ?? 0 }}</span>
                        </a>
                    @endforeach

                    {{-- Done-tasks date window. Empty = this week (Mon–Fri). --}}
                    <form method="GET" action="{{ route('home') }}" class="px-2 mt-2">
                        @if ($selected)    <input type="hidden" name="category" value="{{ $selected->id }}"> @endif
                        @if ($isHoldView)  <input type="hidden" name="view" value="hold"> @endif
                        @if ($preset)      <input type="hidden" name="preset" value="{{ $preset }}"> @endif
                        @if ($onlyMine)    <input type="hidden" name="mine" value="1"> @endif
                        @if ($search)      <input type="hidden" name="q" value="{{ $search }}"> @endif
                        @if ($statusValue) <input type="hidden" name="status" value="{{ $statusValue }}"> @endif
                        @if ($website)     <input type="hidden" name="website" value="{{ $website }}"> @endif
                        @if ($assigneeId)  <input type="hidden" name="assignee" value="{{ $assigneeId }}"> @endif
                        <div class="d-flex align-items-center mb-1">
                            <i class="fa-regular fa-calendar text-secondary me-1" style="font-size:.75rem"></i>
                            <span class="text-secondary fw-semibold" style="font-size:.7rem; text-transform:uppercase; letter-spacing:.04em;">Done range</span>
                            @if ($fromDate || $toDate)
                                <a href="{{ route('home', array_filter(['category' => $selected?->id, 'view' => $isHoldView ? 'hold' : null, 'preset' => $preset ?: null, 'mine' => $onlyMine ? 1 : null, 'q' => $search ?: null, 'status' => $statusValue ?: null, 'website' => $website ?: null, 'assignee' => $assigneeId ?: null])) }}"
                                   class="ms-auto text-secondary text-decoration-none small" title="Clear range — back to this week">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                        <input type="date" name="from" value="{{ $fromDate }}" class="form-control form-control-sm mb-1" placeholder="From" title="From">
                        <input type="date" name="to"   value="{{ $toDate }}"   class="form-control form-control-sm mb-1" placeholder="To"   title="To">
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">Apply range</button>
                        <div class="text-secondary mt-1" style="font-size:.65rem;">
                            @if ($fromDate || $toDate)
                                Custom range active
                            @else
                                Showing this week (Mon–Fri)
                            @endif
                        </div>
                    </form>

                    <hr class="my-2">

                    @forelse ($categories as $category)
                        @php $isActive = $selected && $selected->id === $category->id; @endphp
                        <a href="{{ route('home', array_filter(['category' => $category->id, 'mine' => $onlyMine ? 1 : null, 'q' => $search ?: null, 'status' => $statusValue ?: null])) }}"
                           class="d-flex align-items-center gap-2 px-2 py-2 rounded text-decoration-none {{ $isActive ? 'bg-primary-subtle text-primary-emphasis' : 'text-reset' }}">
                            <span class="rounded-circle d-inline-block" style="width:.65rem;height:.65rem;background:{{ $category->color }}"></span>
                            <span class="flex-grow-1 fw-semibold small">{{ $category->name }}</span>
                            <span class="badge text-bg-light border">{{ $category->task_count }}</span>
                        </a>
                    @empty
                        <p class="text-secondary small px-2 mb-0">No categories yet.</p>
                    @endforelse
                </div>
            </div>
            @can('create', App\Models\Category::class)
                <div class="mt-2">
                    <a href="{{ route('categories.create') }}" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="fa-solid fa-plus me-1"></i>New Category
                    </a>
                </div>
            @endcan
        </aside>

        {{-- MIDDLE — task list. Shrinks when right panel is open. --}}
        <main class="col-12 col-lg-9" id="wm-list-col">
            <div class="card border-0">
                <div class="card-body">
                    {{-- Header --}}
                    @php
                        $presetLabels = [
                            'overdue'   => ['🔥 Overdue', '#EF4444'],
                            'due_today' => ['⚡ Due today', '#F59E0B'],
                            'high'      => ['🔥 High priority', '#DC2626'],
                            'done_week' => ['✅ Done this week', '#10B981'],
                        ];
                    @endphp
                    <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
                        @if ($preset && isset($presetLabels[$preset]))
                            <h2 class="h5 mb-0" style="color:{{ $presetLabels[$preset][1] }}">{{ $presetLabels[$preset][0] }}</h2>
                        @elseif ($isHoldView)
                            <i class="fa-solid fa-circle-pause fs-5" style="color:#7C3AED"></i>
                            <h2 class="h5 mb-0">On Hold</h2>
                        @elseif ($isAllView)
                            <i class="fa-solid fa-list fs-5 text-secondary"></i>
                            <h2 class="h5 mb-0">All Tasks</h2>
                        @else
                            <span class="rounded d-inline-block" style="width:1.5rem;height:1.5rem;background:{{ $selected->color }}"></span>
                            <h2 class="h5 mb-0">{{ $selected->name }}</h2>
                        @endif
                        <span class="badge text-bg-light border">{{ $tasks->count() }} task(s)</span>
                    </div>

                    {{-- Filter bar --}}
                    <form method="GET" action="{{ route('home') }}" class="row g-2 align-items-center mb-3">
                        @if ($selected) <input type="hidden" name="category" value="{{ $selected->id }}"> @endif
                        @if ($isHoldView) <input type="hidden" name="view" value="hold"> @endif
                        @if ($preset)     <input type="hidden" name="preset" value="{{ $preset }}"> @endif
                        @if ($fromDate)   <input type="hidden" name="from" value="{{ $fromDate }}"> @endif
                        @if ($toDate)     <input type="hidden" name="to" value="{{ $toDate }}"> @endif

                        <div class="col-md-4">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass text-secondary"></i></span>
                                <input type="text" name="q" value="{{ $search }}"
                                       class="form-control form-control-sm"
                                       placeholder="Search headline, description, website...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select form-select-sm">
                                <option value="">All statuses</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected($statusValue === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="website" class="form-select form-select-sm">
                                <option value="">All websites</option>
                                @foreach ($priorWebsites as $w)
                                    <option value="{{ $w }}" @selected($website === $w)>{{ Str::limit($w, 30) }}</option>
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
                        @if ($isAdmin)
                            <div class="col-md-auto">
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="onlyMine" name="mine" value="1"
                                           @checked($onlyMine) onchange="this.form.submit()">
                                    <label class="form-check-label small" for="onlyMine">Only mine</label>
                                </div>
                            </div>
                        @endif
                        <div class="col-md-auto d-flex gap-1">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fa-solid fa-filter me-1"></i>Filter
                            </button>
                            @if ($search || $statusValue || $website || $assigneeId)
                                <a href="{{ route('home', array_filter(['category' => $selected?->id, 'view' => $isHoldView ? 'hold' : null, 'preset' => $preset ?: null, 'mine' => $onlyMine ? 1 : null])) }}" class="btn btn-sm btn-outline-secondary" title="Clear filters">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                            @endif
                        </div>
                    </form>

                    {{-- Tasks table --}}
                    @if ($tasks->isEmpty())
                        <p class="text-secondary small mb-0">
                            @if ($search || $statusValue)
                                Koi task nahi mila in filters ke saath.
                            @else
                                Koi task nahi.
                            @endif
                        </p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 wm-task-table">
                                <thead>
                                    <tr>
                                        <th style="width:5rem">Priority</th>
                                        <th>Task headline</th>
                                        <th>Assignee</th>
                                        <th>Assigned</th>
                                        <th>Timeline</th>
                                        <th class="text-end">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($tasks as $task)
                                        <tr data-task-id="{{ $task->id }}" class="{{ $task->status === App\Enums\TaskStatus::HOLD ? 'wm-row-hold' : '' }}">
                                            <td>
                                                @can('update', $task)
                                                    <select class="wm-priority-select wm-priority-{{ $task->priority->value }} wm-inline"
                                                            data-task-id="{{ $task->id }}" data-field="priority"
                                                            title="Change priority">
                                                        @foreach ($priorities as $value => $label)
                                                            <option value="{{ $value }}" @selected($task->priority->value === $value)>{{ $label }}</option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <span class="badge text-bg-{{ $task->priority->color() }}">{{ $task->priority->label() }}</span>
                                                @endcan
                                            </td>
                                            <td>
                                                <span @if ($task->delay_reason) title="Notes: {{ $task->delay_reason }}" style="cursor: help;" @endif>
                                                    {{-- Click-to-change category --}}
                                                    @can('update', $task)
                                                        <span class="wm-cat-pick me-1">
                                                            <span class="dot" style="background:{{ $task->category->color }}"></span>
                                                            <select class="wm-inline" data-task-id="{{ $task->id }}" data-field="category_id" data-reload="1" title="Change category">
                                                                @foreach ($allCategories as $cat)
                                                                    <option value="{{ $cat->id }}" @selected($task->category_id === $cat->id)>{{ $cat->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </span>
                                                    @else
                                                        <span class="d-inline-block rounded-circle me-1" style="width:.5rem;height:.5rem;background:{{ $task->category->color }};vertical-align:middle" title="{{ $task->category->name }}"></span>
                                                    @endcan
                                                    <a href="{{ route('tasks.show', $task) }}" class="text-decoration-none text-reset fw-semibold">
                                                        {{ $task->title }}
                                                    </a>
                                                    @can('update', $task)
                                                        <button type="button"
                                                                class="wm-notes-btn wm-no-panel {{ $task->delay_reason ? 'has-notes' : '' }}"
                                                                data-task-id="{{ $task->id }}"
                                                                data-current="{{ $task->delay_reason }}"
                                                                title="{{ $task->delay_reason ? 'Notes: '.$task->delay_reason : 'Add note' }}">
                                                            <i class="fa-solid fa-note-sticky"></i>
                                                        </button>
                                                    @elseif ($task->delay_reason)
                                                        <i class="fa-solid fa-note-sticky text-warning ms-1 small" aria-label="Has notes"></i>
                                                    @endcan
                                                    @if ($task->status->showsFinalUrl())
                                                        @if ($task->final_url)
                                                            <a href="{{ $task->final_url }}" target="_blank" rel="noopener"
                                                               class="ms-1 text-decoration-none" title="Final URL: {{ $task->final_url }}">
                                                                <i class="fa-solid fa-arrow-up-right-from-square text-primary small"></i>
                                                            </a>
                                                        @else
                                                            @can('update', $task)
                                                                <button type="button"
                                                                        class="btn btn-sm btn-link p-0 ms-1 text-primary wm-add-url"
                                                                        data-task-id="{{ $task->id }}"
                                                                        title="Add final URL">
                                                                    <i class="fa-solid fa-link small"></i>
                                                                    <span class="small">+ link</span>
                                                                </button>
                                                            @endcan
                                                        @endif
                                                    @endif
                                                </span>
                                                @if ($task->status === App\Enums\TaskStatus::HOLD && $task->hold_reason)
                                                    <div class="wm-hold-banner">
                                                        <i class="fa-solid fa-circle-pause me-1"></i>
                                                        <strong>On hold:</strong> {{ $task->hold_reason }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-secondary small">
                                                @can('update', $task)
                                                    <select class="wm-bare-select wm-inline" data-task-id="{{ $task->id }}" data-field="assigned_to" title="Reassign">
                                                        <option value="">— Unassigned —</option>
                                                        @foreach ($assignableUsers as $u)
                                                            <option value="{{ $u->id }}" @selected($task->assigned_to === $u->id)>{{ $u->name }}</option>
                                                        @endforeach
                                                        {{-- Current assignee may not be in $assignableUsers (e.g. admin assigned to a task that a member is editing) --}}
                                                        @if ($task->assignee && ! $assignableUsers->contains('id', $task->assignee->id))
                                                            <option value="{{ $task->assignee->id }}" selected>{{ $task->assignee->name }} (current)</option>
                                                        @endif
                                                    </select>
                                                @else
                                                    {{ $task->assignee?->name ?? '—' }}
                                                @endcan
                                            </td>
                                            <td class="text-secondary small col-hide-mobile">{{ $task->assigned_at?->format('d M') ?? '—' }}</td>
                                            <td class="small">
                                                @can('update', $task)
                                                    @php
                                                        $dueClass = '';
                                                        if ($task->due_date) {
                                                            if ($task->isOverdue())  $dueClass = 'wm-overdue';
                                                            elseif ($task->isDueToday()) $dueClass = 'wm-due-today';
                                                        }
                                                    @endphp
                                                    <span class="badge {{ $dueClass }}" style="{{ $dueClass ? 'padding: 0.15rem 0.3rem;' : 'background:transparent;color:inherit;padding:0;' }}">
                                                        @if ($dueClass === 'wm-overdue')   <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                                        @elseif ($dueClass === 'wm-due-today') <i class="fa-solid fa-bolt me-1"></i>
                                                        @endif
                                                        <input type="date" class="wm-inline-date wm-inline"
                                                               data-task-id="{{ $task->id }}" data-field="due_date"
                                                               data-reload="1"
                                                               value="{{ $task->due_date?->format('Y-m-d') }}"
                                                               style="{{ $dueClass ? 'color:#fff;' : '' }}"
                                                               title="Change timeline">
                                                    </span>
                                                @else
                                                    {{ $task->due_date?->format('d M') ?? '—' }}
                                                @endcan
                                            </td>
                                            <td class="text-end">
                                                @can('update', $task)
                                                    <form method="POST" action="{{ route('tasks.status', $task) }}" class="d-inline wm-status-form">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select name="status" class="wm-status-select wm-status-{{ $task->status->value }}"
                                                                data-original="{{ $task->status->value }}"
                                                                title="Click to change status">
                                                            @foreach ($statuses as $value => $label)
                                                                <option value="{{ $value }}" @selected($task->status->value === $value)>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </form>
                                                @else
                                                    <span class="badge text-bg-{{ $task->status->color() }}">{{ $task->status->label() }}</span>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </main>

        {{-- RIGHT — AJAX-loaded task detail panel. Hidden until a row is clicked. --}}
        <aside class="col-12 d-none wm-task-panel-col" id="wm-panel-col">
            <div class="card border-0">
                <div class="wm-task-panel-inner" id="wm-task-panel">
                    {{-- AJAX-injected content (tasks/_panel.blade.php) --}}
                </div>
            </div>
        </aside>
    </div>
</div>

<script>
    // Inline "+ link" button — prompt for final URL and AJAX save.
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.wm-add-url');
        if (!btn) return;
        e.preventDefault();
        const url = prompt('Paste the final URL (live / draft page link):');
        if (url === null || url.trim() === '') return;
        const fd = new FormData();
        fd.append('_token', '{{ csrf_token() }}');
        fd.append('_method', 'PATCH');
        fd.append('final_url', url.trim());
        fetch(`{{ url('tasks') }}/${btn.dataset.taskId}/final-url`, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .then(res => res.ok ? location.reload() : alert('Save failed — make sure URL starts with http(s)://'))
        .catch(() => alert('Save failed.'));
    });

    // ── Inline field save (shared helper) ──────────────────────────────────
    // Used by priority/assignee/category selects, due_date input, notes button,
    // and any panel-level inline edits. Reads data-task-id, data-field, and
    // optionally data-reload="1" to refresh after success.
    window.wmSaveInline = async function (taskId, field, value, opts = {}) {
        const { reload = false, signal = null } = opts;
        const fd = new FormData();
        fd.append('_token', '{{ csrf_token() }}');
        fd.append('_method', 'PATCH');
        fd.append('field', field);
        if (value !== null && value !== undefined) fd.append('value', value);
        try {
            const res = await fetch(`{{ url('tasks') }}/${taskId}/inline`, {
                method: 'POST',
                body: fd,
                signal,
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

    // Auto-wire any element with class wm-inline (select or input) to save on change.
    document.addEventListener('change', function (e) {
        const el = e.target.closest('.wm-inline');
        if (!el) return;
        const taskId = el.dataset.taskId;
        const field  = el.dataset.field;
        const reload = el.dataset.reload === '1';
        if (!taskId || !field) return;

        // Priority pill needs its colour class updated when value changes.
        if (field === 'priority' && el.classList.contains('wm-priority-select')) {
            el.classList.remove('wm-priority-low', 'wm-priority-medium', 'wm-priority-high');
            el.classList.add('wm-priority-' + el.value);
        }

        wmSaveInline(taskId, field, el.value, { reload })
            .then(() => {
                el.classList.add('wm-saved');
                setTimeout(() => el.classList.remove('wm-saved'), 700);
            })
            .catch(() => {
                el.classList.add('wm-error');
                setTimeout(() => el.classList.remove('wm-error'), 1500);
            });
    });

    // Notes pencil — prompt for text, save.
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.wm-notes-btn');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        const current = btn.dataset.current || '';
        const text = prompt('Notes / delay reason:', current);
        if (text === null) return; // cancelled
        wmSaveInline(btn.dataset.taskId, 'delay_reason', text, { reload: true });
    });

    // ── Click-to-edit text/textarea/url/hours ──────────────────────────────
    // For any element with class .wm-edit-text + data-task-id + data-field +
    // data-input (text|textarea|url|hours). Click swaps the span for an input
    // with Save/Cancel buttons. Save calls wmSaveInline.
    document.addEventListener('click', function (e) {
        const span = e.target.closest('.wm-edit-text');
        if (!span) return;
        // Already in edit mode?
        if (span.dataset.editing === '1') return;
        e.preventDefault();
        e.stopPropagation();
        wmEnterEditMode(span);
    });

    function wmEnterEditMode(span) {
        span.dataset.editing = '1';
        const inputType = span.dataset.input || 'text';
        const original  = span.dataset.original || '';
        const taskId    = span.dataset.taskId;
        const field     = span.dataset.field;
        const reload    = span.dataset.reload === '1';

        const wrap = document.createElement('span');
        wrap.className = 'wm-edit-wrap d-inline-block w-100';

        let input;
        if (inputType === 'textarea') {
            input = document.createElement('textarea');
            input.rows = Math.max(2, original.split('\n').length);
            input.className = 'form-control form-control-sm';
            input.value = original;
        } else if (inputType === 'hours') {
            // h + m two-field input
            const h = Math.floor(parseFloat(original || 0));
            const m = Math.round((parseFloat(original || 0) - h) * 60);
            input = document.createElement('span');
            input.className = 'd-inline-flex gap-1 align-items-center';
            input.innerHTML = `
                <input type="number" min="0" max="999" step="1" value="${h}" class="form-control form-control-sm" style="width:4rem;" data-h>
                <span class="text-secondary small">h</span>
                <input type="number" min="0" max="59" step="1" value="${m}" class="form-control form-control-sm" style="width:4rem;" data-m>
                <span class="text-secondary small">m</span>
            `;
        } else {
            input = document.createElement('input');
            input.type = inputType === 'url' ? 'url' : 'text';
            input.className = 'form-control form-control-sm';
            input.value = original;
        }
        wrap.appendChild(input);

        const btnSave   = document.createElement('button');
        btnSave.type   = 'button';
        btnSave.className = 'btn btn-sm btn-primary ms-1';
        btnSave.innerHTML = '<i class="fa-solid fa-check"></i>';

        const btnCancel = document.createElement('button');
        btnCancel.type  = 'button';
        btnCancel.className = 'btn btn-sm btn-link text-secondary ms-1 p-1';
        btnCancel.innerHTML = '<i class="fa-solid fa-xmark"></i>';

        wrap.appendChild(btnSave);
        wrap.appendChild(btnCancel);

        // Replace the span with the editor
        const placeholderText = span.dataset.placeholder || '—';
        const originalDisplay = span.textContent;
        span.style.display = 'none';
        span.parentNode.insertBefore(wrap, span.nextSibling);
        if (input.focus) input.focus();
        if (input.select) input.select();

        const restore = () => { wrap.remove(); span.style.display = ''; span.dataset.editing = ''; };

        btnCancel.addEventListener('click', restore);

        btnSave.addEventListener('click', async function () {
            let value;
            if (inputType === 'hours') {
                const h = Math.max(0, parseInt(input.querySelector('[data-h]').value || 0, 10));
                const m = Math.max(0, Math.min(59, parseInt(input.querySelector('[data-m]').value || 0, 10)));
                value = (h === 0 && m === 0) ? '' : (h + m / 60).toFixed(4);
            } else {
                value = input.value;
            }

            try {
                const res = await wmSaveInline(taskId, field, value, { reload });
                if (reload) return;
                // Swap displayed text from server (display string if provided)
                const newDisplay = (res && res.display && (res.display.label || res.display.name))
                    ? (res.display.label || res.display.name)
                    : (value === '' ? placeholderText : value);
                span.textContent = newDisplay;
                span.dataset.original = value;
                restore();
            } catch (err) {
                // Stay in edit mode so user can fix.
            }
        });

        // Esc cancels, Cmd/Ctrl-Enter saves
        wrap.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { e.preventDefault(); restore(); }
            if ((e.metaKey || e.ctrlKey) && e.key === 'Enter') { e.preventDefault(); btnSave.click(); }
            if (e.key === 'Enter' && inputType !== 'textarea') { e.preventDefault(); btnSave.click(); }
        });
    }

    // ── Right-side detail panel ────────────────────────────────────────────
    (function () {
        const listCol  = document.getElementById('wm-list-col');
        const panelCol = document.getElementById('wm-panel-col');
        const panelEl  = document.getElementById('wm-task-panel');
        const panelUrl = (id) => `{{ url('tasks') }}/${id}/panel`;

        function setPanelOpen(open) {
            if (open) {
                panelCol.classList.remove('d-none');
                panelCol.classList.add('col-lg-4');
                listCol.classList.remove('col-lg-9');
                listCol.classList.add('col-lg-5');
            } else {
                panelCol.classList.add('d-none');
                panelCol.classList.remove('col-lg-4');
                listCol.classList.add('col-lg-9');
                listCol.classList.remove('col-lg-5');
            }
        }

        function highlightRow(taskId) {
            document.querySelectorAll('.wm-task-table tr[data-task-id]').forEach(tr => {
                tr.classList.toggle('wm-row-selected', tr.dataset.taskId === String(taskId));
            });
        }

        function updateUrl(taskId) {
            const url = new URL(window.location.href);
            if (taskId) url.searchParams.set('task', taskId);
            else        url.searchParams.delete('task');
            window.history.replaceState({}, '', url.toString());
        }

        function openPanel(taskId) {
            if (!taskId) return;
            setPanelOpen(true);
            highlightRow(taskId);
            updateUrl(taskId);

            panelEl.classList.add('wm-task-panel-loading');
            fetch(panelUrl(taskId), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            })
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.text();
            })
            .then(html => {
                panelEl.innerHTML = html;
                panelEl.classList.remove('wm-task-panel-loading');
                panelEl.scrollTop = 0;
                // On mobile, scroll the panel into view.
                if (window.innerWidth < 992) {
                    panelCol.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            })
            .catch(err => {
                console.error('Panel load failed:', err);
                panelEl.innerHTML = '<div class="alert alert-danger small m-3">Failed to load task details. <button type="button" class="btn btn-sm btn-link p-0 align-baseline" onclick="location.reload()">Refresh</button></div>';
                panelEl.classList.remove('wm-task-panel-loading');
            });
        }

        function closePanel() {
            setPanelOpen(false);
            highlightRow(null);
            updateUrl(null);
            panelEl.innerHTML = '';
        }

        // Row click → open panel. Modifier keys / interactive sub-elements pass through.
        document.addEventListener('click', function (e) {
            const row = e.target.closest('.wm-task-table tr[data-task-id]');
            if (!row) return;
            // Let Ctrl/Cmd/Shift-click open the full page in a new tab (normal nav).
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
            // Don't intercept interactive elements inside the row.
            if (e.target.closest('select, form, button, input, a[target="_blank"], .wm-inline, .wm-no-panel')) return;

            e.preventDefault();
            openPanel(row.dataset.taskId);
        });

        // Close button inside the panel (delegated; panel HTML is injected later).
        document.addEventListener('click', function (e) {
            if (e.target.closest('.wm-panel-close')) {
                e.preventDefault();
                closePanel();
            }
        });

        // Deep-link: if URL has ?task=ID, auto-open on load.
        const params = new URLSearchParams(window.location.search);
        const initialTaskId = params.get('task');
        if (initialTaskId) openPanel(initialTaskId);
    })();

    // Inline status select — if user picks Hold, prompt for a reason
    // (same flow as Kanban drag-into-hold). Cancel reverts the select.
    document.addEventListener('change', function (e) {
        const sel = e.target.closest('.wm-status-select');
        if (!sel) return;
        const form = sel.closest('.wm-status-form');
        if (!form) return;

        if (sel.value === 'hold') {
            const reason = prompt('Why is this task on hold? (Reason zaroori hai)');
            if (reason === null || reason.trim() === '') {
                sel.value = sel.dataset.original;
                return;
            }
            // Reuse if it already exists (in case user changes mind multiple times)
            let hidden = form.querySelector('input[name="hold_reason"]');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'hold_reason';
                form.appendChild(hidden);
            }
            hidden.value = reason.trim();
        }
        form.submit();
    });
</script>
@endsection
