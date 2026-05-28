<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Category $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canManage();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->canManage();
    }

    /** Admins delete any category; Managers only ones they created. */
    public function delete(User $user, Category $category): bool
    {
        return $user->isAdmin()
            || ($user->isManager() && $category->created_by === $user->id);
    }
}
