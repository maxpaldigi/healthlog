<?php

namespace App\Http\Controllers\Settings;

use App\Enums\GlucoseUnit;
use App\Enums\ProfileRole;
use App\Enums\WeightUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreHealthProfileRequest;
use App\Http\Requests\Settings\UpdateHealthProfileRequest;
use App\Models\Profile;
use App\Support\CurrentProfile;
use App\Support\ProfileSettings;
use App\Support\Timezones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HealthProfileController extends Controller
{
    public function edit(Request $request, CurrentProfile $currentProfile): Response
    {
        $current = $currentProfile->handle($request->user(), $request);
        $user = $request->user();
        $profiles = $user->accessibleProfiles()->orderBy('name')->get();
        $ownedCount = $user->profiles()->count();
        $role = $user->roleOn($current);
        $members = $current->members()->orderBy('role')->orderBy('email')->get();

        return Inertia::render('settings/HealthProfiles', [
            'current' => ProfileSettings::payload($current),
            'role' => $role?->value,
            'canManage' => $role === ProfileRole::Owner,
            'canInvite' => $user->can('invite', $current),
            'members' => $members->map(fn ($member): array => [
                'id' => $member->id,
                'email' => $member->email,
                'role' => $member->role->value,
                'role_label' => $member->role->label(),
                'accepted' => $member->isAccepted(),
                'can_revoke' => $user->can('revokeMember', [$current, $member]),
            ])->values()->all(),
            'profiles' => $profiles->map(fn (Profile $profile): array => [
                'id' => $profile->id,
                'name' => $profile->name,
                'is_default' => $profile->is_default,
                'is_current' => $profile->id === $current->id,
            ])->values()->all(),
            'timezones' => Timezones::common($current->timezone),
            'glucoseUnits' => ProfileSettings::glucoseUnits(),
            'weightUnits' => ProfileSettings::weightUnits(),
            'canDelete' => $role === ProfileRole::Owner && $ownedCount > 1,
        ]);
    }

    public function store(StoreHealthProfileRequest $request): RedirectResponse
    {
        $this->authorize('create', Profile::class);

        $data = $request->validated();
        $current = $request->user()->profiles()->first();

        $profile = $request->user()->profiles()->create([
            'name' => $data['name'],
            'timezone' => $data['timezone'],
            'glucose_unit' => GlucoseUnit::from($data['glucose_unit']),
            'weight_unit' => WeightUnit::from($data['weight_unit']),
            'is_default' => $current === null,
            'body_checkin_pending' => true,
            'reminders_enabled' => false,
            'reminder_morning' => '08:00',
            'reminder_afternoon' => '14:00',
            'reminder_evening' => '20:00',
        ]);

        $request->session()->put('current_profile_id', $profile->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Health profile added.']);

        return to_route('health-profiles.edit');
    }

    public function update(UpdateHealthProfileRequest $request, Profile $profile): RedirectResponse
    {
        $profile->update($request->profileAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Health profile saved.']);

        return to_route('health-profiles.edit');
    }

    public function destroy(Request $request, Profile $profile, CurrentProfile $currentProfile): RedirectResponse
    {
        $this->authorize('delete', $profile);

        $user = $request->user();

        abort_if($user->profiles()->count() <= 1, 422, 'Keep at least one health profile.');

        $wasCurrent = $currentProfile->handle($user, $request)->is($profile);
        $wasDefault = $profile->is_default;

        $profile->delete();

        $remaining = $user->profiles()->orderBy('name')->first();

        if ($remaining !== null && $wasDefault) {
            $remaining->update(['is_default' => true]);
        }

        if ($remaining !== null && $wasCurrent) {
            $request->session()->put('current_profile_id', $remaining->id);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Health profile removed.']);

        return to_route('health-profiles.edit');
    }
}
