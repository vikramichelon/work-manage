@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h1 class="h4 mb-0">
                    <i class="fa-solid fa-list-ul text-primary me-2"></i>Activity Log
                </h1>
                <span class="text-secondary small">{{ $activities->total() }} events</span>
            </div>

            <div class="card border-0">
                <div class="card-body">
                    @forelse ($activities as $activity)
                        <div class="d-flex gap-3 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div class="text-secondary small" style="min-width: 9rem;">
                                {{ $activity->created_at->format('d M, H:i') }}
                            </div>
                            <div class="small flex-grow-1">
                                {{ $activity->description() }}
                                @if ($activity->task)
                                    <a href="{{ route('tasks.show', $activity->task) }}" class="text-decoration-none ms-1">
                                        <span class="badge text-bg-light border">
                                            <span class="d-inline-block rounded-circle me-1" style="width:.4rem;height:.4rem;background:{{ $activity->task->category?->color ?? '#71717A' }};vertical-align:middle"></span>
                                            {{ $activity->task->title }}
                                        </span>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-secondary small mb-0">No activity yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="mt-3">{{ $activities->links() }}</div>
        </div>
    </div>
</div>
@endsection
