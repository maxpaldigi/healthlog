<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ConsentType;
use App\Http\Controllers\Controller;
use App\Support\AppLocale;
use App\Support\CurrentProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request, CurrentProfile $currentProfile): JsonResponse
    {
        $user = $request->user();
        $profile = $currentProfile->handle($user, $request);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'locale' => app()->getLocale(),
                'health_data_consent' => $user->hasGrantedConsent(ConsentType::HealthData),
            ],
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
                'glucose_unit' => $profile->glucose_unit->value,
                'timezone' => $profile->clockTimezone(),
            ],
            'profiles' => $user->accessibleProfiles()->orderBy('name')->get(['profiles.id', 'profiles.name', 'profiles.is_default']),
            'locales' => AppLocale::options(),
        ]);
    }
}
