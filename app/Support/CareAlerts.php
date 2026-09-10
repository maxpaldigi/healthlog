<?php

namespace App\Support;

use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Models\Profile;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

final class CareAlerts
{
    public const TYPE_INACTIVITY = 'inactivity';

    public const TYPE_STREAK = 'streak';

    /**
     * @return list<array{key: string, message: string}>
     */
    public function active(Profile $profile): array
    {
        $alerts = [];

        $inactivity = $this->inactivity($profile);

        if ($inactivity !== null) {
            $alerts[] = $inactivity;
        }

        $streak = $this->streak($profile);

        if ($streak !== null) {
            $alerts[] = $streak;
        }

        return $alerts;
    }

    /**
     * @return list<array{key: string, message: string}>
     */
    public function dueToSend(Profile $profile): array
    {
        return array_values(array_filter(
            $this->active($profile),
            fn (array $alert): bool => ! $this->alreadySent($profile, $alert['key']),
        ));
    }

    public function markSent(Profile $profile, string $type): void
    {
        Cache::put($this->cacheKey($profile, $type), true, now()->addDay());
    }

    public function alreadySent(Profile $profile, string $type): bool
    {
        return Cache::has($this->cacheKey($profile, $type));
    }

    /**
     * @return array{key: string, message: string}|null
     */
    public function inactivity(Profile $profile): ?array
    {
        $days = max(1, $profile->alert_inactivity_days);
        $now = $profile->nowInTimezone();
        $cutoff = $now->copy()->subDays($days);

        $latest = $this->latestReadingAt($profile);

        if ($latest !== null && $latest->greaterThan($cutoff)) {
            return null;
        }

        if ($latest === null) {
            return [
                'key' => self::TYPE_INACTIVITY,
                'message' => 'No readings logged yet',
            ];
        }

        $weekday = $latest->timezone($profile->clockTimezone())->isoFormat('dddd');

        return [
            'key' => self::TYPE_INACTIVITY,
            'message' => 'No readings since '.$weekday,
        ];
    }

    /**
     * @return array{key: string, message: string}|null
     */
    public function streak(Profile $profile): ?array
    {
        $needed = max(1, $profile->alert_streak);

        if ($this->bloodPressureStreak($profile, $needed) || $this->glucoseStreak($profile, $needed)) {
            return [
                'key' => self::TYPE_STREAK,
                'message' => $needed.' readings above target',
            ];
        }

        return null;
    }

    private function bloodPressureStreak(Profile $profile, int $needed): bool
    {
        $readings = $profile->bpReadings()->orderByDesc('measured_at')->limit($needed)->get();

        if ($readings->count() < $needed) {
            return false;
        }

        return $readings->every(
            fn (BpReading $reading): bool => in_array(BloodPressureColor::for($reading->systolic, $reading->diastolic), ['high', 'crisis'], true),
        );
    }

    private function glucoseStreak(Profile $profile, int $needed): bool
    {
        if ($profile->glucose_high_mg_dl === null) {
            return false;
        }

        $readings = $profile->glucoseReadings()->orderByDesc('measured_at')->limit($needed)->get();

        if ($readings->count() < $needed) {
            return false;
        }

        return $readings->every(
            fn (GlucoseReading $reading): bool => GlucoseRange::label($profile, (string) $reading->value_mg_dl) === 'High',
        );
    }

    private function latestReadingAt(Profile $profile): ?CarbonInterface
    {
        $bp = $profile->bpReadings()->max('measured_at');
        $glucose = $profile->glucoseReadings()->max('measured_at');

        if ($bp === null && $glucose === null) {
            return null;
        }

        if ($bp === null) {
            return Carbon::parse((string) $glucose);
        }

        if ($glucose === null) {
            return Carbon::parse((string) $bp);
        }

        $bpAt = Carbon::parse((string) $bp);
        $glucoseAt = Carbon::parse((string) $glucose);

        return $bpAt->greaterThan($glucoseAt) ? $bpAt : $glucoseAt;
    }

    private function cacheKey(Profile $profile, string $type): string
    {
        return 'healthlog-care-alert:'.$profile->id.':'.$type.':'.$profile->nowInTimezone()->toDateString();
    }
}
