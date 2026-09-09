<?php

namespace App\Support;

final readonly class ExtractedReading
{
    /**
     * @param  'blood_pressure_monitor'|'glucometer'|'unknown'  $deviceType
     * @param  'high'|'medium'|'low'  $confidence
     * @param  'mg/dL'|'mmol/L'|null  $glucoseUnitOnScreen
     */
    public function __construct(
        public string $deviceType,
        public ?int $systolic,
        public ?int $diastolic,
        public ?int $pulse,
        public ?string $glucose,
        public ?string $glucoseUnitOnScreen,
        public string $confidence,
        public string $notes,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $deviceType = $data['device_type'] ?? 'unknown';
        $confidence = $data['confidence'] ?? 'low';
        $unit = $data['glucose_unit_on_screen'] ?? null;

        if (! in_array($deviceType, ['blood_pressure_monitor', 'glucometer', 'unknown'], true)) {
            $deviceType = 'unknown';
        }

        if (! in_array($confidence, ['high', 'medium', 'low'], true)) {
            $confidence = 'low';
        }

        if (! in_array($unit, ['mg/dL', 'mmol/L', null], true)) {
            $unit = null;
        }

        return new self(
            deviceType: $deviceType,
            systolic: self::nullableInt($data['systolic'] ?? null),
            diastolic: self::nullableInt($data['diastolic'] ?? null),
            pulse: self::nullableInt($data['pulse'] ?? null),
            glucose: self::nullableDecimal($data['glucose'] ?? null),
            glucoseUnitOnScreen: $unit,
            confidence: $confidence,
            notes: is_string($data['notes'] ?? null) ? $data['notes'] : '',
        );
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    private static function nullableDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return rtrim(rtrim(sprintf('%.4F', $value), '0'), '.') ?: '0';
        }

        if (is_string($value) && is_numeric($value)) {
            return $value;
        }

        return null;
    }
}
