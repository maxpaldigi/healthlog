<?php

namespace App\Support;

use App\Enums\BpDaySlot;
use App\Models\Profile;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;

final class ReadingReminders
{
    /**
     * @return list<BpDaySlot>
     */
    public function dueSlots(Profile $profile): array
    {
        if (! $profile->reminders_enabled) {
            return [];
        }

        $now = $profile->nowInTimezone();
        $todayStart = $now->copy()->startOfDay()->utc();
        $todayEnd = $now->copy()->endOfDay()->utc();
        $filled = [];

        foreach ($profile->bpReadings()->forPeriod($todayStart, $todayEnd)->get() as $reading) {
            $filled[BpDaySlot::fromLocalTime($reading->measured_at->timezone($profile->timezone))->value] = true;
        }

        $due = [];

        foreach (BpDaySlot::dayOrder() as $slot) {
            if (isset($filled[$slot->value]) || $this->alreadySent($profile, $slot, $now)) {
                continue;
            }

            $at = $this->reminderAt($profile, $slot, $now);

            if ($now->lessThan($at) || $now->greaterThan($at->copy()->addMinutes(20))) {
                continue;
            }

            $due[] = $slot;
        }

        return $due;
    }

    /**
     * Slots that still need a reading and whose reminder time has already passed today.
     *
     * @return list<array{key: string, label: string, time: string}>
     */
    public function openDueSlots(Profile $profile): array
    {
        $now = $profile->nowInTimezone();
        $todayStart = $now->copy()->startOfDay()->utc();
        $todayEnd = $now->copy()->endOfDay()->utc();
        $filled = [];

        foreach ($profile->bpReadings()->forPeriod($todayStart, $todayEnd)->get() as $reading) {
            $filled[BpDaySlot::fromLocalTime($reading->measured_at->timezone($profile->timezone))->value] = true;
        }

        $open = [];

        foreach (BpDaySlot::dayOrder() as $slot) {
            if (isset($filled[$slot->value]) || $now->lessThan($this->reminderAt($profile, $slot, $now))) {
                continue;
            }

            $open[] = [
                'key' => $slot->value,
                'label' => $slot->label(),
                'time' => $profile->reminderTimes()[$slot->value],
            ];
        }

        return $open;
    }

    public function markSent(Profile $profile, BpDaySlot $slot): void
    {
        Cache::put($this->cacheKey($profile, $slot, $profile->nowInTimezone()), true, now()->addDay());
    }

    public function alreadySent(Profile $profile, BpDaySlot $slot, CarbonInterface $localNow): bool
    {
        return Cache::has($this->cacheKey($profile, $slot, $localNow));
    }

    private function reminderAt(Profile $profile, BpDaySlot $slot, CarbonInterface $localNow): CarbonInterface
    {
        [$hour, $minute] = array_map('intval', explode(':', $profile->reminderTimes()[$slot->value]));

        return $localNow->copy()->setTime($hour, $minute);
    }

    private function cacheKey(Profile $profile, BpDaySlot $slot, CarbonInterface $localNow): string
    {
        return 'healthlog-reminder:'.$profile->id.':'.$slot->value.':'.$localNow->toDateString();
    }
}
