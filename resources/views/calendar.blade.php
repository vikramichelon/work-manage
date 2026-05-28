@extends('layouts.app')

@section('content')
<style>
    .wm-cal {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 1px;
        background: var(--wm-border);
        border: 1px solid var(--wm-border);
        border-radius: 0.625rem;
        overflow: hidden;
    }
    .wm-cal-header,
    .wm-cal-cell {
        background: var(--wm-surface);
        padding: 0.5rem;
    }
    .wm-cal-header {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--wm-muted);
        font-weight: 500;
        text-align: center;
        padding: 0.6rem 0.5rem;
    }
    .wm-cal-cell {
        min-height: 100px;
        text-decoration: none;
        color: inherit;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        transition: background-color 0.1s ease;
    }
    .wm-cal-cell:hover { background: #F4F4F5; color: inherit; }
    [data-bs-theme="dark"] .wm-cal-cell:hover { background: #27272A; }
    .wm-cal-other  { opacity: 0.4; }
    .wm-cal-today  { box-shadow: inset 0 0 0 2px #4F46E5; }
    .wm-cal-focus  { background: #EEF2FF !important; }
    [data-bs-theme="dark"] .wm-cal-focus { background: rgba(99, 102, 241, 0.18) !important; }
    .wm-cal-date {
        font-size: 0.8125rem;
        font-weight: 600;
    }
    .wm-cal-pill {
        font-size: 0.65rem;
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
        background: rgba(0,0,0,0.04);
        color: #3F3F46;
        display: flex;
        align-items: center;
        gap: 0.25rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    [data-bs-theme="dark"] .wm-cal-pill { background: rgba(255,255,255,0.06); color: #D4D4D8; }
    .wm-cal-dot { width: 0.5rem; height: 0.5rem; border-radius: 50%; flex-shrink: 0; }
    .wm-cal-more { font-size: 0.65rem; color: var(--wm-muted); }

    /* ── Mobile (< 768px): compact grid ───────────────────────────────── */
    @media (max-width: 767.98px) {
        .wm-cal-header { padding: 0.4rem 0.25rem; font-size: 0.6rem; }
        .wm-cal-cell  { min-height: 60px; padding: 0.25rem; gap: 0.15rem; }
        .wm-cal-date  { font-size: 0.7rem; }
        .wm-cal-pill {
            font-size: 0; /* hide text, keep just the dot */
            padding: 0.1rem;
            background: transparent;
            justify-content: center;
        }
        .wm-cal-dot   { width: 0.45rem; height: 0.45rem; }
        .wm-cal-more  { font-size: 0.55rem; }
    }
</style>

<div class="container-fluid px-3 px-md-4">

    {{-- Top bar --}}
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h1 class="h4 mb-0">
            <i class="fa-regular fa-calendar text-primary me-2"></i>Calendar
        </h1>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('calendar', array_filter(['month' => $cursor->copy()->subMonth()->format('Y-m'), 'category' => $categoryId ?: null, 'status' => $statusValue ?: null])) }}"
               class="btn btn-sm btn-outline-secondary" title="Previous month">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <span class="fw-semibold px-2">{{ $cursor->format('F Y') }}</span>
            <a href="{{ route('calendar', array_filter(['month' => $cursor->copy()->addMonth()->format('Y-m'), 'category' => $categoryId ?: null, 'status' => $statusValue ?: null])) }}"
               class="btn btn-sm btn-outline-secondary" title="Next month">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
            <a href="{{ route('calendar', array_filter(['category' => $categoryId ?: null, 'status' => $statusValue ?: null])) }}"
               class="btn btn-sm btn-outline-secondary ms-1">Today</a>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('calendar') }}" class="row g-2 align-items-center mb-3">
        <input type="hidden" name="month" value="{{ $cursor->format('Y-m') }}">
        <div class="col-md-3">
            <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}" @selected($categoryId === $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($statusValue === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        @if ($categoryId || $statusValue)
            <div class="col-auto">
                <a href="{{ route('calendar', ['month' => $cursor->format('Y-m')]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-xmark me-1"></i>Clear
                </a>
            </div>
        @endif
        <div class="col-auto ms-auto text-secondary small">
            <i class="fa-regular fa-circle-dot me-1"></i>Showing tasks by <strong>Timeline</strong> date
        </div>
    </form>

    {{-- Calendar grid --}}
    <div class="wm-cal mb-3">
        @foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dow)
            <div class="wm-cal-header">{{ $dow }}</div>
        @endforeach

        @foreach ($days as $day)
            @php
                $key = $day->toDateString();
                $dayTasks = $tasks->get($key, collect());
                $isOtherMonth = $day->month !== $monthStart->month;
                $isToday = $day->isToday();
                $isFocus = $focusDate && $day->isSameDay($focusDate);
                $cellClass = 'wm-cal-cell';
                if ($isOtherMonth) $cellClass .= ' wm-cal-other';
                if ($isToday)      $cellClass .= ' wm-cal-today';
                if ($isFocus)      $cellClass .= ' wm-cal-focus';
            @endphp
            <a href="{{ route('calendar', array_filter(['month' => $cursor->format('Y-m'), 'day' => $key, 'category' => $categoryId ?: null, 'status' => $statusValue ?: null])) }}"
               class="{{ $cellClass }}">
                <div class="wm-cal-date">{{ $day->day }}</div>

                @foreach ($dayTasks->take(3) as $task)
                    <div class="wm-cal-pill" title="{{ $task->title }}">
                        <span class="wm-cal-dot" style="background:{{ $task->category->color }}"></span>
                        <span class="text-truncate">{{ $task->title }}</span>
                    </div>
                @endforeach
                @if ($dayTasks->count() > 3)
                    <div class="wm-cal-more">+ {{ $dayTasks->count() - 3 }} more</div>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Focused day detail --}}
    @if ($focusDate)
        <div class="card border-0">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h5 mb-0">
                        <i class="fa-regular fa-calendar-check text-primary me-2"></i>
                        Tasks on {{ $focusDate->format('d M Y') }}
                        <span class="badge text-bg-light border ms-2">{{ $focusTasks->count() }}</span>
                    </h2>
                </div>

                @if ($focusTasks->isEmpty())
                    <p class="text-secondary small mb-0">Is din koi task nahi.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width:5rem">Priority</th>
                                    <th>Client / Website</th>
                                    <th>Category</th>
                                    <th>Assignee</th>
                                    <th class="text-end">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($focusTasks as $task)
                                    <tr>
                                        <td><span class="badge text-bg-{{ $task->priority->color() }}">{{ $task->priority->label() }}</span></td>
                                        <td>
                                            <a href="{{ route('tasks.show', $task) }}" class="text-decoration-none text-reset fw-semibold">{{ $task->title }}</a>
                                        </td>
                                        <td>
                                            <span class="badge text-bg-light border">
                                                <span class="d-inline-block rounded-circle me-1" style="width:.4rem;height:.4rem;background:{{ $task->category->color }};vertical-align:middle"></span>
                                                {{ $task->category->name }}
                                            </span>
                                        </td>
                                        <td class="text-secondary small">{{ $task->assignee?->name ?? '—' }}</td>
                                        <td class="text-end"><span class="badge text-bg-{{ $task->status->color() }}">{{ $task->status->label() }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
