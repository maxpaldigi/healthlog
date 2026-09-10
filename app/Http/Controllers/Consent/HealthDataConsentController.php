<?php

namespace App\Http\Controllers\Consent;

use App\Actions\Auth\RecordConsent;
use App\Enums\ConsentType;
use App\Http\Controllers\Controller;
use App\Models\Consent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HealthDataConsentController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if ($user->hasGrantedConsent(ConsentType::HealthData)) {
            return redirect()->route('home');
        }

        return Inertia::render('consent/HealthData', [
            'policyVersion' => Consent::POLICY_VERSION,
        ]);
    }

    public function store(Request $request, RecordConsent $recordConsent): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $request->validate([
            'health_data_consent' => ['accepted'],
        ]);

        if (! $user->hasGrantedConsent(ConsentType::HealthData)) {
            $recordConsent->handle($user, ConsentType::HealthData);
        }

        return redirect()->route('home');
    }
}
