@extends('layouts.app')

@section('content')
<style>
    .wm-kpi {
        background: var(--wm-surface);
        border: 1px solid var(--wm-border);
        border-radius: 0.625rem;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.875rem;
    }
    .wm-kpi-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.625rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.125rem;
        color: #fff;
    }
    .wm-kpi-label { font-size: 0.7rem; color: var(--wm-muted); text-transform: uppercase; letter-spacing: 0.04em; }
    .wm-kpi-value { font-size: 1.5rem; font-weight: 600; line-height: 1; }
    .chart-box { position: relative; height: 280px; }
</style>

<div class="container-fluid px-3 px-md-4">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0"><i class="fa-solid fa-chart-line text-primary me-2"></i>Reports</h1>
    </div>

    {{-- KPI tiles --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <div class="wm-kpi">
                <div class="wm-kpi-icon" style="background:#71717A"><i class="fa-regular fa-clock"></i></div>
                <div>
                    <div class="wm-kpi-label">Open tasks</div>
                    <div class="wm-kpi-value">{{ $openCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wm-kpi">
                <div class="wm-kpi-icon" style="background:#EF4444"><i class="fa-solid fa-triangle-exclamation"></i></div>
                <div>
                    <div class="wm-kpi-label">Overdue</div>
                    <div class="wm-kpi-value">{{ $overdueCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wm-kpi">
                <div class="wm-kpi-icon" style="background:#10B981"><i class="fa-solid fa-check"></i></div>
                <div>
                    <div class="wm-kpi-label">Done this week</div>
                    <div class="wm-kpi-value">{{ $doneThisWeek }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="wm-kpi">
                <div class="wm-kpi-icon" style="background:#4F46E5"><i class="fa-regular fa-hourglass"></i></div>
                <div>
                    <div class="wm-kpi-label">Avg done time (30d)</div>
                    <div class="wm-kpi-value">{{ $avgHours ? round($avgHours, 1) . 'h' : '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts row 1 --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="card border-0">
                <div class="card-body">
                    <h2 class="h6 text-secondary text-uppercase small mb-3">Tasks per person</h2>
                    <div class="chart-box"><canvas id="chartPerson"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0">
                <div class="card-body">
                    <h2 class="h6 text-secondary text-uppercase small mb-3">Tasks by category</h2>
                    <div class="chart-box"><canvas id="chartCategory"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Daily completions --}}
    <div class="card border-0 mb-3">
        <div class="card-body">
            <h2 class="h6 text-secondary text-uppercase small mb-3">Completed tasks — last 14 days</h2>
            <div class="chart-box"><canvas id="chartDaily"></canvas></div>
        </div>
    </div>

    {{-- Per-person table --}}
    <div class="card border-0">
        <div class="card-body">
            <h2 class="h6 text-secondary text-uppercase small mb-3">Team breakdown</h2>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Person</th>
                            <th>Role</th>
                            <th class="text-end">To Do</th>
                            <th class="text-end">WIP</th>
                            <th class="text-end">First Draft</th>
                            <th class="text-end">Done</th>
                            <th class="text-end">Hold</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($perPerson as $p)
                            <tr>
                                <td class="fw-semibold">{{ $p->name }}</td>
                                <td><span class="badge text-bg-light border">{{ ucfirst($p->role->value ?? $p->role) }}</span></td>
                                <td class="text-end">{{ $p->todo_count }}</td>
                                <td class="text-end">{{ $p->wip_count }}</td>
                                <td class="text-end">{{ $p->draft_count }}</td>
                                <td class="text-end">{{ $p->done_count }}</td>
                                <td class="text-end">{{ $p->hold_count }}</td>
                                <td class="text-end fw-semibold">{{ $p->total_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-secondary text-center small">No data yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
    (function () {
        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        const gridColor = isDark ? '#27272A' : '#E4E4E7';
        const textColor = isDark ? '#A1A1AA' : '#52525B';

        Chart.defaults.font.family = "Inter, system-ui, sans-serif";
        Chart.defaults.color = textColor;
        Chart.defaults.borderColor = gridColor;

        // ── Per-person (stacked bar) ──────────────────────────────────────
        new Chart(document.getElementById('chartPerson'), {
            type: 'bar',
            data: {
                labels: @json($perPerson->pluck('name')),
                datasets: [
                    { label: 'To Do',       backgroundColor: '#71717A', data: @json($perPerson->pluck('todo_count')) },
                    { label: 'WIP',         backgroundColor: '#3B82F6', data: @json($perPerson->pluck('wip_count')) },
                    { label: 'First Draft', backgroundColor: '#F59E0B', data: @json($perPerson->pluck('draft_count')) },
                    { label: 'Done',        backgroundColor: '#10B981', data: @json($perPerson->pluck('done_count')) },
                    { label: 'Hold',        backgroundColor: '#7C3AED', data: @json($perPerson->pluck('hold_count')) },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    x: { stacked: true, grid: { color: gridColor } },
                    y: { stacked: true, beginAtZero: true, ticks: { precision: 0 }, grid: { color: gridColor } },
                },
                plugins: { legend: { position: 'bottom' } },
            },
        });

        // ── Per-category (donut) ──────────────────────────────────────────
        new Chart(document.getElementById('chartCategory'), {
            type: 'doughnut',
            data: {
                labels: @json($perCategory->pluck('name')),
                datasets: [{
                    data: @json($perCategory->pluck('total_count')),
                    backgroundColor: @json($perCategory->pluck('color')),
                    borderColor: isDark ? '#18181B' : '#FFFFFF',
                    borderWidth: 3,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
            },
        });

        // ── Daily completions (line) ──────────────────────────────────────
        new Chart(document.getElementById('chartDaily'), {
            type: 'line',
            data: {
                labels: @json($days->pluck('label')),
                datasets: [{
                    label: 'Completed',
                    data: @json($days->pluck('count')),
                    borderColor: '#4F46E5',
                    backgroundColor: 'rgba(79, 70, 229, 0.12)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 3,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: gridColor } },
                    x: { grid: { color: gridColor } },
                },
                plugins: { legend: { display: false } },
            },
        });
    })();
</script>
@endsection
