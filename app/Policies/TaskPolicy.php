<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Controller scopes the list per role.
    }

    /** Admin sees any task; otherwise only ones the user created or is assigned. */
    public function view(User $user, Task $task): bool
    {
        return $user->isAdmin()
            || $task->created_by === $user->id
            || $task->assigned_to === $user->id;
    }

    /** Any authenticated user can create their own tasks. */
    public function create(User $user): bool
    {
        return true;
    }

    /** Admin can edit anything; others edit tasks they own or are assigned to. */
    public function update(User $user, Task $task): bool
    {
        return $user->isAdmin()
            || $task->created_by === $user->id
            || $task->assigned_to === $user->id;
    }

    /** Only Admin or the task's creator can delete. */
    public function delete(User $user, Task $task): bool
    {
        return $user->isAdmin() || $task->created_by === $user->id;
    }
}
