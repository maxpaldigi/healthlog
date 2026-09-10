<?php

namespace App\Policies;

use App\Models\Consent;
use App\Models\User;

class ConsentPolicy
{
    public function view(User $user, Consent $consent): bool
    {
        return $user->is($consent->user);
    }

    public function create(User $user): bool
    {
        return $user->exists;
    }
}
