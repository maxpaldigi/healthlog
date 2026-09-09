<?php

namespace App\Support;

final class BloodPressureColor
{
    /**
     * Colour token for a reading against the published AHA category chart.
     * Used for display only — never as a diagnosis.
     */
    public static function for(int $systolic, int $diastolic): string
    {
        if ($systolic >= 180 || $diastolic >= 120) {
            return 'crisis';
        }

        if ($systolic >= 140 || $diastolic >= 90) {
            return 'high';
        }

        if ($systolic >= 130 || $diastolic >= 80) {
            return 'raised';
        }

        if ($systolic >= 120 && $diastolic < 80) {
            return 'elevated';
        }

        return 'normal';
    }

    /**
     * Plain-language flag for seniors. Null when the reading is in the usual range.
     * Display only — never a diagnosis.
     */
    public static function label(int $systolic, int $diastolic): ?string
    {
        return match (self::for($systolic, $diastolic)) {
            'crisis' => 'Check with your clinic',
            'high' => 'High',
            'raised', 'elevated' => 'Raised',
            default => null,
        };
    }
}
