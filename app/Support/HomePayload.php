<?php

namespace App\Support;

use App\Enums\BpDaySlot;
use App\Enums\ProfileRole;
use App\Models\Profile;
use App\Models\User;

/**
 * Shared Home payload for Inertia and the JSON API.
 */
final class HomePayload
{
    /**
     * @return array<string, mixed>
     */
    public static function make(User $user, Profile $profile, ReadingReminders $reminders, CareAlerts $alerts): array
    {
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

        return [
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
            ],
            'profiles' => $profiles->map(fn (Profile $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'is_default' => $item->is_default,
            ])->values()->all(),
            'role' => $role?->value,
            'access' => [
                'is_owner' => $role === ProfileRole::Owner,
                'caregivers' => $caregivers->map(fn ($member): array => [
                    'id' => $member->id,
                    'email' => $member->email,
                    'label' => $member->email,
                ])->values()->all(),
            ],
            'alerts' => array_values(array_filter(
                $alerts->active($profile),
                fn (array $alert): bool => $alert['message'] !== 'No readings logged yet',
            )),
            'todayCount' => count($filledSlots),
            'todayTarget' => 3,
            'todaySlots' => $todaySlots,
            'summarySpeech' => ReadingSpeech::home(count($filledSlots), 3, $todaySlots),
            'recent' => $recent,
            'foodResponse' => FoodResponse::make($profile),
            'bodyPrompt' => BodyCheckIn::homeCard($profile),
            'clinicNote' => ClinicianNote::current($profile),
            'reminders' => [
                'enabled' => $profile->reminders_enabled,
                'timezone' => $profile->timezone,
                'times' => $profile->reminderTimes(),
                'due' => $reminders->openDueSlots($profile),
            ],
            'body_check_in_required' => $user->can('update', $profile)
                && BodyCheckIn::shouldOpenAfterSignIn($profile),
        ];
    }
}
