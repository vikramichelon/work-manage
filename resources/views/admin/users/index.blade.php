@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h1 class="h4 mb-0"><i class="fa-solid fa-users-gear text-primary me-2"></i>Manage Users</h1>
                <span class="text-secondary small">{{ $users->total() }} user(s)</span>
            </div>

            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Current role</th>
                                <th class="text-end">Change role</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td class="fw-semibold">
                                        {{ $user->name }}
                                        @if ($user->is(auth()->user()))
                                            <span class="badge text-bg-light border ms-1">You</span>
                                        @endif
                                    </td>
                                    <td class="text-secondary">{{ $user->email }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $user->role->color() }}">{{ $user->role->label() }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-end align-items-center gap-2">
                                            <form method="POST" action="{{ route('admin.users.role', $user) }}"
                                                  class="d-flex gap-2 m-0">
                                                @csrf
                                                @method('PATCH')
                                                <select name="role" class="form-select form-select-sm w-auto">
                                                    @foreach ($roles as $value => $label)
                                                        <option value="{{ $value }}" @selected($user->role->value === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="btn btn-sm btn-primary" title="Save role">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </form>

                                            @if (! $user->is(auth()->user()))
                                                @php
                                                    $assigned = (int) $user->assigned_tasks_count;
                                                    $created  = (int) $user->created_tasks_count;
                                                    $cats     = (int) $user->created_categories_count;
                                                    $msg      = "Delete user '{$user->name}'?";
                                                    if ($created || $cats) $msg .= "\\n\\n• {$created} created task(s) and {$cats} category(s) will be re-attributed to YOU.";
                                                    if ($assigned)         $msg .= "\\n• {$assigned} assigned task(s) will become Unassigned.";
                                                    $msg .= "\\n\\nThis cannot be undone.";
                                                @endphp
                                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                                      class="d-inline m-0"
                                                      onsubmit="return confirm('{{ $msg }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete user">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-secondary py-4">No users yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
