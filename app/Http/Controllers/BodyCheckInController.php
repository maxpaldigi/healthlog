<?php

namespace App\Http\Controllers;

use App\Actions\Clinic\SaveBodyCheckIn;
use App\Actions\Clinic\SkipBodyCheckIn;
use App\Http\Requests\Clinic\StoreBodyCheckInRequest;
use App\Models\User;
use App\Support\BodyCheckIn;
use App\Support\CurrentProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BodyCheckInController extends Controller
{
    public function create(Request $request, CurrentProfile $currentProfile): Response
    {
        $profile = $currentProfile->handle($request->user(), $request);
        $latest = BodyCheckIn::latestWeight($profile);

        return Inertia::render('clinic/BodyCheckIn', [
            'height_cm' => $profile->height_cm === null ? '' : (string) $profile->height_cm,
            'can_edit_height' => $request->user()?->can('update', $profile) ?? false,
            'weight_unit' => $profile->weight_unit->label(),
            'weight_due' => BodyCheckIn::weightIsDue($profile),
            'last_weight_on' => $latest?->measured_at
                ?->timezone($profile->clockTimezone())
                ->format('j M Y'),
        ]);
    }

    public function store(
        StoreBodyCheckInRequest $request,
        CurrentProfile $currentProfile,
        SaveBodyCheckIn $saveBodyCheckIn,
    ): RedirectResponse {
        $profile = $currentProfile->handle($request->user(), $request);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $saveBodyCheckIn->handle($user, $profile, $request);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Saved.']);

        return to_route('home');
    }

    public function skip(
        Request $request,
        CurrentProfile $currentProfile,
        SkipBodyCheckIn $skipBodyCheckIn,
    ): RedirectResponse {
        $profile = $currentProfile->handle($request->user(), $request);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $skipBodyCheckIn->handle($user, $profile);

        return to_route('home');
    }
}
