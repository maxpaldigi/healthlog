<?php

namespace App\Policies;

use App\Models\Profile;
use App\Models\PushSubscription;
use App\Models\User;

class PushSubscriptionPolicy
{
    public function create(User $user, Profile $profile): bool
    {
        return $user->is($profile->user);
    }

    public function delete(User $user, PushSubscription $pushSubscription): bool
    {
        return $user->profiles()->whereKey($pushSubscription->profile_id)->exists();
    }
}
