<?php

namespace App\Http\Controllers;

use App\Enums\BpDaySlot;
use App\Support\CurrentProfile;
use App\Support\ReadingReminders;
use App\Support\ReadingTimeline;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request, CurrentProfile $currentProfile, ReadingReminders $reminders): Response
    {
        $profile = $currentProfile->handle($request->user(), $request);
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

        $profiles = $request->user()->profiles()->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Home', [
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
            ],
            'profiles' => $profiles,
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
