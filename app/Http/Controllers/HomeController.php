<?php

namespace App\Http\Controllers;

use App\Enums\Priority;
use App\Enums\Role;
use App\Enums\TaskStatus;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Dashboard: sidebar of categories + tasks for the selected scope.
     * Default scope = ALL tasks across categories (no category in URL).
     * Picking a category narrows to just that one.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $onlyMine = $user->isAdmin() && $request->boolean('mine');

        // Filters
        $search     = trim((string) $request->query('q', ''));
        $statusVal  = $request->query('status', '');
        $statusEnum = TaskStatus::tryFrom((string) $statusVal);
        $website    = trim((string) $request->query('website', ''));
        $assigneeId = (int) $request->query('assignee', 0);
        $preset     = (string) $request->query('preset', '');
        // Custom Done-task window (overrides the default current-week limit).
        $fromDate   = (string) $request->query('from', '');
        $toDate     = (string) $request->query('to', '');

        $scopePerUser = function ($query) use ($onlyMine, $user) {
            if ($user->isAdmin()) {
                if ($onlyMine) {
                    $query->where('assigned_to', $user->id);
                }
            } else {
                $query->where(function ($inner) use ($user) {
                    $inner->where('created_by', $user->id)
                          ->orWhere('assigned_to', $user->id);
                });
            }
        };

        // Per-category counts for sidebar (respect only-mine but ignore other filters).
        $categories = Category::query()
            ->withCount(['tasks as task_count' => $scopePerUser])
            ->orderBy('name')
            ->get();

        // "All Tasks" total count.
        $allCount = Task::query()->tap($scopePerUser)->count();

        // "On Hold" count (across all categories, per-user scope).
        $holdCount = Task::query()
            ->tap($scopePerUser)
            ->where('status', TaskStatus::HOLD->value)
            ->count();

        // Resolve selected scope from ?category=. Empty/0/invalid → All.
        // Special: ?view=hold → show only on-hold tasks.
        // Special: ?preset=overdue / due_today / high
        $isHoldView = $request->query('view') === 'hold';
        $selectedId = (int) $request->query('category');
        $selected = $selectedId > 0 ? $categories->firstWhere('id', $selectedId) : null;
        $isAllView = ! $isHoldView && ! $preset && $selected === null;

        $tasks = Task::query()
            ->when($selected, fn ($q) => $q->where('category_id', $selected->id))
            ->when($isHoldView, fn ($q) => $q->where('status', TaskStatus::HOLD->value))
            ->tap($scopePerUser)
            ->when($search !== '', fn ($q) => $q->where(function ($inner) use ($search) {
                $inner->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('assigned_by_name', 'like', "%{$search}%")
                      ->orWhere('website', 'like', "%{$search}%");
            }))
            ->when($statusEnum, fn ($q) => $q->where('status', $statusEnum->value))
            ->when($website !== '', fn ($q) => $q->where('website', $website))
            ->when($assigneeId > 0, fn ($q) => $q->where('assigned_to', $assigneeId))
            ->when($preset, fn ($q) => $this->applyPreset($q, $preset))
            ->tap(fn ($q) => self::applyDoneWindow($q, $fromDate, $toDate))
            ->byPriority()
            ->with(['assignee', 'creator', 'category'])
            ->get();

        return view('home', [
            'categories'  => $categories,
            'selected'    => $selected,
            'isAllView'   => $isAllView,
            'isHoldView'  => $isHoldView,
            'allCount'    => $allCount,
            'holdCount'   => $holdCount,
            'tasks'       => $tasks,
            'statuses'    => TaskStatus::options(),
            'priorities'  => Priority::options(),
            'allCategories' => $categories, // reuse — already loaded with counts
            'assignableUsers' => $this->inlineAssignees($user),
            'search'      => $search,
            'statusValue' => $statusEnum?->value ?? '',
            'website'     => $website,
            'assigneeId'  => $assigneeId,
            'preset'      => $preset,
            'fromDate'    => $fromDate,
            'toDate'      => $toDate,
            'priorWebsites'   => $this->websiteOptions($scopePerUser),
            'presets'         => $this->presetCounts($scopePerUser),
            'onlyMine'    => $onlyMine,
            'isAdmin'     => $user->isAdmin(),
        ]);
    }

    /** Apply preset filter onto the task query. */
    public static function applyPreset($query, string $preset)
    {
        $today = Carbon::today();
        return match ($preset) {
            'overdue' => $query
                ->whereNotIn('status', [TaskStatus::DONE->value, TaskStatus::HOLD->value])
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', $today->toDateString()),
            'due_today' => $query
                ->whereNotIn('status', [TaskStatus::DONE->value, TaskStatus::HOLD->value])
                ->whereDate('due_date', $today->toDateString()),
            'high' => $query
                ->where('priority', Priority::HIGH->value),
            default => $query,
        };
    }

    /**
     * Limit Done tasks to a date window on completed_at. When no window is
     * supplied, default to the current week (Mon–Fri). Tasks in any other
     * status pass through untouched.
     */
    public static function applyDoneWindow($query, ?string $from, ?string $to)
    {
        $start = $from ? Carbon::parse($from)->startOfDay() : null;
        $end   = $to   ? Carbon::parse($to)->endOfDay()     : null;

        if (! $start && ! $end) {
            $start = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $end   = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays(4)->endOfDay();
        }

        return $query->where(function ($outer) use ($start, $end) {
            $outer->where('status', '!=', TaskStatus::DONE->value)
                  ->orWhere(function ($d) use ($start, $end) {
                      $d->where('status', TaskStatus::DONE->value);
                      if ($start) $d->where('completed_at', '>=', $start);
                      if ($end)   $d->where('completed_at', '<=', $end);
                  });
        });
    }

    /** Distinct website values for the filter dropdown. */
    private function websiteOptions(\Closure $scopePerUser)
    {
        return Task::query()
            ->tap($scopePerUser)
            ->whereNotNull('website')
            ->where('website', '!=', '')
            ->distinct()
            ->orderBy('website')
            ->pluck('website');
    }

    /**
     * Assignees shown in inline dropdowns on dashboard/kanban. Admins see all
     * users; members see only other members (matches the form-level rule).
     */
    private function inlineAssignees(User $user)
    {
        return User::orderBy('name')
            ->when(! $user->isAdmin(), fn ($q) => $q->where('role', Role::MEMBER->value))
            ->get(['id', 'name', 'role']);
    }

    /** Counts for each preset (so sidebar shows numbers). */
    private function presetCounts(\Closure $scopePerUser): array
    {
        $base = fn () => Task::query()->tap($scopePerUser);

        return [
            'overdue'   => self::applyPreset($base(), 'overdue')->count(),
            'due_today' => self::applyPreset($base(), 'due_today')->count(),
            'high'      => self::applyPreset($base(), 'high')
                            ->whereNotIn('status', [TaskStatus::DONE->value, TaskStatus::HOLD->value])
                            ->count(),
        ];
    }
}
