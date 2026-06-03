@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <nav class="mb-3">
                <a href="{{ route('tasks.show', $task) }}" class="text-decoration-none text-secondary small">
                    <i class="fa-solid fa-arrow-left me-1"></i>Back to task
                </a>
            </nav>
            <h1 class="h4 mb-3"><i class="fa-solid fa-pen-to-square text-primary me-2"></i>Edit Task</h1>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('tasks.update', $task) }}" enctype="multipart/form-data">
                        @method('PUT')
                        @include('tasks._form', ['submitLabel' => 'Save changes'])
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
