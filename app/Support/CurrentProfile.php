<?php

namespace App\Support;

use App\Enums\GlucoseUnit;
use App\Enums\WeightUnit;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;

class CurrentProfile
{
    public const HEADER = 'X-Profile-Id';

    public function handle(User $user, Request $request): Profile
    {
        $profiles = $user->accessibleProfiles()->orderBy('name')->get();

        $headerId = $request->header(self::HEADER);

        if ($headerId !== null && $headerId !== '') {
            $fromHeader = $profiles->firstWhere('id', (int) $headerId);

            if ($fromHeader !== null) {
                $this->remember($request, $fromHeader->id);

                return $fromHeader;
            }
        }

        if ($request->hasSession()) {
            $sessionId = $request->session()->get('current_profile_id');

            if ($sessionId !== null) {
                $fromSession = $profiles->firstWhere('id', (int) $sessionId);

                if ($fromSession !== null) {
                    return $fromSession;
                }
            }
        }

        $owned = $profiles->firstWhere('user_id', $user->id);
        $profile = $profiles->firstWhere('is_default', true) ?? $owned ?? $profiles->first();

        if ($profile === null) {
            $profile = $user->profiles()->create([
                'name' => $user->name,
                'timezone' => SystemTimezone::detected() ?? (string) config('app.timezone'),
                'glucose_unit' => GlucoseUnit::MgDl,
                'weight_unit' => WeightUnit::Kg,
                'is_default' => true,
            ]);
        }

        $this->remember($request, $profile->id);

        return $profile;
    }

    private function remember(Request $request, int $profileId): void
    {
        if ($request->hasSession()) {
            $request->session()->put('current_profile_id', $profileId);
        }
    }
}
