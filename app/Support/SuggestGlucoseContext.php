<?php

namespace App\Support;

use App\Enums\GlucoseContext;
use Carbon\CarbonInterface;

final class SuggestGlucoseContext
{
    /**
     * Pre-select a glucose context from the profile's local time.
     *
     * 05:00–09:59 → Fasting
     * 12:00–14:59 and 19:00–21:59 → After meal 2h
     * 22:00+ → Bedtime (21:00–21:59 is covered by the after-meal window)
     * else → Random
     */
    public static function forLocalTime(CarbonInterface $local): GlucoseContext
    {
        $minutes = ($local->hour * 60) + $local->minute;

        if ($minutes >= (5 * 60) && $minutes <= (9 * 60) + 59) {
            return GlucoseContext::Fasting;
        }

        if (
            ($minutes >= (12 * 60) && $minutes <= (14 * 60) + 59)
            || ($minutes >= (19 * 60) && $minutes <= (21 * 60) + 59)
        ) {
            return GlucoseContext::PostMeal2h;
        }

        if ($minutes >= (21 * 60)) {
            return GlucoseContext::Bedtime;
        }

        return GlucoseContext::Random;
    }
}
