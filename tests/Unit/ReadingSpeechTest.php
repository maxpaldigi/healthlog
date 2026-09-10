<?php

use App\Enums\GlucoseContext;
use App\Enums\GlucoseUnit;
use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Models\Profile;
use App\Support\ReadingSpeech;

it('speaks blood pressure numbers without interpreting them', function () {
    $usual = new BpReading(['systolic' => 118, 'diastolic' => 76, 'pulse' => 68]);
    $raised = new BpReading(['systolic' => 132, 'diastolic' => 84, 'pulse' => 70]);
    $clinic = new BpReading(['systolic' => 184, 'diastolic' => 122]);

    expect(ReadingSpeech::bloodPressure($usual))->toBe('Blood pressure 118 over 76. Pulse 68.')
        ->and(ReadingSpeech::bloodPressure($raised))->toBe('Blood pressure 132 over 84. Pulse 70. Raised.')
        ->and(ReadingSpeech::bloodPressure($clinic))->toBe('Blood pressure 184 over 122. Check with your clinic.');
});

it('speaks sugar in the profile unit', function () {
    $mg = new Profile(['glucose_unit' => GlucoseUnit::MgDl]);
    $mmol = new Profile(['glucose_unit' => GlucoseUnit::MmolL]);
    $reading = new GlucoseReading([
        'value_mg_dl' => '110.00',
        'context' => GlucoseContext::Fasting,
    ]);

    expect(ReadingSpeech::glucose($mg, $reading))->toBe('Sugar 110 milligrams per deciliter. Fasting.')
        ->and(ReadingSpeech::glucose($mmol, $reading))->toStartWith('Sugar ')
        ->and(ReadingSpeech::glucose($mmol, $reading))->toContain('millimoles per litre')
        ->and(ReadingSpeech::glucose($mmol, $reading))->toContain('Fasting');
});

it('speaks the home summary from slot completion', function () {
    $empty = ReadingSpeech::home(0, 3, [
        ['key' => 'morning', 'label' => 'Morning', 'filled' => false],
        ['key' => 'afternoon', 'label' => 'Afternoon', 'filled' => false],
        ['key' => 'evening', 'label' => 'Evening', 'filled' => false],
    ]);

    $done = ReadingSpeech::home(3, 3, [
        ['key' => 'morning', 'label' => 'Morning', 'filled' => true],
        ['key' => 'afternoon', 'label' => 'Afternoon', 'filled' => true],
        ['key' => 'evening', 'label' => 'Evening', 'filled' => true],
    ]);

    expect($empty)->toBe('0 of 3 blood pressure readings today. Morning not yet. Afternoon not yet. Evening not yet.')
        ->and($done)->toBe('3 blood pressure readings logged today. Morning logged. Afternoon logged. Evening logged.');
});
