<?php

namespace App\Http\Controllers;

use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KanbanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): View
    {
        $user       = $request->user();
        $categoryId = (int) $request->query('category');
        $onlyMine   = $user->isAdmin() && $request->boolean('mine');
        $search     = trim((string) $request->query('q', ''));
        $website    = trim((string) $request->query('website', ''));
        $assigneeId = (int) $request->query('assignee', 0);
        $preset     = (string) $request->query('preset', '');
        $fromDate   = (string) $request->query('from', '');
        $toDate     = (string) $request->query('to', '');

        $scopePerUser = function ($query) use ($onlyMine, $user) {
            if (! $user->isAdmin()) {
                $query->where(function ($inner) use ($user) {
                    $inner->where('created_by', $user->id)
                          ->orWhere('assigned_to', $user->id);
                });
            }
            if ($onlyMine) {
                $query->where('assigned_to', $user->id);
            }
        };

        $tasks = Task::query()
            ->tap($scopePerUser)
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($search !== '', fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('assigned_by_name', 'like', "%{$search}%")
                      ->orWhere('website', 'like', "%{$search}%");
            }))
            ->when($website !== '', fn ($q) => $q->where('website', $website))
            ->when($assigneeId > 0, fn ($q) => $q->where('assigned_to', $assigneeId))
            ->when($preset, fn ($q) => HomeController::applyPreset($q, $preset))
            ->tap(fn ($q) => HomeController::applyDoneWindow($q, $fromDate, $toDate))
            ->byPriority()
            ->with(['category', 'assignee'])
            ->get()
            ->groupBy(fn (Task $t) => $t->status->value);

        // Hold goes in its own panel — exclude from the main column list.
        $columnStatuses = array_values(array_filter(
            TaskStatus::cases(),
            fn ($s) => $s !== TaskStatus::HOLD
        ));
        $holdTasks = $tasks->get(TaskStatus::HOLD->value, collect());

        $priorWebsites = Task::query()
            ->tap($scopePerUser)
            ->whereNotNull('website')->where('website', '!=', '')
            ->distinct()->orderBy('website')->pluck('website');

        return view('kanban', [
            'tasksByStatus'   => $tasks,
            'statuses'        => $columnStatuses,
            'holdTasks'       => $holdTasks,
            'categories'      => Category::orderBy('name')->get(),
            'categoryId'      => $categoryId,
            'onlyMine'        => $onlyMine,
            'isAdmin'         => $user->isAdmin(),
            'search'          => $search,
            'website'         => $website,
            'assigneeId'      => $assigneeId,
            'preset'          => $preset,
            'fromDate'        => $fromDate,
            'toDate'          => $toDate,
            'priorWebsites'   => $priorWebsites,
            'priorities'      => Priority::options(),
            'assignableUsers' => User::orderBy('name')
                ->when(! $user->isAdmin(), fn ($q) => $q->where('role', Role::MEMBER->value))
                ->get(['id', 'name', 'role']),
        ]);
    }
}
