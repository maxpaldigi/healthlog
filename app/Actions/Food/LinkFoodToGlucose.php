<?php

namespace App\Actions\Food;

use App\Enums\GlucoseContext;
use App\Models\GlucoseReading;
use App\Models\Profile;

class LinkFoodToGlucose
{
    public function handle(Profile $profile): void
    {
        $profile->foodLogs()->update(['glucose_reading_id' => null]);

        $readings = $profile->glucoseReadings()
            ->where('context', GlucoseContext::PostMeal2h)
            ->orderBy('measured_at')
            ->get();

        foreach ($readings as $reading) {
            $this->attachFoods($profile, $reading);
        }
    }

    private function attachFoods(Profile $profile, GlucoseReading $reading): void
    {
        $windowStart = $reading->measured_at->copy()->subHours(2);

        $profile->foodLogs()
            ->whereNull('glucose_reading_id')
            ->where('eaten_at', '>=', $windowStart)
            ->where('eaten_at', '<', $reading->measured_at)
            ->update(['glucose_reading_id' => $reading->id]);
    }
}
