<?php

namespace App\Policies;

use App\Models\Profile;
use App\Models\PushSubscription;
use App\Models\User;

class PushSubscriptionPolicy
{
    public function create(User $user, Profile $profile): bool
    {
        return $user->canAccessProfile($profile);
    }

    public function delete(User $user, PushSubscription $pushSubscription): bool
    {
        if ($pushSubscription->user_id !== null) {
            return $user->id === (int) $pushSubscription->user_id;
        }

        return $user->canAccessProfile($pushSubscription->profile_id);
    }
}
