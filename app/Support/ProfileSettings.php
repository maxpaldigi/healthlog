<?php

namespace App\Support;

use App\Enums\GlucoseUnit;
use App\Enums\WeightUnit;
use App\Models\Profile;

final class ProfileSettings
{
    /**
     * @return array<string, mixed>
     */
    public static function payload(Profile $profile): array
    {
        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'timezone' => $profile->timezone,
            'glucose_unit' => $profile->glucose_unit->value,
            'glucose_unit_label' => $profile->glucose_unit->label(),
            'weight_unit' => $profile->weight_unit->value,
            'weight_unit_label' => $profile->weight_unit->label(),
            'height_cm' => $profile->height_cm === null ? '' : (string) $profile->height_cm,
            'is_default' => $profile->is_default,
            'glucose_low' => $profile->glucose_low_mg_dl === null
                ? ''
                : Glucose::fromMgDl((string) $profile->glucose_low_mg_dl, $profile->glucose_unit),
            'glucose_high' => $profile->glucose_high_mg_dl === null
                ? ''
                : Glucose::fromMgDl((string) $profile->glucose_high_mg_dl, $profile->glucose_unit),
            'glucose_target_set_by' => $profile->glucose_target_set_by->value,
            'reminders_enabled' => $profile->reminders_enabled,
            'reminder_morning' => $profile->reminder_morning ?: '08:00',
            'reminder_afternoon' => $profile->reminder_afternoon ?: '14:00',
            'reminder_evening' => $profile->reminder_evening ?: '20:00',
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function glucoseUnits(): array
    {
        return array_map(
            fn (GlucoseUnit $unit): array => ['value' => $unit->value, 'label' => $unit->label()],
            GlucoseUnit::cases(),
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function weightUnits(): array
    {
        return array_map(
            fn (WeightUnit $unit): array => ['value' => $unit->value, 'label' => $unit->label()],
            WeightUnit::cases(),
        );
    }
}
