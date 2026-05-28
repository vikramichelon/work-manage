@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0"><i class="fa-solid fa-clipboard-list text-primary me-2"></i>Activity Templates</h1>
        @can('create', App\Models\ActivityTemplate::class)
            <a href="{{ route('templates.create') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-plus me-1"></i>New Template
            </a>
        @endcan
    </div>

    <p class="text-secondary small">
        Common work types with typical time. Task form mein "Task headline" type karte hi yeh suggest hote hain
        aur **Estimated time auto-fill** ho jata hai.
    </p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Template name</th>
                            <th>Typical time</th>
                            <th>Default team</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($templates as $t)
                            <tr>
                                <td class="fw-semibold">{{ $t->name }}</td>
                                <td>{{ $t->timeLabel() }}</td>
                                <td>
                                    @if ($t->category)
                                        <span class="badge text-bg-light border">
                                            <span class="d-inline-block rounded-circle me-1" style="width:.4rem;height:.4rem;background:{{ $t->category->color }};vertical-align:middle"></span>
                                            {{ $t->category->name }}
                                        </span>
                                    @else
                                        <span class="text-secondary small">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @can('update', $t)
                                        <a href="{{ route('templates.edit', $t) }}" class="btn btn-sm btn-outline-secondary" title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $t)
                                        <form method="POST" action="{{ route('templates.destroy', $t) }}" class="d-inline"
                                              onsubmit="return confirm('Delete this template?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-secondary small">No templates yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
