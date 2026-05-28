@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav class="mb-3">
                <a href="{{ route('templates.index') }}" class="text-decoration-none text-secondary small">
                    <i class="fa-solid fa-arrow-left me-1"></i>Activity Templates
                </a>
            </nav>
            <h1 class="h4 mb-3"><i class="fa-solid fa-plus text-primary me-2"></i>New Template</h1>
            <div class="card border-0">
                <div class="card-body">
                    <form method="POST" action="{{ route('templates.store') }}">
                        @include('activity_templates._form', ['submitLabel' => 'Create template'])
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
