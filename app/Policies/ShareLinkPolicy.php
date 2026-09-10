<?php

namespace App\Policies;

use App\Models\Profile;
use App\Models\ShareLink;
use App\Models\User;

class ShareLinkPolicy
{
    public function create(User $user, Profile $profile): bool
    {
        return $user->is($profile->user);
    }

    public function view(?User $user, ShareLink $shareLink): bool
    {
        return ! $shareLink->isExpired();
    }
}
