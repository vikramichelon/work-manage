<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class UserController extends Controller
{
    /** List every user with their role. */
    public function index(): View
    {
        // Pre-load counts so the delete confirmation can show what'll be
        // re-attributed (created tasks/categories) and what'll auto-unassign.
        $users = User::orderBy('name')
            ->withCount(['assignedTasks', 'createdCategories'])
            ->paginate(15);

        // Cheap per-row count for created tasks (no dedicated relationship).
        foreach ($users as $u) {
            $u->created_tasks_count = Task::where('created_by', $u->id)->count();
        }

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::options(),
        ]);
    }

    /** Change a single user's role. */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', new Enum(Role::class)],
        ]);

        // Guard: an admin must not strip their own admin rights and lock
        // themselves (and possibly everyone) out.
        if ($user->is($request->user()) && $validated['role'] !== Role::ADMIN->value) {
            return back()->with('error', 'You cannot change your own admin role.');
        }

        $user->update(['role' => $validated['role']]);

        return back()->with('status', "{$user->name}'s role updated to ".$user->role->label().'.');
    }

    /**
     * Soft-delete a user. Row stays in DB with deleted_at timestamp; user
     * disappears from lists and can no longer log in (Laravel auth respects
     * SoftDeletes). All foreign-key references (tasks, comments, categories)
     * stay intact — restoring the user would bring everything back.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Guard 1: can't delete yourself.
        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        // Guard 2: don't allow deleting the last admin — would lock everyone out.
        if ($user->isAdmin() && User::where('role', Role::ADMIN->value)->count() <= 1) {
            return back()->with('error', 'Cannot delete the last admin — at least one admin must remain.');
        }

        $name = $user->name;
        $user->delete(); // soft delete — sets deleted_at, row stays in DB

        return back()->with('status', "User '{$name}' deleted (their data and history are preserved).");
    }
}
