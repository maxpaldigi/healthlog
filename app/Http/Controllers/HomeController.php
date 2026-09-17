<?php

namespace App\Http\Controllers;

use App\Support\CareAlerts;
use App\Support\CurrentProfile;
use App\Support\HomePayload;
use App\Support\ReadingReminders;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request, CurrentProfile $currentProfile, ReadingReminders $reminders, CareAlerts $alerts): Response|RedirectResponse
    {
        $user = $request->user();
        $profile = $currentProfile->handle($user, $request);
        $payload = HomePayload::make($user, $profile, $reminders, $alerts);

        if ($payload['body_check_in_required']) {
            return redirect()->route('clinic.body');
        }

        unset($payload['body_check_in_required']);

        // Web Home still expects full Profile models for the switcher.
        $payload['profiles'] = $user->accessibleProfiles()->orderBy('name')->get();

        return Inertia::render('Home', $payload);
    }
}
