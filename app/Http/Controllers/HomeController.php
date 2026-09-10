<?php

namespace App\Http\Controllers;

use App\Enums\BpDaySlot;
use App\Enums\ProfileRole;
use App\Support\CareAlerts;
use App\Support\CurrentProfile;
use App\Support\ReadingReminders;
use App\Support\ReadingTimeline;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request, CurrentProfile $currentProfile, ReadingReminders $reminders, CareAlerts $alerts): Response
    {
        $user = $request->user();
        $profile = $currentProfile->handle($user, $request);
        $todayStart = $profile->nowInTimezone()->startOfDay()->utc();
        $todayEnd = $profile->nowInTimezone()->endOfDay()->utc();

        $bpToday = $profile->bpReadings()->forPeriod($todayStart, $todayEnd)->get();
        $filledSlots = [];

        foreach ($bpToday as $reading) {
            $slot = BpDaySlot::fromLocalTime($reading->measured_at->timezone($profile->clockTimezone()));
            $filledSlots[$slot->value] = true;
        }

        $todaySlots = collect(BpDaySlot::dayOrder())
            ->map(fn (BpDaySlot $slot): array => [
                'key' => $slot->value,
                'label' => $slot->label(),
                'filled' => isset($filledSlots[$slot->value]),
            ])
            ->all();

        $recentBp = $profile->bpReadings()->orderByDesc('measured_at')->limit(3)->get();
        $recentGlucose = $profile->glucoseReadings()->orderByDesc('measured_at')->limit(3)->get();
        $recent = array_slice(ReadingTimeline::merge($profile, $recentBp, $recentGlucose), 0, 3);

        $profiles = $user->accessibleProfiles()->orderBy('name')->get();
        $role = $user->roleOn($profile);
        $caregivers = $profile->members()
            ->accepted()
            ->where('role', ProfileRole::Caregiver)
            ->orderBy('email')
            ->get(['id', 'email', 'role']);

        return Inertia::render('Home', [
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
            ],
            'profiles' => $profiles,
            'role' => $role?->value,
            'access' => [
                'is_owner' => $role === ProfileRole::Owner,
                'caregivers' => $caregivers->map(fn ($member): array => [
                    'id' => $member->id,
                    'email' => $member->email,
                    'label' => $member->email,
                ])->values()->all(),
            ],
            'alerts' => $alerts->active($profile),
            'todayCount' => count($filledSlots),
            'todayTarget' => 3,
            'todaySlots' => $todaySlots,
            'recent' => $recent,
            'reminders' => [
                'enabled' => $profile->reminders_enabled,
                'timezone' => $profile->timezone,
                'times' => $profile->reminderTimes(),
                'due' => $reminders->openDueSlots($profile),
            ],
        ]);
    }
}
