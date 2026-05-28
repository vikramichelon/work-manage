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
     * Delete a user. Re-attributes their created tasks/categories to the
     * acting admin first (otherwise the FK cascade would wipe that data).
     * Assigned tasks auto-unassign; comments/attachments/activities anonymise.
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

        $newOwnerId = $request->user()->id;
        $name = $user->name;

        DB::transaction(function () use ($user, $newOwnerId) {
            // Re-attribute the cascade-on-delete columns so their data survives.
            Task::where('created_by', $user->id)->update(['created_by' => $newOwnerId]);
            Category::where('created_by', $user->id)->update(['created_by' => $newOwnerId]);
            // Everything else (assigned_to, comments.user_id, attachments.user_id,
            // activities.user_id, activity_templates.created_by) auto-NULLs via FK.
            $user->delete();
        });

        return back()->with('status', "User '{$name}' deleted. Their tasks and categories were re-attributed to you.");
    }
}
