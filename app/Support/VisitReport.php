<?php

namespace App\Support;

use App\Enums\BpDaySlot;
use App\Enums\GlucoseContext;
use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Models\Hba1cResult;
use App\Models\Medication;
use App\Models\Profile;
use App\Models\Weight;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class VisitReport
{
    /**
     * @param  Collection<int, BpReading>  $bpReadings
     * @param  Collection<int, GlucoseReading>  $glucoseReadings
     * @param  Collection<int, Weight>  $weights
     * @param  Collection<int, Hba1cResult>  $hba1cResults
     * @param  Collection<int, Medication>  $medications
     * @return array<string, mixed>
     */
    public static function make(
        Profile $profile,
        CarbonInterface $fromLocal,
        CarbonInterface $toLocal,
        Collection $bpReadings,
        Collection $glucoseReadings,
        Collection $weights = new Collection,
        Collection $hba1cResults = new Collection,
        Collection $medications = new Collection,
    ): array {
        $insights = ReadingInsights::make($profile, $bpReadings, $glucoseReadings);
        $entries = collect(ReadingTimeline::merge($profile, $bpReadings, $glucoseReadings))
            ->groupBy('date_key');
        $unit = $profile->glucose_unit->label();
        $weightUnit = $profile->weight_unit->label();
        $days = [];
        $endLocal = $toLocal->copy()->startOfDay();

        for (
            $cursor = $fromLocal->copy()->startOfDay();
            $cursor->lessThanOrEqualTo($endLocal);
            $cursor = $cursor->copy()->addDay()
        ) {
            $date = $cursor->toDateString();
            $dayEntries = collect($entries->get($date, []));
            $bp = $dayEntries->where('type', 'bp');
            $slots = [];

            foreach (BpDaySlot::dayOrder() as $slot) {
                $reading = $bp->firstWhere('slot', $slot->value);
                $slots[$slot->value] = $reading === null ? null : [
                    'title' => $reading['title'],
                    'time_label' => $reading['time_label'],
                    'range_label' => $reading['range_label'],
                    'note' => $reading['note'],
                ];
            }

            $days[] = [
                'date' => $date,
                'date_label' => $dayEntries->first()['date_label'] ?? ReadingTimeline::dateLabel($profile, $cursor),
                'bp_filled' => collect($slots)->filter()->count(),
                'slots' => $slots,
                'sugar' => $dayEntries->where('type', 'glucose')->values()->map(fn (array $item): array => [
                    'title' => $item['title'],
                    'time_label' => $item['time_label'],
                    'context_label' => $item['context_label'],
                    'range_label' => $item['range_label'],
                    'note' => $item['note'],
                ])->all(),
            ];
        }

        $sugarByContext = collect(GlucoseContext::cases())
            ->map(function (GlucoseContext $context) use ($glucoseReadings, $profile, $unit): array {
                $inContext = $glucoseReadings->filter(
                    fn (GlucoseReading $reading): bool => $reading->context === $context
                );
                $average = $inContext->isEmpty()
                    ? null
                    : Glucose::fromMgDl((string) round((float) $inContext->avg('value_mg_dl'), 2), $profile->glucose_unit);

                return [
                    'key' => $context->value,
                    'label' => $context->label(),
                    'count' => $inContext->count(),
                    'average' => $average === null ? null : $average.' '.$unit,
                ];
            })
            ->filter(fn (array $row): bool => $row['count'] > 0)
            ->values()
            ->all();

        return [
            'profile' => [
                'name' => $profile->name,
                'timezone' => $profile->timezone,
                'glucose_unit' => $unit,
                'weight_unit' => $weightUnit,
            ],
            'range' => [
                'from' => $fromLocal->toDateString(),
                'to' => $toLocal->toDateString(),
                'label' => HistoryDateRange::rangeLabel($fromLocal, $toLocal),
            ],
            'insights' => $insights,
            'days' => $days,
            'sugar_by_context' => $sugarByContext,
            'weights' => $weights->map(fn (Weight $weight): array => BodyCheckIn::weightPayload(
                $profile,
                $weight,
                $weight->measured_at->timezone($profile->timezone)->format('j M Y'),
            ))->values()->all(),
            'hba1c' => $hba1cResults->map(fn (Hba1cResult $result): array => [
                'id' => $result->id,
                'value' => (string) $result->value,
                'tested_at' => $result->tested_at->format('j M Y'),
                'lab_name' => $result->lab_name,
            ])->values()->all(),
            'medications' => $medications->map(fn (Medication $medication): array => [
                'id' => $medication->id,
                'name' => $medication->name,
                'dose' => $medication->dose,
                'schedule' => $medication->schedule,
                'is_active' => $medication->is_active,
            ])->values()->all(),
            'clinic_note' => ClinicianNote::current($profile),
            'disclaimer' => 'This page is a record of what was logged. It is not a medical device and does not replace a doctor. If a reading is 180/120 or higher, or if you feel unwell, seek immediate medical attention.',
        ];
    }
}
