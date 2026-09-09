<?php

namespace App\Support;

use App\Enums\GlucoseUnit;
use App\Enums\ReadingSource;
use App\Models\Profile;

final class ValidateExtractedReading
{
    /**
     * @return array{
     *     route: string,
     *     message: string,
     *     extraction: array<string, mixed>|null
     * }
     */
    public static function handle(ExtractedReading $reading, Profile $profile): array
    {
        if ($reading->deviceType === 'unknown' || $reading->confidence === 'low') {
            return self::retake();
        }

        if ($reading->deviceType === 'blood_pressure_monitor') {
            return self::bloodPressure($reading);
        }

        return self::glucose($reading, $profile);
    }

    /**
     * @return array{route: string, message: string, extraction: array<string, mixed>|null}
     */
    private static function bloodPressure(ExtractedReading $reading): array
    {
        $systolic = $reading->systolic;
        $diastolic = $reading->diastolic;

        if ($systolic === null || $diastolic === null) {
            return self::retake();
        }

        if ($systolic < 60 || $systolic > 300 || $diastolic < 30 || $diastolic > 200 || $systolic <= $diastolic) {
            return self::retake();
        }

        $pulse = $reading->pulse;
        if ($pulse !== null && ($pulse < 30 || $pulse > 220)) {
            $pulse = null;
        }

        return [
            'route' => 'bp.create',
            'message' => 'Check these numbers, then Save.',
            'extraction' => [
                'source' => ReadingSource::Photo->value,
                'systolic' => $systolic,
                'diastolic' => $diastolic,
                'pulse' => $pulse,
                'note' => $reading->notes !== '' ? $reading->notes : null,
            ],
        ];
    }

    /**
     * @return array{route: string, message: string, extraction: array<string, mixed>|null}
     */
    private static function glucose(ExtractedReading $reading, Profile $profile): array
    {
        if ($reading->glucose === null) {
            return self::retake();
        }

        $screenUnit = self::screenUnit($reading->glucoseUnitOnScreen);
        $storageUnit = $screenUnit ?? $profile->glucose_unit;
        $mgDl = Glucose::toMgDl($reading->glucose, $storageUnit);

        if (! Glucose::isWithinStoredRange($mgDl)) {
            return self::retake();
        }

        $display = Glucose::fromMgDl($mgDl, $profile->glucose_unit);
        $converted = $screenUnit !== null && $screenUnit !== $profile->glucose_unit;

        return [
            'route' => 'glucose.create',
            'message' => 'Check these numbers, then Save.',
            'extraction' => [
                'source' => ReadingSource::Photo->value,
                'value' => $display,
                'note' => $converted
                    ? "Photo showed {$reading->glucose} {$reading->glucoseUnitOnScreen}. Converted to {$display} {$profile->glucose_unit->label()}."
                    : ($reading->notes !== '' ? $reading->notes : null),
                'original' => $converted ? $reading->glucose : null,
                'original_unit' => $converted ? $reading->glucoseUnitOnScreen : null,
            ],
        ];
    }

    /**
     * @return array{route: string, message: string, extraction: null}
     */
    private static function retake(): array
    {
        return [
            'route' => 'scan',
            'message' => "Couldn't read that photo clearly. Please retake it or enter the numbers manually.",
            'extraction' => null,
        ];
    }

    private static function screenUnit(?string $unit): ?GlucoseUnit
    {
        return match ($unit) {
            'mg/dL' => GlucoseUnit::MgDl,
            'mmol/L' => GlucoseUnit::MmolL,
            default => null,
        };
    }
}
