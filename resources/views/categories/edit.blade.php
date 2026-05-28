@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="h4 mb-3"><i class="fa-solid fa-pen-to-square text-primary me-2"></i>Edit Category</h1>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('categories.update', $category) }}">
                        @method('PUT')
                        @include('categories._form', ['submitLabel' => 'Save changes'])
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
