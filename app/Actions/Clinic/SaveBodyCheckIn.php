<?php

namespace App\Actions\Clinic;

use App\Http\Requests\Clinic\StoreBodyCheckInRequest;
use App\Models\Profile;
use App\Models\User;
use App\Support\BodyWeight;

class SaveBodyCheckIn
{
    public function handle(User $user, Profile $profile, StoreBodyCheckInRequest $request): void
    {
        $height = $request->heightCm();
        $attributes = [
            'body_checkin_pending' => false,
        ];

        if ($height !== null && $user->can('update', $profile)) {
            $attributes['height_cm'] = $height;
            $attributes['height_prompt_skipped_at'] = null;
        }

        $profile->update($attributes);

        $weight = $request->validated('weight') ?? $request->input('weight');

        if ($weight === null || $weight === '') {
            return;
        }

        $profile->weights()->create([
            'kg' => BodyWeight::toKg((string) $weight, $profile->weight_unit),
            'measured_at' => $profile->nowInTimezone()->utc(),
        ]);
    }
}
