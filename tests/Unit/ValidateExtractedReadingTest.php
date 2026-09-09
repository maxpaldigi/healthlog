<?php

use App\Enums\GlucoseUnit;
use App\Models\Profile;
use App\Support\ExtractedReading;
use App\Support\ValidateExtractedReading;

function extracted(array $overrides = []): ExtractedReading
{
    return ExtractedReading::fromArray([
        'device_type' => 'blood_pressure_monitor',
        'systolic' => 128,
        'diastolic' => 82,
        'pulse' => 70,
        'glucose' => null,
        'glucose_unit_on_screen' => null,
        'confidence' => 'high',
        'notes' => 'Clear LCD',
        ...$overrides,
    ]);
}

function profileWithUnit(GlucoseUnit $unit = GlucoseUnit::MgDl): Profile
{
    return new Profile([
        'glucose_unit' => $unit,
    ]);
}

it('accepts a clear blood pressure reading', function () {
    $decision = ValidateExtractedReading::handle(extracted(), profileWithUnit());

    expect($decision['route'])->toBe('bp.create')
        ->and($decision['extraction']['systolic'])->toBe(128)
        ->and($decision['extraction']['source'])->toBe('photo');
});

it('rejects unknown or low-confidence extractions', function (string $deviceType, string $confidence) {
    $decision = ValidateExtractedReading::handle(extracted([
        'device_type' => $deviceType,
        'confidence' => $confidence,
    ]), profileWithUnit());

    expect($decision['route'])->toBe('scan')
        ->and($decision['extraction'])->toBeNull();
})->with([
    ['unknown', 'high'],
    ['blood_pressure_monitor', 'low'],
]);

it('rejects impossible blood pressure values', function () {
    $decision = ValidateExtractedReading::handle(extracted([
        'systolic' => 80,
        'diastolic' => 90,
    ]), profileWithUnit());

    expect($decision['route'])->toBe('scan');
});

it('converts glucose when the screen unit disagrees with the profile', function () {
    $decision = ValidateExtractedReading::handle(extracted([
        'device_type' => 'glucometer',
        'systolic' => null,
        'diastolic' => null,
        'pulse' => null,
        'glucose' => 6.2,
        'glucose_unit_on_screen' => 'mmol/L',
        'confidence' => 'medium',
    ]), profileWithUnit());

    expect($decision['route'])->toBe('glucose.create')
        ->and($decision['extraction']['original'])->toBe('6.2')
        ->and($decision['extraction']['original_unit'])->toBe('mmol/L')
        ->and($decision['extraction']['note'])->toContain('6.2 mmol/L');
});

it('rejects glucose outside the safe range after conversion', function () {
    $decision = ValidateExtractedReading::handle(extracted([
        'device_type' => 'glucometer',
        'glucose' => 10,
        'glucose_unit_on_screen' => 'mg/dL',
    ]), profileWithUnit());

    expect($decision['route'])->toBe('scan');
});
