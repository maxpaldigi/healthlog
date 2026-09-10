<?php

namespace App\Support;

use App\Enums\GlucoseUnit;
use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Models\Profile;

final class ReadingSpeech
{
    public static function bloodPressure(BpReading $reading): string
    {
        $parts = [sprintf('Blood pressure %d over %d', $reading->systolic, $reading->diastolic)];

        if ($reading->pulse !== null) {
            $parts[] = sprintf('Pulse %d', $reading->pulse);
        }

        $flag = BloodPressureColor::label($reading->systolic, $reading->diastolic);

        if ($flag !== null) {
            $parts[] = $flag;
        }

        return self::join($parts);
    }

    public static function glucose(Profile $profile, GlucoseReading $reading): string
    {
        $display = self::spokenNumber(Glucose::fromMgDl((string) $reading->value_mg_dl, $profile->glucose_unit));
        $unit = $profile->glucose_unit === GlucoseUnit::MgDl
            ? 'milligrams per deciliter'
            : 'millimoles per litre';

        $parts = [sprintf('Sugar %s %s', $display, $unit), $reading->context->label()];

        $flag = GlucoseRange::label($profile, (string) $reading->value_mg_dl);

        if ($flag !== null) {
            $parts[] = $flag;
        }

        return self::join($parts);
    }

    /**
     * @param  array<int, array{key: string, label: string, filled: bool}>  $slots
     */
    public static function home(int $count, int $target, array $slots): string
    {
        if ($count >= $target) {
            $parts = [sprintf('%d blood pressure readings logged today', $count)];
        } else {
            $parts = [sprintf('%d of %d blood pressure readings today', $count, $target)];
        }

        foreach ($slots as $slot) {
            $parts[] = $slot['filled']
                ? $slot['label'].' logged'
                : $slot['label'].' not yet';
        }

        return self::join($parts);
    }

    /**
     * @param  list<string>  $parts
     */
    private static function join(array $parts): string
    {
        return implode('. ', $parts).'.';
    }

    private static function spokenNumber(string $value): string
    {
        if (! str_contains($value, '.')) {
            return $value;
        }

        return rtrim(rtrim($value, '0'), '.');
    }
}
