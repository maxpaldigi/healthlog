<?php

namespace App\Http\Controllers;

use App\Enums\ConsentType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CompleteOnboardingController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($user->hasGrantedConsent(ConsentType::HealthData), 403);

        $user->forceFill([
            'onboarding_completed_at' => now(),
        ])->save();

        return back();
    }
}
