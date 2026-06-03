<?php

namespace App\Http\Controllers;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Models\ActivityTemplate;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class TaskController extends Controller
{
    // Working window is defined on the Task model (10am–6pm, Mon–Fri).

    public function create(Request $request): View
    {
        $this->authorize('create', Task::class);

        return view('tasks.create', $this->formData(new Task([
            'category_id' => $request->integer('category') ?: null,
            'priority'    => Priority::MEDIUM->value,
            'status'      => TaskStatus::TODO->value,
            'assigned_at' => now()->toDateString(),
        ])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Task::class);

        $data = $this->validateTask($request);
        $data['activity_template_id'] = $this->resolveActivityTemplate($request, $data);
        $data['due_date'] = ($data['due_date'] ?? null)
            ?: $this->suggestDueDate((int) ($data['assigned_to'] ?? 0), $this->minutesFromData($data))->toDateString();

        // If the task is created already in WIP, start the timer.
        if ($data['status'] === TaskStatus::WIP->value) {
            $data['wip_started_at'] = now();
        }

        $task = Task::create([
            ...$data,
            'created_by'   => $request->user()->id,
            'completed_at' => $data['status'] === TaskStatus::DONE->value ? now() : null,
        ]);

        $this->saveUploadedAttachments($request, $task);

        // Always return to the All Tasks view — user explicitly asked for this.
        return redirect()->route('home')->with('status', 'Task created.');
    }

    public function show(Task $task): View
    {
        $this->authorize('view', $task);
        $task->load(['category', 'assignee', 'creator', 'comments.user', 'attachments.user']);

        $activities = \App\Models\Activity::where('task_id', $task->id)
            ->with('user')->latest('created_at')->get();

        return view('tasks.show', compact('task', 'activities'));
    }

    /**
     * HTML fragment of the task detail panel — used by the dashboard's
     * right column (AJAX-loaded). Returns the same content as show() but
     * without the page layout.
     */
    public function panel(Task $task): View
    {
        $this->authorize('view', $task);
        $task->load(['category', 'assignee', 'creator', 'comments.user', 'attachments.user']);

        $activities = \App\Models\Activity::where('task_id', $task->id)
            ->with('user')->latest('created_at')->get();

        $assignableUsers = User::orderBy('name')
            ->when(! auth()->user()->isAdmin(),
                fn ($q) => $q->where('role', \App\Enums\Role::MEMBER->value))
            ->get(['id', 'name', 'role']);

        return view('tasks._panel', [
            'task'            => $task,
            'activities'      => $activities,
            'inPanel'         => true,
            'priorities'      => Priority::options(),
            'assignableUsers' => $assignableUsers,
            'allCategories'   => Category::orderBy('name')->get(['id', 'name', 'color']),
        ]);
    }

    public function edit(Task $task): View
    {
        $this->authorize('update', $task);

        return view('tasks.edit', $this->formData($task));
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $oldStatus = $task->status;
        $data = $this->validateTask($request);
        $data['activity_template_id'] = $this->resolveActivityTemplate($request, $data);
        $data['completed_at'] = $data['status'] === TaskStatus::DONE->value
            ? ($task->completed_at ?? now())
            : null;

        // Auto-timer: enter/leave WIP folds elapsed working time into actual_hours.
        $newStatus = TaskStatus::from($data['status']);
        $data = array_merge($data, $this->applyTimerTransition($task, $oldStatus, $newStatus));

        $task->update($data);

        $this->saveUploadedAttachments($request, $task);

        return redirect()->route('home')->with('status', 'Task updated.');
    }

    /**
     * Accept file inputs named "attachments[]" from the task form and write
     * them as Attachment rows. Validates per-file (size + mime). Empty input
     * is a no-op, so legacy callers that don't include the field still work.
     */
    private function saveUploadedAttachments(Request $request, Task $task): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        $request->validate([
            'attachments.*' => [
                'file', 'max:10240',
                'mimes:jpg,jpeg,png,gif,webp,bmp,svg,heic,pdf,doc,docx,xls,xlsx,csv,txt,zip',
            ],
        ]);

        foreach ($request->file('attachments') as $file) {
            \App\Http\Controllers\AttachmentController::storeUploaded(
                $file, $task, $request->user()->id
            );
        }
    }

    public function destroy(Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);
        $task->delete();

        return redirect()->route('home')->with('status', 'Task deleted.');
    }

    /** Inline final-URL save from the dashboard/Kanban (single-field PATCH). */
    public function updateFinalUrl(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'final_url' => ['nullable', 'url', 'max:500'],
        ]);

        $task->update(['final_url' => $data['final_url'] ?? null]);

        if ($request->wantsJson()) {
            return response()->json([
                'ok'        => true,
                'final_url' => $task->final_url,
            ]);
        }

        return back()->with('status', 'Final URL saved.');
    }

    /**
     * Generic single-field inline edit from the dashboard / Kanban / panel.
     * Whitelisted fields only; each has its own validation rule.
     */
    public function updateInline(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $field = (string) $request->input('field');
        $rule  = $this->inlineFieldRule($field, $task);
        if ($rule === null) {
            abort(422, "Field '{$field}' is not inline-editable.");
        }

        $data = $request->validate(['value' => $rule]);
        $value = $data['value'] ?? null;

        // Normalize empty strings to null for nullable fields.
        if (in_array($field, ['assigned_to', 'due_date', 'delay_reason', 'description', 'estimated_hours', 'actual_hours', 'website', 'final_url'], true)
            && ($value === '' || $value === [])) {
            $value = null;
        }

        $task->update([$field => $value]);

        if ($request->wantsJson()) {
            $task->refresh()->loadMissing(['assignee', 'category']);
            return response()->json([
                'ok'    => true,
                'field' => $field,
                'value' => $task->{$field},
                // Convenient display strings the frontend can swap in.
                'display' => match ($field) {
                    'priority'        => ['label' => $task->priority->label(), 'color' => $task->priority->color()],
                    'assigned_to'     => ['name' => $task->assignee?->name ?? 'Unassigned'],
                    'due_date'        => ['label' => $task->due_date?->format('d M') ?? '—'],
                    'category_id'     => ['name' => $task->category->name, 'color' => $task->category->color],
                    'estimated_hours' => ['label' => $task->estimatedDisplay()],
                    'actual_hours'    => ['label' => $task->actualDisplay()],
                    default           => null,
                },
            ]);
        }

        return back()->with('status', 'Updated.');
    }

    /** Per-field validation rules for the inline endpoint. Returns null for non-whitelisted fields. */
    private function inlineFieldRule(string $field, Task $task): ?array
    {
        return match ($field) {
            'priority'        => ['required', new Enum(Priority::class)],
            'assigned_to'     => $this->assigneeRule($task, isUpdate: true),
            'due_date'        => ['nullable', 'date'],
            'delay_reason'    => ['nullable', 'string', 'max:5000'],
            'category_id'     => ['required', Rule::exists('categories', 'id')],
            'description'     => ['nullable', 'string', 'max:10000'],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'actual_hours'    => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'title'           => ['required', 'string', 'max:255'],
            'website'         => ['nullable', 'string', 'max:500'],
            'final_url'       => ['nullable', 'url', 'max:500'],
            default           => null,
        };
    }

    /** Shared rule: members can only assign to other members (+ keep existing assignee on edit). */
    private function assigneeRule(Task $task, bool $isUpdate): array
    {
        if (auth()->user()->isAdmin()) {
            return ['nullable', Rule::exists('users', 'id')];
        }
        $allowedIds = User::where('role', \App\Enums\Role::MEMBER->value)->pluck('id')->all();
        if ($isUpdate && $task->assigned_to) {
            $allowedIds[] = $task->assigned_to;
        }
        return ['nullable', Rule::in($allowedIds)];
    }

    /** Quick status change from the dashboard or Kanban (single-field PATCH). */
    public function updateStatus(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $data = $request->validate([
            'status'      => ['required', new Enum(TaskStatus::class)],
            'hold_reason' => [
                $request->input('status') === TaskStatus::HOLD->value ? 'required' : 'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $oldStatus = $task->status;
        $newStatus = TaskStatus::from($data['status']);

        $updates = [
            'status'       => $data['status'],
            'completed_at' => $data['status'] === TaskStatus::DONE->value
                ? ($task->completed_at ?? now())
                : null,
            // Set the reason when going into Hold; clear it when leaving Hold.
            'hold_reason'  => $newStatus === TaskStatus::HOLD
                ? ($data['hold_reason'] ?? $task->hold_reason)
                : null,
        ];
        // Fold elapsed working time when entering/leaving WIP.
        $updates = array_merge($updates, $this->applyTimerTransition($task, $oldStatus, $newStatus));

        $task->update($updates);

        if ($request->wantsJson()) {
            return response()->json([
                'ok'     => true,
                'status' => $task->status->value,
                'label'  => $task->status->label(),
            ]);
        }

        return back()->with('status', 'Status updated.');
    }

    /**
     * AJAX endpoint used by the task form to preview an auto-suggested
     * timeline based on the assignee's current open-task queue.
     */
    public function suggestTimeline(Request $request): JsonResponse
    {
        $minutes  = max(0, (int) $request->query('minutes'));
        $assignee = (int) $request->query('assignee');

        $due = $this->suggestDueDate($assignee, $minutes);
        $queueMin = $this->openQueueMinutes($assignee);

        return response()->json([
            'date'        => $due->toDateString(),
            'date_label'  => $due->format('d M Y'),
            'queue_label' => Task::formatHours($queueMin / 60),
        ]);
    }

    private function validateTask(Request $request): array
    {
        // Combine hours+minutes inputs.
        foreach (['estimated_hours', 'actual_hours'] as $key) {
            $h = max(0, (int) $request->input("{$key}_h", 0));
            $m = max(0, min(59, (int) $request->input("{$key}_m", 0)));
            $request->merge([$key => ($h > 0 || $m > 0) ? round($h + $m / 60, 4) : null]);
        }

        // Activity required on CREATE, optional on UPDATE (so legacy tasks
        // without an activity can still be edited freely).
        $isUpdate = $request->route('task') !== null;
        $activityVal = (string) $request->input('activity_template_id');

        // Members can only assign to other members (+ keep existing assignee on edit).
        $assigneeRule = $this->assigneeRule(
            $request->route('task') ?? new Task(),
            isUpdate: $isUpdate,
        );
        if ($activityVal === 'other') {
            $rules = [
                'new_activity_name' => ['required', 'string', 'max:255', Rule::unique('activity_templates', 'name')],
                'estimated_hours'   => ['required', 'numeric', 'min:0.01', 'max:9999'],
            ];
        } else {
            $rules = ['activity_template_id' => [
                $isUpdate ? 'nullable' : 'required',
                Rule::exists('activity_templates', 'id'),
            ]];
        }

        return $request->validate([
            ...$rules,
            'category_id'      => ['required', Rule::exists('categories', 'id')],
            'title'            => ['required', 'string', 'max:255'],
            'website'          => ['nullable', 'string', 'max:500'],
            'description'      => ['nullable', 'string'],
            'assigned_to'      => $assigneeRule,
            'assigned_by_name' => ['nullable', 'string', 'max:255'],
            'assigned_at'      => ['nullable', 'date'],
            'priority'         => ['required', new Enum(Priority::class)],
            'status'           => ['required', new Enum(TaskStatus::class)],
            'start_date'       => ['nullable', 'date'],
            'due_date'         => ['nullable', 'date', 'after_or_equal:start_date'],
            'estimated_hours'  => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'actual_hours'     => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'delay_reason'     => ['nullable', 'string'],
            'hold_reason'      => [
                $request->input('status') === TaskStatus::HOLD->value ? 'required' : 'nullable',
                'string',
                'max:1000',
            ],
            'final_url'        => ['nullable', 'url', 'max:500'],
        ]);
    }

    /**
     * Convert the activity dropdown value into an actual template id.
     * If the user picked "Other", auto-create a template using the new name +
     * the form's estimated time so it's reusable next time.
     */
    private function resolveActivityTemplate(Request $request, array $data): ?int
    {
        $val = (string) $request->input('activity_template_id');

        if ($val === 'other' && $request->filled('new_activity_name')) {
            $minutes = max(1, (int) round(((float) ($data['estimated_hours'] ?? 0)) * 60));
            $template = ActivityTemplate::create([
                'name'              => trim($request->input('new_activity_name')),
                'estimated_minutes' => $minutes,
                'created_by'        => $request->user()->id,
            ]);

            return $template->id;
        }

        return is_numeric($val) ? (int) $val : null;
    }

    /** Bundled view data: prior websites + activity templates + dropdowns. */
    private function formData(Task $task): array
    {
        // Admins can assign to anyone; members only to other members.
        // On edit, always keep the task's current assignee in the list so the
        // existing selection stays visible even if it's an admin.
        $assignees = User::orderBy('name')
            ->when(! auth()->user()->isAdmin(), function ($q) use ($task) {
                $q->where(function ($inner) use ($task) {
                    $inner->where('role', \App\Enums\Role::MEMBER->value);
                    if ($task->assigned_to) {
                        $inner->orWhere('id', $task->assigned_to);
                    }
                });
            })
            ->get(['id', 'name', 'email', 'role']);

        return [
            'task'             => $task,
            'categories'       => Category::orderBy('name')->get(),
            'priorities'       => Priority::options(),
            'statuses'         => TaskStatus::options(),
            'assignableUsers'  => $assignees,
            'templates'        => ActivityTemplate::orderBy('name')->get(['id', 'name', 'estimated_minutes']),
            'priorWebsites'    => Task::query()
                ->whereNotNull('website')->where('website', '!=', '')
                ->distinct()->orderBy('website')->pluck('website'),
        ];
    }

    /**
     * Total open estimated minutes already on the assignee's plate.
     * Excludes Done AND Hold — Hold is paused work, not real queue load.
     */
    private function openQueueMinutes(int $assigneeId): int
    {
        if ($assigneeId <= 0) {
            return 0;
        }

        return (int) round(60 * (float) Task::query()
            ->where('assigned_to', $assigneeId)
            ->whereNotIn('status', [TaskStatus::DONE->value, TaskStatus::HOLD->value])
            ->sum('estimated_hours'));
    }

    /**
     * Walk the assignee's queue + new task across the 10am–6pm Mon–Fri
     * window and return the timestamp the new task would finish on.
     * (For the Timeline date field we use the date portion of this value.)
     */
    private function suggestDueDate(int $assigneeId, int $newMinutes): Carbon
    {
        $totalMin = $this->openQueueMinutes($assigneeId) + max(0, $newMinutes);

        return Task::addWorkingMinutes(Carbon::now(), $totalMin);
    }

    /** Convert form's combined estimated_hours (already merged) back to minutes. */
    private function minutesFromData(array $data): int
    {
        return (int) round(((float) ($data['estimated_hours'] ?? 0)) * 60);
    }

    /**
     * When status crosses the WIP boundary, manage the auto-timer.
     *  - Entering WIP  → record wip_started_at = now
     *  - Leaving  WIP  → fold elapsed *working* minutes into actual_hours
     *                    and null out wip_started_at
     * Returns the partial updates to merge into the row.
     */
    private function applyTimerTransition(Task $task, ?TaskStatus $old, TaskStatus $new): array
    {
        $updates = [];
        $wasWip = $old === TaskStatus::WIP;
        $nowWip = $new === TaskStatus::WIP;

        if ($nowWip && ! $wasWip) {
            $updates['wip_started_at'] = now();
        }

        if ($wasWip && ! $nowWip && $task->wip_started_at) {
            $elapsedMin = Task::workingMinutesBetween($task->wip_started_at, now());
            if ($elapsedMin > 0) {
                $current = (float) ($task->actual_hours ?? 0);
                $updates['actual_hours'] = round($current + ($elapsedMin / 60), 2);
            }
            $updates['wip_started_at'] = null;
        }

        return $updates;
    }
}
