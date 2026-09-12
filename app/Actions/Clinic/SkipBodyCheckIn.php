<?php

namespace App\Actions\Clinic;

use App\Models\Profile;
use App\Models\User;

class SkipBodyCheckIn
{
    public function handle(User $user, Profile $profile): void
    {
        $attributes = [
            'body_checkin_pending' => false,
        ];

        if (
            $user->can('update', $profile)
            && $profile->height_cm === null
            && $profile->height_prompt_skipped_at === null
        ) {
            $attributes['height_prompt_skipped_at'] = $profile->nowInTimezone()->utc();
        }

        $profile->update($attributes);
    }
}
