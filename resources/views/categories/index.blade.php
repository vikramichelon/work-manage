@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0"><i class="fa-solid fa-tags text-primary me-2"></i>Categories</h1>
        @can('create', App\Models\Category::class)
            <a href="{{ route('categories.create') }}" class="btn btn-primary">
                <i class="fa-solid fa-plus me-1"></i> New Category
            </a>
        @endcan
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-3">
        @forelse ($categories as $category)
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="rounded-circle d-inline-block"
                                  style="width:.75rem;height:.75rem;background:{{ $category->color }}"></span>
                            <h2 class="h6 fw-semibold mb-0">
                                <a href="{{ route('categories.show', $category) }}" class="text-decoration-none stretched-link text-reset">
                                    {{ $category->name }}
                                </a>
                            </h2>
                            <span class="ms-auto text-secondary small">
                                <i class="fa-solid fa-list-check me-1"></i>{{ $category->tasks_count }}
                            </span>
                        </div>
                        @if ($category->description)
                            <p class="text-secondary small mb-3">{{ $category->description }}</p>
                        @endif
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge text-bg-secondary">To Do {{ $category->todo_count }}</span>
                            <span class="badge text-bg-info">WIP {{ $category->wip_count }}</span>
                            <span class="badge text-bg-warning">Draft {{ $category->draft_count ?? 0 }}</span>
                            <span class="badge text-bg-success">Done {{ $category->done_count }}</span>
                            @if (($category->hold_count ?? 0) > 0)
                                <span class="badge" style="background:#7C3AED;color:#fff">Hold {{ $category->hold_count }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center text-secondary py-5">
                        <i class="fa-solid fa-tags fs-1 mb-3 d-block"></i>
                        Abhi koi category nahi hai (e.g. <strong>Ads</strong>, <strong>SEO</strong>).
                        @can('create', App\Models\Category::class)
                            <div class="mt-3">
                                <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm">Create your first category</a>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
