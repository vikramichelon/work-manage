{{--
    Task detail panel — used by the dashboard's right column (AJAX-loaded)
    AND included by the full-page tasks/show view. Layout is a single column
    so it works in a narrow panel; cards stack vertically.

    Required: $task, $activities
    Optional: $inPanel (bool) — when true, renders a close button in the header.
--}}
@php $inPanel = $inPanel ?? false; @endphp

<div class="wm-task-panel" data-task-id="{{ $task->id }}">

    {{-- Header: inline-editable badges + title + actions --}}
    <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
        <div class="flex-grow-1 min-w-0">
            <div class="d-flex gap-1 mb-2 flex-wrap align-items-center">
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
                    <form method="POST" action="{{ route('tasks.status', $task) }}" class="d-inline wm-status-form">
                        @csrf
                        @method('PATCH')
                        <select name="status" class="wm-status-select wm-status-{{ $task->status->value }}"
                                data-original="{{ $task->status->value }}" title="Change status">
                            @foreach (App\Enums\TaskStatus::options() as $value => $label)
                                <option value="{{ $value }}" @selected($task->status->value === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                @else
                    <span class="badge text-bg-{{ $task->status->color() }}">{{ $task->status->label() }}</span>
                @endcan
                <span class="wm-cat-pick">
                    <span class="dot" style="background:{{ $task->category->color }}"></span>
                    @can('update', $task)
                        <select class="wm-inline" data-task-id="{{ $task->id }}" data-field="category_id" data-reload="1" title="Change category">
                            @foreach ($allCategories as $cat)
                                <option value="{{ $cat->id }}" @selected($task->category_id === $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <span>{{ $task->category->name }}</span>
                    @endcan
                </span>
                @if ($task->isOverdue())
                    <span class="badge text-bg-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>Overdue</span>
                @endif
            </div>
            @can('update', $task)
                <h2 class="h5 mb-0 wm-edit-text" data-task-id="{{ $task->id }}" data-field="title"
                    data-original="{{ $task->title }}" data-input="text" title="Click to edit">{{ $task->title }}</h2>
            @else
                <h2 class="h5 mb-0">{{ $task->title }}</h2>
            @endcan
        </div>
        <div class="d-flex gap-1 align-items-center flex-shrink-0">
            @can('update', $task)
                <a href="{{ route('tasks.edit', $task) }}" class="btn btn-outline-secondary btn-sm" title="Edit">
                    <i class="fa-solid fa-pen"></i>
                </a>
            @endcan
            @can('delete', $task)
                <form method="POST" action="{{ route('tasks.destroy', $task) }}"
                      onsubmit="return confirm('Delete this task?');" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </form>
            @endcan
            @if ($inPanel)
                <button type="button" class="btn btn-sm btn-link text-secondary p-1 wm-panel-close" title="Close">
                    <i class="fa-solid fa-xmark fs-5"></i>
                </button>
            @endif
        </div>
    </div>

    {{-- Description (click to edit) --}}
    <div class="card border-0 mb-3">
        <div class="card-body py-3">
            <h3 class="h6 text-secondary text-uppercase small mb-2">Description</h3>
            @can('update', $task)
                <p class="mb-0 small wm-edit-text" style="white-space: pre-wrap; word-break: break-word; min-height: 1.5em;"
                   data-task-id="{{ $task->id }}" data-field="description"
                   data-original="{{ $task->description }}" data-input="textarea"
                   data-placeholder="Click to add description…"
                   title="Click to edit">{{ $task->description ?: 'Click to add description…' }}</p>
            @else
                <p class="mb-0 small" style="white-space: pre-wrap; word-break: break-word;">{{ $task->description ?: 'No description provided.' }}</p>
            @endcan

            @if ($task->status === App\Enums\TaskStatus::HOLD)
                <div class="alert mt-3 mb-0 py-2 px-3" style="background:#F5F3FF;border:1px solid #DDD6FE;border-left:4px solid #7C3AED;color:#5B21B6;font-size:.8rem;">
                    <strong><i class="fa-solid fa-circle-pause me-1"></i>On hold:</strong>
                    @can('update', $task)
                        <span class="wm-edit-text" style="white-space: pre-wrap; word-break: break-word;"
                              data-task-id="{{ $task->id }}" data-field="hold_reason"
                              data-original="{{ $task->hold_reason }}" data-input="textarea" data-reload="1"
                              title="Click to edit reason">{{ $task->hold_reason ?: '(no reason — click to add)' }}</span>
                    @else
                        <span style="white-space: pre-wrap; word-break: break-word;">{{ $task->hold_reason }}</span>
                    @endcan
                </div>
            @endif

            <div class="mt-3 pt-3 border-top">
                <h3 class="h6 text-secondary text-uppercase small mb-2">
                    <i class="fa-solid fa-note-sticky text-warning me-1"></i>Notes
                </h3>
                @can('update', $task)
                    <p class="mb-0 small wm-edit-text" style="white-space: pre-wrap; word-break: break-word; min-height: 1.5em;"
                       data-task-id="{{ $task->id }}" data-field="delay_reason"
                       data-original="{{ $task->delay_reason }}" data-input="textarea"
                       data-placeholder="Click to add notes…"
                       title="Click to edit">{{ $task->delay_reason ?: 'Click to add notes…' }}</p>
                @else
                    <p class="mb-0 small" style="white-space: pre-wrap; word-break: break-word;">{{ $task->delay_reason ?: '—' }}</p>
                @endcan
            </div>
        </div>
    </div>

    {{-- Details (all fields click-to-edit when user has permission) --}}
    @php $canEdit = auth()->user()->can('update', $task); @endphp
    <div class="card border-0 mb-3">
        <div class="card-body py-3">
            <h3 class="h6 text-secondary text-uppercase small mb-2">Details</h3>
            <dl class="row small mb-0">

                <dt class="col-5 text-secondary fw-normal">Website</dt>
                <dd class="col-7">
                    @if ($canEdit)
                        <span class="wm-edit-text d-inline-block text-truncate" style="max-width:100%"
                              data-task-id="{{ $task->id }}" data-field="website"
                              data-original="{{ $task->website }}" data-input="text" data-reload="1"
                              data-placeholder="— add website —"
                              title="Click to edit">{{ $task->website ?: '— add website —' }}</span>
                    @else
                        {{ $task->website ?? '—' }}
                    @endif
                </dd>

                @if ($task->status->showsFinalUrl())
                    <dt class="col-5 text-secondary fw-normal">Final URL</dt>
                    <dd class="col-7">
                        @if ($canEdit)
                            <span class="wm-edit-text d-inline-block text-truncate" style="max-width:100%"
                                  data-task-id="{{ $task->id }}" data-field="final_url"
                                  data-original="{{ $task->final_url }}" data-input="url" data-reload="1"
                                  data-placeholder="— add final URL —"
                                  title="Click to edit">{{ $task->final_url ?: '— add final URL —' }}</span>
                        @else
                            {{ $task->final_url ?? '—' }}
                        @endif
                        @if ($task->final_url)
                            <a href="{{ $task->final_url }}" target="_blank" class="ms-1 text-primary" title="Open">
                                <i class="fa-solid fa-arrow-up-right-from-square small"></i>
                            </a>
                        @endif
                    </dd>
                @endif

                <dt class="col-5 text-secondary fw-normal">Assigned by</dt>
                <dd class="col-7">
                    @if ($canEdit)
                        <span class="wm-edit-text" data-task-id="{{ $task->id }}" data-field="assigned_by_name"
                              data-original="{{ $task->assigned_by_name }}" data-input="text"
                              data-placeholder="—" title="Click to edit">{{ $task->assigned_by_name ?: '—' }}</span>
                    @else
                        {{ $task->assigned_by_name ?? '—' }}
                    @endif
                </dd>

                <dt class="col-5 text-secondary fw-normal">Assignee</dt>
                <dd class="col-7">
                    @if ($canEdit)
                        <select class="wm-bare-select wm-inline" data-task-id="{{ $task->id }}" data-field="assigned_to" data-reload="1">
                            <option value="">— Unassigned —</option>
                            @foreach ($assignableUsers as $u)
                                <option value="{{ $u->id }}" @selected($task->assigned_to === $u->id)>{{ $u->name }}</option>
                            @endforeach
                            @if ($task->assignee && ! $assignableUsers->contains('id', $task->assignee->id))
                                <option value="{{ $task->assignee->id }}" selected>{{ $task->assignee->name }} (current)</option>
                            @endif
                        </select>
                    @else
                        {{ $task->assignee?->name ?? 'Unassigned' }}
                    @endif
                </dd>

                <dt class="col-5 text-secondary fw-normal">Timeline</dt>
                <dd class="col-7">
                    @if ($canEdit)
                        <input type="date" class="wm-inline-date wm-inline"
                               data-task-id="{{ $task->id }}" data-field="due_date" data-reload="1"
                               value="{{ $task->due_date?->format('Y-m-d') }}">
                    @else
                        {{ $task->due_date?->format('d M Y') ?? '—' }}
                    @endif
                </dd>

                <dt class="col-5 text-secondary fw-normal">Assigned on</dt>
                <dd class="col-7 text-secondary">{{ $task->assigned_at?->format('d M Y') ?? '—' }}</dd>

                <dt class="col-5 text-secondary fw-normal">Est. time</dt>
                <dd class="col-7">
                    @if ($canEdit)
                        <span class="wm-edit-text" data-task-id="{{ $task->id }}" data-field="estimated_hours"
                              data-original="{{ $task->estimated_hours }}" data-input="hours"
                              data-display="{{ $task->estimatedDisplay() }}"
                              title="Click to edit (hours)">{{ $task->estimatedDisplay() }}</span>
                    @else
                        {{ $task->estimatedDisplay() }}
                    @endif
                </dd>

                <dt class="col-5 text-secondary fw-normal">Actual time</dt>
                <dd class="col-7">
                    @if ($canEdit)
                        <span class="wm-edit-text" data-task-id="{{ $task->id }}" data-field="actual_hours"
                              data-original="{{ $task->actual_hours }}" data-input="hours"
                              data-display="{{ $task->actualDisplay() }}"
                              title="Click to edit (hours)">{{ $task->actualDisplay() }}</span>
                    @else
                        {{ $task->actualDisplay() }}
                    @endif
                </dd>

                <dt class="col-5 text-secondary fw-normal">Created by</dt>
                <dd class="col-7 text-secondary">{{ $task->creator->name }}</dd>
            </dl>
        </div>
    </div>

    {{-- Attachments --}}
    <div class="card border-0 mb-3">
        <div class="card-body py-3">
            <h3 class="h6 text-secondary text-uppercase small mb-2">
                <i class="fa-solid fa-paperclip me-1"></i>Attachments ({{ $task->attachments->count() }})
            </h3>

            @forelse ($task->attachments as $att)
                <div class="d-flex align-items-center gap-2 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                    @if ($att->isImage())
                        <a href="{{ $att->publicUrl() }}" target="_blank">
                            <img src="{{ $att->publicUrl() }}" alt="{{ $att->original_name }}"
                                 style="width:32px;height:32px;object-fit:cover;border-radius:.375rem;border:1px solid var(--wm-border)">
                        </a>
                    @else
                        <span class="d-inline-flex align-items-center justify-content-center rounded text-secondary"
                              style="width:32px;height:32px;border:1px solid var(--wm-border);background:var(--wm-surface)">
                            <i class="fa-regular fa-file-lines"></i>
                        </span>
                    @endif
                    <div class="flex-grow-1 min-w-0">
                        <a href="{{ route('attachments.download', $att) }}" target="_blank"
                           class="text-decoration-none text-reset fw-semibold text-truncate d-block small">
                            {{ $att->original_name }}
                        </a>
                        <div class="text-secondary" style="font-size:.7rem;">
                            {{ $att->humanSize() }} ·
                            {{ $att->user?->name ?? 'Someone' }} ·
                            {{ $att->created_at->diffForHumans() }}
                        </div>
                    </div>
                    <a href="{{ route('attachments.download', $att) }}" class="btn btn-sm btn-link text-secondary p-1" title="Download">
                        <i class="fa-solid fa-download"></i>
                    </a>
                    @can('delete', $att)
                        <form method="POST" action="{{ route('attachments.destroy', $att) }}"
                              onsubmit="return confirm('Delete this file?');" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-link text-danger p-1" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    @endcan
                </div>
            @empty
                <p class="text-secondary small mb-2">No files attached yet.</p>
            @endforelse

            <form method="POST" action="{{ route('attachments.store', $task) }}" enctype="multipart/form-data"
                  class="mt-2 border-top pt-2">
                @csrf
                <div class="input-group input-group-sm">
                    <input type="file" name="file" class="form-control form-control-sm @error('file') is-invalid @enderror" required>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-upload"></i>
                    </button>
                </div>
                <div class="form-text" style="font-size:.65rem;">Max 10 MB. images, PDF, DOC/X, XLS/X, CSV, TXT, ZIP.</div>
                @error('file') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </form>
        </div>
    </div>

    {{-- Comments --}}
    <div class="card border-0 mb-3">
        <div class="card-body py-3">
            <h3 class="h6 text-secondary text-uppercase small mb-2">
                <i class="fa-regular fa-comments me-1"></i>Comments ({{ $task->comments->count() }})
            </h3>

            @forelse ($task->comments as $c)
                <div class="d-flex gap-2 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-semibold small">{{ $c->user?->name ?? 'Someone' }}</span>
                            <span class="text-secondary" style="font-size:.65rem">{{ $c->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="small" style="white-space: pre-wrap;">{{ $c->body }}</div>
                    </div>
                    @can('delete', $c)
                        <form method="POST" action="{{ route('comments.destroy', $c) }}"
                              onsubmit="return confirm('Delete this comment?');" class="ms-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Delete">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </form>
                    @endcan
                </div>
            @empty
                <p class="text-secondary small mb-2">No comments yet.</p>
            @endforelse

            <form method="POST" action="{{ route('comments.store', $task) }}" class="mt-2 border-top pt-2">
                @csrf
                <textarea name="body" rows="2" required maxlength="5000"
                          class="form-control form-control-sm mb-2 @error('body') is-invalid @enderror"
                          placeholder="Write a comment…">{{ old('body') }}</textarea>
                @error('body') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fa-regular fa-paper-plane me-1"></i>Add comment
                </button>
            </form>
        </div>
    </div>

    {{-- Activity feed --}}
    <div class="card border-0">
        <div class="card-body py-3">
            <h3 class="h6 text-secondary text-uppercase small mb-2">
                <i class="fa-solid fa-clock-rotate-left me-1"></i>Activity
            </h3>
            @forelse ($activities as $activity)
                <div class="d-flex gap-2 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                    <div class="text-secondary" style="min-width: 6rem; font-size:.7rem;">
                        {{ $activity->created_at->diffForHumans() }}
                    </div>
                    <div class="small flex-grow-1">
                        {{ $activity->description() }}
                    </div>
                </div>
            @empty
                <p class="text-secondary small mb-0">No activity yet.</p>
            @endforelse
        </div>
    </div>
</div>
