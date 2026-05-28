<?php

namespace App\Policies;

use App\Models\ActivityTemplate;
use App\Models\User;

class ActivityTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // everyone can see templates (used in task form)
    }

    public function view(User $user, ActivityTemplate $template): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->canManage();
    }

    public function update(User $user, ActivityTemplate $template): bool
    {
        return $user->canManage();
    }

    public function delete(User $user, ActivityTemplate $template): bool
    {
        return $user->canManage();
    }
}
