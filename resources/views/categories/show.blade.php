@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <nav class="mb-3">
                <a href="{{ route('categories.index') }}" class="text-decoration-none text-secondary small">
                    <i class="fa-solid fa-arrow-left me-1"></i>Back to categories
                </a>
            </nav>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="rounded d-inline-block" style="width:2.25rem;height:2.25rem;background:{{ $category->color }}"></span>
                    <div>
                        <h1 class="h3 mb-0">{{ $category->name }}</h1>
                        @if ($category->description)
                            <div class="text-secondary small">{{ $category->description }}</div>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2">
                    @can('create', App\Models\Task::class)
                        <a href="{{ route('tasks.create', ['category' => $category->id]) }}" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-plus me-1"></i>New Task
                        </a>
                    @endcan
                    @can('update', $category)
                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa-solid fa-pen me-1"></i>Edit
                        </a>
                    @endcan
                    @can('delete', $category)
                        <form method="POST" action="{{ route('categories.destroy', $category) }}"
                              onsubmit="return confirm('Delete this category and all its tasks?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                <i class="fa-solid fa-trash me-1"></i>Delete
                            </button>
                        </form>
                    @endcan
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-secondary text-uppercase small mb-3">
                        Tasks ({{ $category->tasks->count() }})
                    </h2>

                    @forelse ($category->tasks as $task)
                        <div class="d-flex align-items-center gap-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <span class="badge text-bg-{{ $task->priority->color() }}" style="width:4.5rem;">
                                {{ $task->priority->label() }}
                            </span>
                            <div class="flex-grow-1 min-w-0">
                                <a href="{{ route('tasks.show', $task) }}" class="text-decoration-none text-reset fw-semibold">
                                    {{ $task->title }}
                                </a>
                                <div class="text-secondary small">
                                    <i class="fa-solid fa-user me-1"></i>{{ $task->assignee?->name ?? 'Unassigned' }}
                                    @if ($task->due_date)
                                        <span class="ms-2 {{ $task->isOverdue() ? 'text-danger' : '' }}">
                                            <i class="fa-regular fa-calendar me-1"></i>{{ $task->due_date->format('d M Y') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <span class="badge text-bg-{{ $task->status->color() }}">{{ $task->status->label() }}</span>
                        </div>
                    @empty
                        <p class="text-secondary small mb-0">No tasks yet.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
