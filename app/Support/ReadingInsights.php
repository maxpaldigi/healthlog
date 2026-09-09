<?php

namespace App\Support;

use App\Enums\BpDaySlot;
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
        $bpDays = $bpReadings
            ->groupBy(fn (BpReading $reading): string => $reading->measured_at->timezone($profile->timezone)->toDateString())
            ->sortKeys();

        $glucoseDays = $glucoseReadings
            ->groupBy(fn (GlucoseReading $reading): string => $reading->measured_at->timezone($profile->timezone)->toDateString())
            ->sortKeys();

        $bpTrend = $bpDays->map(function (Collection $dayReadings, string $date) use ($profile): array {
            $local = $dayReadings->first()?->measured_at->timezone($profile->timezone);

            return [
                'date' => $date,
                'label' => $local?->format('j M') ?? $date,
                'systolic' => (int) round((float) $dayReadings->avg('systolic')),
                'diastolic' => (int) round((float) $dayReadings->avg('diastolic')),
            ];
        })->values()->all();

        $glucoseTrend = $glucoseDays->map(function (Collection $dayReadings, string $date) use ($profile): array {
            $local = $dayReadings->first()?->measured_at->timezone($profile->timezone);
            $averageMgDl = (string) round((float) $dayReadings->avg('value_mg_dl'), 2);

            return [
                'date' => $date,
                'label' => $local?->format('j M') ?? $date,
                'value' => (float) Glucose::fromMgDl($averageMgDl, $profile->glucose_unit),
            ];
        })->values()->all();

        $slots = collect(BpDaySlot::dayOrder())
            ->map(function (BpDaySlot $slot) use ($bpReadings, $profile): array {
                $inSlot = $bpReadings->filter(
                    fn (BpReading $reading): bool => BpDaySlot::fromLocalTime($reading->measured_at->timezone($profile->timezone)) === $slot
                );

                return [
                    'key' => $slot->value,
                    'label' => $slot->label(),
                    'count' => $inSlot->count(),
                    'average_systolic' => $inSlot->isEmpty() ? null : (int) round((float) $inSlot->avg('systolic')),
                    'average_diastolic' => $inSlot->isEmpty() ? null : (int) round((float) $inSlot->avg('diastolic')),
                ];
            })
            ->all();

        $daysWithThreeSlots = $bpDays->filter(function (Collection $dayReadings) use ($profile): bool {
            $filled = [];

            foreach ($dayReadings as $reading) {
                $filled[BpDaySlot::fromLocalTime($reading->measured_at->timezone($profile->timezone))->value] = true;
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
            'summary_text' => self::summaryText(
                $bpReadings->count(),
                $glucoseReadings->count(),
                $averageSystolic,
                $averageDiastolic,
                $averageGlucose,
                $unit,
                $daysWithThreeSlots,
                $bpDays->count(),
            ),
        ];
    }

    private static function summaryText(
        int $bpCount,
        int $glucoseCount,
        ?int $averageSystolic,
        ?int $averageDiastolic,
        ?string $averageGlucose,
        string $unit,
        int $completeDays,
        int $bpDays,
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

        return implode(' ', $parts);
    }
}
