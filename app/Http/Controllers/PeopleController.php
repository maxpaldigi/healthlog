<?php

namespace App\Http\Controllers;

use App\Enums\BpDaySlot;
use App\Support\CareAlerts;
use App\Support\CurrentProfile;
use App\Support\ReadingTimeline;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PeopleController extends Controller
{
    public function __invoke(Request $request, CurrentProfile $currentProfile, CareAlerts $alerts): Response
    {
        $user = $request->user();
        $current = $currentProfile->handle($user, $request);

        $profiles = $user->accessibleProfiles()
            ->with(['bpReadings' => fn ($query) => $query->orderByDesc('measured_at')->limit(1), 'glucoseReadings' => fn ($query) => $query->orderByDesc('measured_at')->limit(1)])
            ->orderBy('name')
            ->get();

        $people = $profiles->map(function ($profile) use ($user, $alerts): array {
            $todayStart = $profile->nowInTimezone()->startOfDay()->utc();
            $todayEnd = $profile->nowInTimezone()->endOfDay()->utc();
            $bpToday = $profile->bpReadings()->forPeriod($todayStart, $todayEnd)->get();
            $filled = [];

            foreach ($bpToday as $reading) {
                $slot = BpDaySlot::fromLocalTime($reading->measured_at->timezone($profile->clockTimezone()));
                $filled[$slot->value] = true;
            }

            $latestBp = $profile->bpReadings->first();
            $latestGlucose = $profile->glucoseReadings->first();
            $latest = null;

            if ($latestBp !== null || $latestGlucose !== null) {
                $items = ReadingTimeline::merge(
                    $profile,
                    collect($latestBp !== null ? [$latestBp] : []),
                    collect($latestGlucose !== null ? [$latestGlucose] : []),
                );
                $latest = $items[0] ?? null;
            }

            $active = $alerts->active($profile);
            $role = $user->roleOn($profile);

            return [
                'id' => $profile->id,
                'name' => $profile->name,
                'role' => $role?->value,
                'role_label' => $role?->label(),
                'today_filled' => count($filled),
                'today_target' => 3,
                'last_reading' => $latest === null ? null : [
                    'title' => $latest['title'],
                    'when' => $latest['measured_at_label'],
                ],
                'alerts' => $active,
                'needs_attention' => $active !== [],
            ];
        })
            ->sortByDesc('needs_attention')
            ->values()
            ->all();

        return Inertia::render('People', [
            'people' => $people,
            'current_id' => $current->id,
        ]);
    }
}
