<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        // Resolve month from ?month=YYYY-MM (defaults to current).
        try {
            $cursor = $request->filled('month')
                ? Carbon::createFromFormat('Y-m', $request->query('month'))->startOfMonth()
                : Carbon::today()->startOfMonth();
        } catch (\Throwable $e) {
            $cursor = Carbon::today()->startOfMonth();
        }

        $monthStart = $cursor->copy()->startOfMonth();
        $monthEnd   = $cursor->copy()->endOfMonth();
        $gridStart  = $monthStart->copy()->startOfWeek(Carbon::SUNDAY);
        $gridEnd    = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);

        // Build the day list for the grid (5 or 6 weeks).
        $days = [];
        for ($d = $gridStart->copy(); $d->lte($gridEnd); $d->addDay()) {
            $days[] = $d->copy();
        }

        // Filters
        $categoryId = (int) $request->query('category');
        $statusValue = $request->query('status', '');
        $statusEnum = TaskStatus::tryFrom((string) $statusValue);

        // Load tasks that have a Timeline (due_date) in this window.
        $tasks = Task::query()
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->when(! $user->isAdmin(), fn ($q) => $q->where(function ($inner) use ($user) {
                $inner->where('created_by', $user->id)
                      ->orWhere('assigned_to', $user->id);
            }))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($statusEnum, fn ($q) => $q->where('status', $statusEnum->value))
            ->with(['category', 'assignee'])
            ->byPriority()
            ->get()
            ->groupBy(fn (Task $t) => $t->due_date->toDateString());

        // Tasks for the focused day (if any).
        $focusDate = null;
        $focusTasks = collect();
        if ($request->filled('day')) {
            try {
                $focusDate = Carbon::createFromFormat('Y-m-d', $request->query('day'))->startOfDay();
                $focusTasks = $tasks->get($focusDate->toDateString(), collect());
            } catch (\Throwable $e) {
                $focusDate = null;
            }
        }

        return view('calendar', [
            'cursor'      => $cursor,
            'monthStart'  => $monthStart,
            'days'        => $days,
            'tasks'       => $tasks,
            'categories'  => Category::orderBy('name')->get(),
            'statuses'    => TaskStatus::options(),
            'categoryId'  => $categoryId,
            'statusValue' => $statusEnum?->value ?? '',
            'focusDate'   => $focusDate,
            'focusTasks'  => $focusTasks,
        ]);
    }
}
