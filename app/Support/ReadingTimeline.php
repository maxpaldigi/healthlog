<?php

namespace App\Support;

use App\Enums\BpDaySlot;
use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Models\Profile;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class ReadingTimeline
{
    /**
     * @param  Collection<int, BpReading>  $bpReadings
     * @param  Collection<int, GlucoseReading>  $glucoseReadings
     * @return list<array<string, mixed>>
     */
    public static function merge(Profile $profile, Collection $bpReadings, Collection $glucoseReadings): array
    {
        $items = $bpReadings->map(fn (BpReading $reading): array => self::bpItem($profile, $reading))
            ->concat($glucoseReadings->map(fn (GlucoseReading $reading): array => self::glucoseItem($profile, $reading)))
            ->sortByDesc('measured_at_utc')
            ->values();

        return $items->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function bpItem(Profile $profile, BpReading $reading): array
    {
        $local = $reading->measured_at->timezone($profile->clockTimezone());
        $slot = BpDaySlot::fromLocalTime($local);

        return [
            'id' => $reading->id,
            'type' => 'bp',
            'title' => $reading->systolic.' / '.$reading->diastolic,
            'subtitle' => self::bpSubtitle($reading),
            'context_label' => $reading->tag === null || $reading->tag->value === 'none' ? 'Blood pressure' : $reading->tag->label(),
            'color' => BloodPressureColor::for($reading->systolic, $reading->diastolic),
            'range_label' => BloodPressureColor::label($reading->systolic, $reading->diastolic),
            'note' => $reading->note,
            'measured_at' => $local->format('Y-m-d\TH:i'),
            'measured_at_label' => $local->format('j M, g:ia'),
            'measured_at_utc' => $reading->measured_at->toIso8601String(),
            'date_key' => $local->toDateString(),
            'date_label' => self::dateLabel($profile, $local),
            'time_label' => $local->format('g:ia'),
            'slot' => $slot->value,
            'slot_label' => $slot->label(),
            'edit_url' => route('bp.edit', $reading),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function glucoseItem(Profile $profile, GlucoseReading $reading): array
    {
        $local = $reading->measured_at->timezone($profile->clockTimezone());
        $display = Glucose::fromMgDl((string) $reading->value_mg_dl, $profile->glucose_unit);

        return [
            'id' => $reading->id,
            'type' => 'glucose',
            'title' => $display.' '.$profile->glucose_unit->label(),
            'subtitle' => $reading->context->label(),
            'context_label' => $reading->context->label(),
            'color' => 'glucose',
            'range_label' => GlucoseRange::label($profile, (string) $reading->value_mg_dl),
            'note' => $reading->note,
            'measured_at' => $local->format('Y-m-d\TH:i'),
            'measured_at_label' => $local->format('j M, g:ia'),
            'measured_at_utc' => $reading->measured_at->toIso8601String(),
            'date_key' => $local->toDateString(),
            'date_label' => self::dateLabel($profile, $local),
            'time_label' => $local->format('g:ia'),
            'slot' => null,
            'slot_label' => null,
            'edit_url' => route('glucose.edit', $reading),
        ];
    }

    public static function dateLabel(Profile $profile, CarbonInterface $local): string
    {
        $today = $profile->nowInTimezone()->startOfDay();
        $date = $local->copy()->timezone($profile->clockTimezone())->startOfDay();

        if ($date->isSameDay($today)) {
            return 'Today';
        }

        if ($date->isSameDay($today->copy()->subDay())) {
            return 'Yesterday';
        }

        return $date->year === $today->year
            ? $local->timezone($profile->clockTimezone())->format('j M')
            : $local->timezone($profile->clockTimezone())->format('j M Y');
    }

    private static function bpSubtitle(BpReading $reading): string
    {
        $parts = ['Blood pressure'];

        if ($reading->pulse !== null) {
            $parts[] = 'pulse '.$reading->pulse;
        }

        if ($reading->tag->value !== 'none') {
            $parts[] = $reading->tag->label();
        }

        return implode(' · ', $parts);
    }

    public static function localDateTimeValue(Profile $profile, ?CarbonInterface $at = null): string
    {
        return ($at ?? $profile->nowInTimezone())->timezone($profile->clockTimezone())->format('Y-m-d\TH:i');
    }
}
