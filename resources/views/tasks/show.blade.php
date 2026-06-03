@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <nav class="mb-3">
                <a href="{{ route('home', ['category' => $task->category_id]) }}" class="text-decoration-none text-secondary small">
                    <i class="fa-solid fa-arrow-left me-1"></i>Dashboard ({{ $task->category->name }})
                </a>
            </nav>

            <div class="d-flex align-items-start justify-content-between mb-3">
                <div>
                    <div class="d-flex gap-2 mb-2 flex-wrap">
                        <span class="badge text-bg-{{ $task->priority->color() }}">{{ $task->priority->label() }} priority</span>
                        <span class="badge text-bg-{{ $task->status->color() }}">{{ $task->status->label() }}</span>
                        <span class="badge text-bg-light border">
                            <span class="d-inline-block rounded-circle me-1" style="width:.5rem;height:.5rem;background:{{ $task->category->color }};vertical-align:middle"></span>
                            {{ $task->category->name }}
                        </span>
                        @if ($task->isOverdue())
                            <span class="badge text-bg-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>Overdue</span>
                        @endif
                    </div>
                    <h1 class="h3 mb-0">{{ $task->title }}</h1>
                </div>
                <div class="d-flex gap-2">
                    @can('update', $task)
                        <a href="{{ route('tasks.edit', $task) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa-solid fa-pen me-1"></i>Edit
                        </a>
                    @endcan
                    @can('delete', $task)
                        <form method="POST" action="{{ route('tasks.destroy', $task) }}"
                              onsubmit="return confirm('Delete this task?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="fa-solid fa-trash me-1"></i>Delete
                            </button>
                        </form>
                    @endcan
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-8">
                    <div class="card border-0 h-100">
                        <div class="card-body">
                            <h2 class="h6 text-secondary text-uppercase small mb-2">Description</h2>
                            <p class="mb-3" style="white-space: pre-wrap; word-break: break-word;">{{ $task->description ?: 'No description provided.' }}</p>

                            @if ($task->status === App\Enums\TaskStatus::HOLD && $task->hold_reason)
                                <div class="alert mb-3 mt-3" style="background:#F5F3FF;border:1px solid #DDD6FE;border-left:4px solid #7C3AED;color:#5B21B6;">
                                    <strong><i class="fa-solid fa-circle-pause me-1"></i>On hold:</strong>
                                    <span style="white-space: pre-wrap; word-break: break-word;">{{ $task->hold_reason }}</span>
                                </div>
                            @endif

                            @if ($task->delay_reason)
                                <h2 class="h6 text-secondary text-uppercase small mb-2 mt-4">
                                    <i class="fa-solid fa-note-sticky text-warning me-1"></i>
                                    Notes
                                </h2>
                                <p class="mb-0" style="white-space: pre-wrap; word-break: break-word;">{{ $task->delay_reason }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 h-100">
                        <div class="card-body">
                            <h2 class="h6 text-secondary text-uppercase small mb-3">Details</h2>
                            <dl class="row small mb-0">
                                <dt class="col-5 text-secondary fw-normal">Team</dt>
                                <dd class="col-7">{{ $task->category->name }}</dd>
                                @if ($task->website)
                                    <dt class="col-5 text-secondary fw-normal">Website</dt>
                                    <dd class="col-7"><a href="{{ $task->website }}" target="_blank" class="text-decoration-none text-truncate d-inline-block" style="max-width:100%">{{ $task->website }}</a></dd>
                                @endif
                                <dt class="col-5 text-secondary fw-normal">Assign person</dt>
                                <dd class="col-7">{{ $task->assigned_by_name ?? '—' }}</dd>
                                <dt class="col-5 text-secondary fw-normal">Assignee</dt>
                                <dd class="col-7">{{ $task->assignee?->name ?? 'Unassigned' }}</dd>
                                <dt class="col-5 text-secondary fw-normal">Assigned on</dt>
                                <dd class="col-7">{{ $task->assigned_at?->format('d M Y') ?? '—' }}</dd>
                                <dt class="col-5 text-secondary fw-normal">Timeline</dt>
                                <dd class="col-7">{{ $task->due_date?->format('d M Y') ?? '—' }}</dd>
                                <dt class="col-5 text-secondary fw-normal">Est. time</dt>
                                <dd class="col-7">{{ $task->estimatedDisplay() }}</dd>
                                <dt class="col-5 text-secondary fw-normal">Actual time</dt>
                                <dd class="col-7">{{ $task->actualDisplay() }}</dd>
                                <dt class="col-5 text-secondary fw-normal">Created by</dt>
                                <dd class="col-7">{{ $task->creator->name }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Attachments --}}
            <div class="card border-0 mt-3">
                <div class="card-body">
                    <h2 class="h6 text-secondary text-uppercase small mb-3">
                        <i class="fa-solid fa-paperclip me-1"></i>Attachments ({{ $task->attachments->count() }})
                    </h2>

                    @forelse ($task->attachments as $att)
                        <div class="d-flex align-items-center gap-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            @if ($att->isImage())
                                <a href="{{ $att->publicUrl() }}" target="_blank">
                                    <img src="{{ $att->publicUrl() }}" alt="{{ $att->original_name }}"
                                         style="width:40px;height:40px;object-fit:cover;border-radius:.375rem;border:1px solid var(--wm-border)">
                                </a>
                            @else
                                <span class="d-inline-flex align-items-center justify-content-center rounded text-secondary"
                                      style="width:40px;height:40px;border:1px solid var(--wm-border);background:var(--wm-surface)">
                                    <i class="fa-regular fa-file-lines"></i>
                                </span>
                            @endif
                            <div class="flex-grow-1 min-w-0">
                                <a href="{{ route('attachments.download', $att) }}" target="_blank"
                                   class="text-decoration-none text-reset fw-semibold text-truncate d-block">
                                    {{ $att->original_name }}
                                </a>
                                <div class="text-secondary small">
                                    {{ $att->humanSize() }} ·
                                    {{ $att->user?->name ?? 'Someone' }} ·
                                    {{ $att->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <a href="{{ route('attachments.download', $att) }}" class="btn btn-sm btn-outline-secondary" title="Download">
                                <i class="fa-solid fa-download"></i>
                            </a>
                            @can('delete', $att)
                                <form method="POST" action="{{ route('attachments.destroy', $att) }}"
                                      onsubmit="return confirm('Delete this file?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    @empty
                        <p class="text-secondary small mb-3">No files attached yet.</p>
                    @endforelse

                    {{-- Upload form --}}
                    <form method="POST" action="{{ route('attachments.store', $task) }}" enctype="multipart/form-data"
                          class="mt-3 border-top pt-3">
                        @csrf
                        <div class="input-group input-group-sm">
                            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" required>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-upload me-1"></i>Upload
                            </button>
                        </div>
                        <div class="form-text">Max 10 MB. Allowed: images, PDF, DOC/X, XLS/X, CSV, TXT, ZIP.</div>
                        @error('file') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </form>
                </div>
            </div>

            {{-- Comments --}}
            <div class="card border-0 mt-3">
                <div class="card-body">
                    <h2 class="h6 text-secondary text-uppercase small mb-3">
                        <i class="fa-regular fa-comments me-1"></i>Comments ({{ $task->comments->count() }})
                    </h2>

                    @forelse ($task->comments as $c)
                        <div class="d-flex gap-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="fw-semibold small">{{ $c->user?->name ?? 'Someone' }}</span>
                                    <span class="text-secondary" style="font-size:.7rem">{{ $c->created_at->diffForHumans() }}</span>
                                </div>
                                <div class="small" style="white-space: pre-wrap;">{{ $c->body }}</div>
                            </div>
                            @can('delete', $c)
                                <form method="POST" action="{{ route('comments.destroy', $c) }}"
                                      onsubmit="return confirm('Delete this comment?');" class="ms-2">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Delete">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </form>
                            @endcan
                        </div>
                    @empty
                        <p class="text-secondary small mb-3">No comments yet.</p>
                    @endforelse

                    <form method="POST" action="{{ route('comments.store', $task) }}" class="mt-3 border-top pt-3">
                        @csrf
                        <textarea name="body" rows="2" required maxlength="5000"
                                  class="form-control form-control-sm mb-2 @error('body') is-invalid @enderror"
                                  placeholder="Write a comment…">{{ old('body') }}</textarea>
                        @error('body') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-regular fa-paper-plane me-1"></i>Add comment
                        </button>
                    </form>
                </div>
            </div>

            {{-- Activity feed --}}
            <div class="card border-0 mt-3">
                <div class="card-body">
                    <h2 class="h6 text-secondary text-uppercase small mb-3">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i>Activity
                    </h2>
                    @forelse ($activities as $activity)
                        <div class="d-flex gap-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div class="text-secondary small" style="min-width: 9rem;">
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
    </div>
</div>
@endsection
