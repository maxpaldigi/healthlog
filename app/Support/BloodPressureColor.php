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

    /**
     * Display tokens for the colour band. Keep hex values in sync with
     * `--bp-*` in resources/css/app.css. Foreground on background must
     * meet WCAG AA (4.5:1). Display only — never a diagnosis.
     *
     * @return list<array{band: string, scheme: 'light'|'dark', background: string, foreground: string}>
     */
    public static function tokens(): array
    {
        return [
            ['band' => 'crisis', 'scheme' => 'light', 'background' => '#b91c1c', 'foreground' => '#ffffff'],
            ['band' => 'high', 'scheme' => 'light', 'background' => '#c2410c', 'foreground' => '#ffffff'],
            ['band' => 'raised', 'scheme' => 'light', 'background' => '#b45309', 'foreground' => '#ffffff'],
            ['band' => 'elevated', 'scheme' => 'light', 'background' => '#a16207', 'foreground' => '#ffffff'],
            ['band' => 'normal', 'scheme' => 'light', 'background' => '#047857', 'foreground' => '#ffffff'],
            ['band' => 'crisis', 'scheme' => 'dark', 'background' => '#f87171', 'foreground' => '#1c1917'],
            ['band' => 'high', 'scheme' => 'dark', 'background' => '#fb923c', 'foreground' => '#1c1917'],
            ['band' => 'raised', 'scheme' => 'dark', 'background' => '#fbbf24', 'foreground' => '#1c1917'],
            ['band' => 'elevated', 'scheme' => 'dark', 'background' => '#fde047', 'foreground' => '#1c1917'],
            ['band' => 'normal', 'scheme' => 'dark', 'background' => '#34d399', 'foreground' => '#1c1917'],
        ];
    }
}
