@extends('layouts.app')

@section('content')
<div class="container">
    {{-- Hero --}}
    <section class="text-center py-5">
        <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis mb-3 px-3 py-2">
            <i class="fa-solid fa-bolt me-1"></i> Task &amp; Project Tracking
        </span>
        <h1 class="display-4 fw-bold mx-auto" style="max-width: 44rem;">
            Manage your team&rsquo;s work,
            <span class="text-primary">all in one place.</span>
        </h1>
        <p class="lead text-secondary mx-auto mt-3" style="max-width: 36rem;">
            Plan projects, assign tasks, track time, and watch progress on a
            drag-and-drop board &mdash; built for Admins, Managers, and team members.
        </p>
        <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
            @auth
                <a href="{{ route('home') }}" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-gauge-high me-2"></i> Go to dashboard
                </a>
            @else
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-user-plus me-2"></i> Create an account
                </a>
                <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg">
                    <i class="fa-solid fa-right-to-bracket me-2"></i> Log in
                </a>
            @endauth
        </div>
    </section>

    {{-- Feature grid --}}
    <section class="row g-3 pb-5">
        @php
            $features = [
                ['fa-tags', 'Categories', 'Tag tasks by side (Ads, SEO, etc.).'],
                ['fa-list-check', 'Tasks', 'Assign tasks with priorities & due dates.'],
                ['fa-table-columns', 'Kanban', 'Drag tasks across Todo to Done.'],
                ['fa-stopwatch', 'Time Tracking', 'Start, pause & log working hours.'],
                ['fa-comments', 'Collaboration', 'Comments, mentions & file uploads.'],
                ['fa-chart-line', 'Dashboards', 'Per-category task status overview.'],
            ];
        @endphp
        @foreach ($features as [$icon, $title, $desc])
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="text-primary fs-4 mb-2"><i class="fa-solid {{ $icon }}"></i></div>
                        <h2 class="h6 fw-semibold mb-1">{{ $title }}</h2>
                        <p class="text-secondary small mb-0">{{ $desc }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </section>
</div>
@endsection
