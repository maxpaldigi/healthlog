<?php

namespace App\Policies;

use App\Models\ProfileMember;
use App\Models\User;

class ProfileMemberPolicy
{
    public function delete(User $user, ProfileMember $profileMember): bool
    {
        return $user->can('revokeMember', [$profileMember->profile, $profileMember]);
    }
}
