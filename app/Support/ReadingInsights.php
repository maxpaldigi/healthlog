<?php

namespace App\Support;

use App\Enums\BpDaySlot;
use App\Enums\GlucoseContext;
use App\Enums\TargetSetBy;
use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Models\Profile;
use Illuminate\Support\Collection;

final class ReadingInsights
{
    /**
     * @param  Collection<int, BpReading>  $bpReadings
     * @param  Collection<int, GlucoseReading>  $glucoseReadings
     * @return array<string, mixed>
     */
    public static function make(Profile $profile, Collection $bpReadings, Collection $glucoseReadings): array
    {
        $tz = self::timezone($profile);

        $bpDays = $bpReadings
            ->groupBy(fn (BpReading $reading): string => $reading->measured_at->timezone($tz)->toDateString())
            ->sortKeys();

        $glucoseDays = $glucoseReadings
            ->groupBy(fn (GlucoseReading $reading): string => $reading->measured_at->timezone($tz)->toDateString())
            ->sortKeys();

        $bpTrend = $bpDays->map(function (Collection $dayReadings, string $date) use ($tz): array {
            $local = $dayReadings->first()?->measured_at->timezone($tz);

            return [
                'date' => $date,
                'label' => $local?->format('j M') ?? $date,
                'systolic' => (int) round((float) $dayReadings->avg('systolic')),
                'diastolic' => (int) round((float) $dayReadings->avg('diastolic')),
            ];
        })->values()->all();

        $glucoseTrend = $glucoseDays->map(function (Collection $dayReadings, string $date) use ($profile, $tz): array {
            $local = $dayReadings->first()?->measured_at->timezone($tz);
            $averageMgDl = (string) round((float) $dayReadings->avg('value_mg_dl'), 2);

            return [
                'date' => $date,
                'label' => $local?->format('j M') ?? $date,
                'value' => (float) Glucose::fromMgDl($averageMgDl, $profile->glucose_unit),
            ];
        })->values()->all();

        $slots = collect(BpDaySlot::dayOrder())
            ->map(function (BpDaySlot $slot) use ($bpReadings, $tz): array {
                $inSlot = $bpReadings->filter(
                    fn (BpReading $reading): bool => BpDaySlot::fromLocalTime($reading->measured_at->timezone($tz)) === $slot
                );

                return [
                    'key' => $slot->value,
                    'label' => $slot->label(),
                    'count' => $inSlot->count(),
                    'average_systolic' => $inSlot->isEmpty() ? null : (int) round((float) $inSlot->avg('systolic')),
                    'average_diastolic' => $inSlot->isEmpty() ? null : (int) round((float) $inSlot->avg('diastolic')),
                ];
            })
            ->values()
            ->all();

        $daysWithThreeSlots = $bpDays->filter(function (Collection $dayReadings) use ($tz): bool {
            $filled = [];

            foreach ($dayReadings as $reading) {
                $filled[BpDaySlot::fromLocalTime($reading->measured_at->timezone($tz))->value] = true;
            }

            return count($filled) === 3;
        })->count();

        $latestBp = $bpReadings->sortByDesc('measured_at')->first();
        $latestGlucose = $glucoseReadings->sortByDesc('measured_at')->first();

        $averageSystolic = $bpReadings->isEmpty() ? null : (int) round((float) $bpReadings->avg('systolic'));
        $averageDiastolic = $bpReadings->isEmpty() ? null : (int) round((float) $bpReadings->avg('diastolic'));
        $averageGlucose = $glucoseReadings->isEmpty()
            ? null
            : Glucose::fromMgDl((string) round((float) $glucoseReadings->avg('value_mg_dl'), 2), $profile->glucose_unit);

        $unit = $profile->glucose_unit->label();
        $stats = [
            'bp_variability' => self::bpVariability($bpReadings),
            'morning_minus_evening' => self::morningMinusEvening($slots),
            'time_in_range' => self::timeInRange($profile, $glucoseReadings),
            'post_meal_minus_fasting' => self::postMealMinusFasting($profile, $glucoseReadings),
        ];
        $statsLines = [];

        foreach ($stats as $row) {
            if (is_array($row)) {
                $statsLines[] = $row['line'];
            }
        }

        return [
            'summary' => [
                'bp_count' => $bpReadings->count(),
                'glucose_count' => $glucoseReadings->count(),
                'latest_bp' => $latestBp instanceof BpReading ? $latestBp->systolic.' / '.$latestBp->diastolic : null,
                'latest_bp_range_label' => $latestBp instanceof BpReading
                    ? BloodPressureColor::label($latestBp->systolic, $latestBp->diastolic)
                    : null,
                'average_bp' => $averageSystolic !== null ? $averageSystolic.' / '.$averageDiastolic : null,
                'latest_glucose' => $latestGlucose instanceof GlucoseReading
                    ? Glucose::fromMgDl((string) $latestGlucose->value_mg_dl, $profile->glucose_unit).' '.$unit
                    : null,
                'latest_glucose_range_label' => $latestGlucose instanceof GlucoseReading
                    ? GlucoseRange::label($profile, (string) $latestGlucose->value_mg_dl)
                    : null,
                'average_glucose' => $averageGlucose !== null ? $averageGlucose.' '.$unit : null,
                'days_with_bp' => $bpDays->count(),
                'days_with_three_bp' => $daysWithThreeSlots,
                'glucose_unit' => $unit,
            ],
            'bp_trend' => $bpTrend,
            'glucose_trend' => $glucoseTrend,
            'bp_slots' => $slots,
            'stats' => $stats,
            'stats_lines' => $statsLines,
            'summary_text' => self::summaryText(
                $bpReadings->count(),
                $glucoseReadings->count(),
                $averageSystolic,
                $averageDiastolic,
                $averageGlucose,
                $unit,
                $daysWithThreeSlots,
                $bpDays->count(),
                $statsLines,
            ),
        ];
    }

    private static function timezone(Profile $profile): string
    {
        if (is_string($profile->timezone) && $profile->timezone !== '') {
            return $profile->timezone;
        }

        return $profile->clockTimezone();
    }

    /**
     * Sample standard deviation and coefficient of variation of systolic mmHg.
     * Display only — never a diagnosis.
     *
     * @param  Collection<int, BpReading>  $bpReadings
     * @return array{sample_count: int, sd_systolic: string, cv_percent: string, line: string}|null
     */
    private static function bpVariability(Collection $bpReadings): ?array
    {
        $count = $bpReadings->count();

        if ($count < 2) {
            return null;
        }

        $sum = '0';

        foreach ($bpReadings as $reading) {
            $sum = bcadd($sum, (string) $reading->systolic, 4);
        }

        $mean = bcdiv($sum, (string) $count, 6);

        if (bccomp($mean, '0', 6) === 0) {
            return null;
        }

        $squares = '0';

        foreach ($bpReadings as $reading) {
            $deviation = bcsub((string) $reading->systolic, $mean, 6);
            $squares = bcadd($squares, bcmul($deviation, $deviation, 8), 8);
        }

        $sd = self::roundPlaces(bcsqrt(bcdiv($squares, (string) ($count - 1), 8), 8), 1);
        $cv = self::roundPlaces(bcmul(bcdiv($sd, $mean, 8), '100', 6), 1);

        return [
            'sample_count' => $count,
            'sd_systolic' => $sd,
            'cv_percent' => $cv,
            'line' => 'Systolic standard deviation was '.$sd.' mmHg (CV '.$cv.'%).',
        ];
    }

    /**
     * Morning slot average systolic minus evening slot average systolic.
     * Display only — never a diagnosis.
     *
     * @param  list<array{key: string, label: string, count: int, average_systolic: int|null, average_diastolic: int|null}>  $slots
     * @return array{morning_systolic: int, evening_systolic: int, difference: int, line: string}|null
     */
    private static function morningMinusEvening(array $slots): ?array
    {
        $morning = null;
        $evening = null;

        foreach ($slots as $slot) {
            if ($slot['key'] === BpDaySlot::Morning->value) {
                $morning = $slot['average_systolic'];
            }

            if ($slot['key'] === BpDaySlot::Evening->value) {
                $evening = $slot['average_systolic'];
            }
        }

        if ($morning === null || $evening === null) {
            return null;
        }

        $difference = $morning - $evening;

        $line = match (true) {
            $difference > 0 => 'Morning systolic averaged '.$difference.' mmHg above evening.',
            $difference < 0 => 'Morning systolic averaged '.abs($difference).' mmHg below evening.',
            default => 'Morning systolic averaged the same as evening.',
        };

        return [
            'morning_systolic' => $morning,
            'evening_systolic' => $evening,
            'difference' => $difference,
            'line' => $line,
        ];
    }

    /**
     * Share of sugar readings inside the active targets on the profile.
     * Display only — never a diagnosis.
     *
     * @param  Collection<int, GlucoseReading>  $glucoseReadings
     * @return array{in_range: int, total: int, percent: int, line: string}|null
     */
    private static function timeInRange(Profile $profile, Collection $glucoseReadings): ?array
    {
        $total = $glucoseReadings->count();

        if ($total === 0 || ($profile->glucose_low_mg_dl === null && $profile->glucose_high_mg_dl === null)) {
            return null;
        }

        $inRange = $glucoseReadings
            ->filter(fn (GlucoseReading $reading): bool => GlucoseRange::label($profile, (string) $reading->value_mg_dl) === null)
            ->count();

        $percent = (int) self::roundPlaces(bcmul(bcdiv((string) $inRange, (string) $total, 6), '100', 4), 0);

        $whose = $profile->glucose_target_set_by === TargetSetBy::Clinician
            ? 'the clinic targets'
            : 'your targets';

        return [
            'in_range' => $inRange,
            'total' => $total,
            'percent' => $percent,
            'line' => $inRange.' of '.$total.' sugar readings were inside '.$whose.' ('.$percent.'%).',
        ];
    }

    /**
     * Mean 2-hour after-meal sugar minus mean fasting, in the profile unit.
     * Display only — never a diagnosis.
     *
     * @param  Collection<int, GlucoseReading>  $glucoseReadings
     * @return array{difference: string, unit: string, line: string}|null
     */
    private static function postMealMinusFasting(Profile $profile, Collection $glucoseReadings): ?array
    {
        $fasting = $glucoseReadings->filter(
            fn (GlucoseReading $reading): bool => $reading->context === GlucoseContext::Fasting,
        );
        $postMeal = $glucoseReadings->filter(
            fn (GlucoseReading $reading): bool => $reading->context === GlucoseContext::PostMeal2h,
        );

        if ($fasting->isEmpty() || $postMeal->isEmpty()) {
            return null;
        }

        $fastingMean = self::meanMgDl($fasting);
        $postMean = self::meanMgDl($postMeal);
        $diffMgDl = bcsub($postMean, $fastingMean, 2);
        $unit = $profile->glucose_unit->label();
        $absolute = NutritionFacts::display(Glucose::fromMgDl(self::absoluteDecimal($diffMgDl), $profile->glucose_unit));

        $line = match (bccomp($diffMgDl, '0', 2)) {
            1 => 'Mean 2-hour after-meal sugar was '.$absolute.' '.$unit.' above mean fasting.',
            -1 => 'Mean 2-hour after-meal sugar was '.$absolute.' '.$unit.' below mean fasting.',
            default => 'Mean 2-hour after-meal sugar was the same as mean fasting.',
        };

        return [
            'difference' => $absolute,
            'unit' => $unit,
            'line' => $line,
        ];
    }

    /**
     * @param  Collection<int, GlucoseReading>  $readings
     */
    private static function meanMgDl(Collection $readings): string
    {
        $sum = '0';

        foreach ($readings as $reading) {
            $sum = bcadd($sum, (string) $reading->value_mg_dl, 4);
        }

        return bcdiv($sum, (string) $readings->count(), 2);
    }

    private static function absoluteDecimal(string $value): string
    {
        return bccomp($value, '0', 2) < 0 ? bcmul($value, '-1', 2) : $value;
    }

    private static function roundPlaces(string $value, int $places): string
    {
        $factor = bcpow('10', (string) $places, 0);
        $scaled = bcmul($value, $factor, $places + 2);
        $adjustment = bccomp($scaled, '0', 4) >= 0 ? '0.5' : '-0.5';

        return bcdiv(bcadd($scaled, $adjustment, 0), $factor, $places);
    }

    /**
     * @param  list<string>  $statsLines
     */
    private static function summaryText(
        int $bpCount,
        int $glucoseCount,
        ?int $averageSystolic,
        ?int $averageDiastolic,
        ?string $averageGlucose,
        string $unit,
        int $completeDays,
        int $bpDays,
        array $statsLines,
    ): string {
        if ($bpCount === 0 && $glucoseCount === 0) {
            return 'No readings in this date range yet.';
        }

        $parts = [];

        if ($averageSystolic !== null) {
            $parts[] = 'Average blood pressure was '.$averageSystolic.' / '.$averageDiastolic
                .' from '.$bpCount.' reading'.($bpCount === 1 ? '' : 's').'.';
        }

        if ($bpDays > 0) {
            $parts[] = $completeDays.' of '.$bpDays.' days had morning, afternoon, and evening blood pressure.';
        }

        if ($averageGlucose !== null) {
            $parts[] = 'Average sugar was '.$averageGlucose.' '.$unit
                .' from '.$glucoseCount.' reading'.($glucoseCount === 1 ? '' : 's').'.';
        }

        return implode(' ', array_merge($parts, $statsLines));
    }
}
