<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:admin']);
    }

    public function index(): View
    {
        $now       = Carbon::now();
        $weekStart = $now->copy()->startOfWeek();
        $todoVal   = TaskStatus::TODO->value;
        $wipVal    = TaskStatus::WIP->value;
        $draftVal  = TaskStatus::FIRST_DRAFT->value;
        $doneVal   = TaskStatus::DONE->value;
        $holdVal   = TaskStatus::HOLD->value;

        // ── KPI tiles ────────────────────────────────────────────────────
        $openCount = Task::where('status', '!=', $doneVal)->count();

        $overdueCount = Task::where('status', '!=', $doneVal)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $now->toDateString())
            ->count();

        $doneThisWeek = Task::where('status', $doneVal)
            ->where('completed_at', '>=', $weekStart)
            ->count();

        // Average completion time (in hours) for tasks done in the last 30 days.
        $avgHours = (float) Task::where('status', $doneVal)
            ->whereNotNull('completed_at')
            ->whereNotNull('assigned_at')
            ->where('completed_at', '>=', $now->copy()->subDays(30))
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, assigned_at, completed_at)) as avg_hrs')
            ->value('avg_hrs');

        // ── Per-person breakdown ─────────────────────────────────────────
        $perPerson = User::query()
            ->leftJoin('tasks', 'tasks.assigned_to', '=', 'users.id')
            ->groupBy('users.id', 'users.name', 'users.role')
            ->select('users.id', 'users.name', 'users.role')
            ->selectRaw(
                'SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) AS todo_count,
                 SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) AS wip_count,
                 SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) AS draft_count,
                 SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) AS done_count,
                 SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) AS hold_count,
                 COUNT(tasks.id) AS total_count',
                [$todoVal, $wipVal, $draftVal, $doneVal, $holdVal]
            )
            ->orderBy('users.name')
            ->get();

        // ── Per-category breakdown ───────────────────────────────────────
        $perCategory = Category::query()
            ->leftJoin('tasks', 'tasks.category_id', '=', 'categories.id')
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->select('categories.id', 'categories.name', 'categories.color')
            ->selectRaw(
                'SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) AS todo_count,
                 SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) AS wip_count,
                 SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) AS draft_count,
                 SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) AS done_count,
                 SUM(CASE WHEN tasks.status = ? THEN 1 ELSE 0 END) AS hold_count,
                 COUNT(tasks.id) AS total_count',
                [$todoVal, $wipVal, $draftVal, $doneVal, $holdVal]
            )
            ->orderBy('categories.name')
            ->get();

        // ── Last 14 days — tasks completed per day ───────────────────────
        $days = collect();
        for ($i = 13; $i >= 0; $i--) {
            $d = $now->copy()->subDays($i);
            $count = Task::where('status', $doneVal)
                ->whereDate('completed_at', $d->toDateString())
                ->count();
            $days->push([
                'date'  => $d->format('Y-m-d'),
                'label' => $d->format('d M'),
                'count' => $count,
            ]);
        }

        return view('reports.index', compact(
            'openCount', 'overdueCount', 'doneThisWeek', 'avgHours',
            'perPerson', 'perCategory', 'days'
        ));
    }
}
