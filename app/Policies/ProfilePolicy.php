<?php

namespace App\Policies;

use App\Enums\ProfileRole;
use App\Models\Profile;
use App\Models\ProfileMember;
use App\Models\User;

class ProfilePolicy
{
    public function create(User $user): bool
    {
        return $user->exists;
    }

    public function view(User $user, Profile $profile): bool
    {
        return $user->canAccessProfile($profile);
    }

    public function update(User $user, Profile $profile): bool
    {
        return $user->isOwnerOf($profile);
    }

    public function delete(User $user, Profile $profile): bool
    {
        return $user->isOwnerOf($profile);
    }

    public function invite(User $user, Profile $profile): bool
    {
        return $user->canAccessProfile($profile);
    }

    public function revokeMember(User $user, Profile $profile, ProfileMember $member): bool
    {
        if ($member->profile_id !== $profile->id) {
            return false;
        }

        if ($member->role === ProfileRole::Owner) {
            return false;
        }

        return $user->isOwnerOf($profile);
    }
}
