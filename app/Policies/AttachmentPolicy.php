<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Attachment $attachment): bool
    {
        return true; // anyone who can see the task can see its files
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, Attachment $attachment): bool
    {
        return $user->isAdmin() || $attachment->user_id === $user->id;
    }
}
