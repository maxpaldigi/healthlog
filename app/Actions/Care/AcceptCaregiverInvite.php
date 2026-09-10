<?php

namespace App\Actions\Care;

use App\Enums\ProfileRole;
use App\Models\ProfileMember;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AcceptCaregiverInvite
{
    public function handle(ProfileMember $member, User $user): ProfileMember
    {
        if ($member->isAccepted()) {
            throw ValidationException::withMessages([
                'invite' => 'This invite was already accepted.',
            ]);
        }

        if (mb_strtolower($user->email) !== mb_strtolower($member->email)) {
            throw ValidationException::withMessages([
                'invite' => 'Log in with '.$member->email.' to accept this invite.',
            ]);
        }

        $member->forceFill([
            'user_id' => $user->id,
            'role' => ProfileRole::Caregiver,
            'accepted_at' => now(),
        ])->save();

        return $member->refresh();
    }
}
