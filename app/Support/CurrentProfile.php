<?php

namespace App\Support;

use App\Enums\GlucoseUnit;
use App\Enums\WeightUnit;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;

class CurrentProfile
{
    public function handle(User $user, Request $request): Profile
    {
        $profiles = $user->profiles()->get();

        $sessionId = $request->session()->get('current_profile_id');

        if ($sessionId !== null) {
            $fromSession = $profiles->firstWhere('id', (int) $sessionId);

            if ($fromSession !== null) {
                return $fromSession;
            }
        }

        $profile = $profiles->firstWhere('is_default', true) ?? $profiles->first();

        if ($profile === null) {
            $profile = $user->profiles()->create([
                'name' => $user->name,
                'timezone' => 'Asia/Kolkata',
                'glucose_unit' => GlucoseUnit::MgDl,
                'weight_unit' => WeightUnit::Kg,
                'is_default' => true,
            ]);
        }

        $request->session()->put('current_profile_id', $profile->id);

        return $profile;
    }
}
