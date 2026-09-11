<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->isSuperAdmin() || $actor->is($user);
    }
}
