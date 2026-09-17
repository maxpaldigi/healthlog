<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\RecordConsent;
use App\Enums\ConsentType;
use App\Http\Controllers\Controller;
use App\Models\Consent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HealthDataConsentController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'granted' => $user->hasGrantedConsent(ConsentType::HealthData),
            'policy_version' => Consent::POLICY_VERSION,
        ]);
    }

    public function store(Request $request, RecordConsent $recordConsent): JsonResponse
    {
        $request->validate([
            'health_data_consent' => ['accepted'],
        ]);

        $user = $request->user();

        if (! $user->hasGrantedConsent(ConsentType::HealthData)) {
            $recordConsent->handle($user, ConsentType::HealthData);
        }

        return response()->json([
            'granted' => true,
            'policy_version' => Consent::POLICY_VERSION,
        ]);
    }
}
