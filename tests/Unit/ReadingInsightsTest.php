<?php

use App\Enums\GlucoseContext;
use App\Enums\GlucoseUnit;
use App\Enums\TargetSetBy;
use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Models\Profile;
use App\Support\ReadingInsights;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

function insightsFor(Profile $profile, Collection $bp, Collection $glucose): array
{
    return ReadingInsights::make($profile, $bp, $glucose);
}

it('hides systolic variability until there are two blood pressure readings', function () {
    $profile = userWithProfile([], ['timezone' => 'UTC'])->defaultProfile;
    $bp = collect([
        BpReading::factory()->for($profile)->make([
            'systolic' => 120,
            'diastolic' => 80,
            'measured_at' => Carbon::parse('2026-01-01 08:00:00', 'UTC'),
        ]),
    ]);

    expect(insightsFor($profile, $bp, collect())['stats']['bp_variability'])->toBeNull();
});

it('computes systolic standard deviation and coefficient of variation', function () {
    $profile = userWithProfile([], ['timezone' => 'UTC'])->defaultProfile;
    $times = [
        '2026-01-01 08:00:00',
        '2026-01-01 14:00:00',
        '2026-01-01 20:00:00',
        '2026-01-02 08:00:00',
    ];
    $systolic = [120, 130, 128, 122];
    $bp = collect($systolic)->map(function (int $value, int $index) use ($profile, $times): BpReading {
        return BpReading::factory()->for($profile)->make([
            'systolic' => $value,
            'diastolic' => 80,
            'measured_at' => Carbon::parse($times[$index], 'UTC'),
        ]);
    });

    $row = insightsFor($profile, $bp, collect())['stats']['bp_variability'];

    expect($row['sd_systolic'])->toBe('4.8')
        ->and($row['cv_percent'])->toBe('3.8')
        ->and($row['line'])->toBe('Systolic standard deviation was 4.8 mmHg (CV 3.8%).');
});

it('subtracts evening systolic from morning systolic', function () {
    $profile = userWithProfile([], ['timezone' => 'UTC'])->defaultProfile;
    $bp = collect([
        BpReading::factory()->for($profile)->make([
            'systolic' => 120,
            'diastolic' => 80,
            'measured_at' => Carbon::parse('2026-01-01 08:00:00', 'UTC'),
        ]),
        BpReading::factory()->for($profile)->make([
            'systolic' => 122,
            'diastolic' => 78,
            'measured_at' => Carbon::parse('2026-01-02 08:00:00', 'UTC'),
        ]),
        BpReading::factory()->for($profile)->make([
            'systolic' => 128,
            'diastolic' => 82,
            'measured_at' => Carbon::parse('2026-01-01 20:00:00', 'UTC'),
        ]),
    ]);

    $row = insightsFor($profile, $bp, collect())['stats']['morning_minus_evening'];

    expect($row['morning_systolic'])->toBe(121)
        ->and($row['evening_systolic'])->toBe(128)
        ->and($row['difference'])->toBe(-7)
        ->and($row['line'])->toBe('Morning systolic averaged 7 mmHg below evening.');
});

it('hides morning minus evening when a slot is missing', function () {
    $profile = userWithProfile([], ['timezone' => 'UTC'])->defaultProfile;
    $bp = collect([
        BpReading::factory()->for($profile)->make([
            'systolic' => 120,
            'diastolic' => 80,
            'measured_at' => Carbon::parse('2026-01-01 08:00:00', 'UTC'),
        ]),
    ]);

    expect(insightsFor($profile, $bp, collect())['stats']['morning_minus_evening'])->toBeNull();
});

it('counts sugar readings inside the persons own targets', function () {
    $profile = userWithProfile([], [
        'timezone' => 'UTC',
        'glucose_low_mg_dl' => '70.00',
        'glucose_high_mg_dl' => '180.00',
    ])->defaultProfile;

    $glucose = collect([
        GlucoseReading::factory()->for($profile)->make([
            'value_mg_dl' => '100.00',
            'context' => GlucoseContext::Fasting,
            'measured_at' => Carbon::parse('2026-01-01 07:00:00', 'UTC'),
        ]),
        GlucoseReading::factory()->for($profile)->make([
            'value_mg_dl' => '210.00',
            'context' => GlucoseContext::PostMeal2h,
            'measured_at' => Carbon::parse('2026-01-01 14:00:00', 'UTC'),
        ]),
        GlucoseReading::factory()->for($profile)->make([
            'value_mg_dl' => '140.00',
            'context' => GlucoseContext::Random,
            'measured_at' => Carbon::parse('2026-01-01 18:00:00', 'UTC'),
        ]),
        GlucoseReading::factory()->for($profile)->make([
            'value_mg_dl' => '60.00',
            'context' => GlucoseContext::Bedtime,
            'measured_at' => Carbon::parse('2026-01-01 22:00:00', 'UTC'),
        ]),
    ]);

    $row = insightsFor($profile, collect(), $glucose)['stats']['time_in_range'];

    expect($row['in_range'])->toBe(2)
        ->and($row['total'])->toBe(4)
        ->and($row['percent'])->toBe(50)
        ->and($row['line'])->toBe('2 of 4 sugar readings were inside your targets (50%).');
});

it('names clinic targets when the active numbers came from a clinic reply', function () {
    $profile = userWithProfile([], [
        'timezone' => 'UTC',
        'glucose_low_mg_dl' => '70.00',
        'glucose_high_mg_dl' => '180.00',
        'glucose_target_set_by' => TargetSetBy::Clinician,
    ])->defaultProfile;

    $glucose = collect([
        GlucoseReading::factory()->for($profile)->make([
            'value_mg_dl' => '100.00',
            'context' => GlucoseContext::Fasting,
            'measured_at' => Carbon::parse('2026-01-01 07:00:00', 'UTC'),
        ]),
        GlucoseReading::factory()->for($profile)->make([
            'value_mg_dl' => '210.00',
            'context' => GlucoseContext::PostMeal2h,
            'measured_at' => Carbon::parse('2026-01-01 14:00:00', 'UTC'),
        ]),
    ]);

    $row = insightsFor($profile, collect(), $glucose)['stats']['time_in_range'];

    expect($row['line'])->toBe('1 of 2 sugar readings were inside the clinic targets (50%).');
});

it('does not compute time in range when no targets are set', function () {
    $profile = userWithProfile([], ['timezone' => 'UTC'])->defaultProfile;
    $glucose = collect([
        GlucoseReading::factory()->for($profile)->make([
            'value_mg_dl' => '210.00',
            'context' => GlucoseContext::Random,
            'measured_at' => Carbon::parse('2026-01-01 12:00:00', 'UTC'),
        ]),
    ]);

    expect(insightsFor($profile, collect(), $glucose)['stats']['time_in_range'])->toBeNull();
});

it('subtracts mean fasting sugar from mean 2-hour after-meal sugar', function () {
    $profile = userWithProfile([], ['timezone' => 'UTC'])->defaultProfile;
    $glucose = collect([
        GlucoseReading::factory()->for($profile)->make([
            'value_mg_dl' => '100.00',
            'context' => GlucoseContext::Fasting,
            'measured_at' => Carbon::parse('2026-01-01 07:00:00', 'UTC'),
        ]),
        GlucoseReading::factory()->for($profile)->make([
            'value_mg_dl' => '110.00',
            'context' => GlucoseContext::Fasting,
            'measured_at' => Carbon::parse('2026-01-02 07:00:00', 'UTC'),
        ]),
        GlucoseReading::factory()->for($profile)->make([
            'value_mg_dl' => '140.00',
            'context' => GlucoseContext::PostMeal2h,
            'measured_at' => Carbon::parse('2026-01-01 14:00:00', 'UTC'),
        ]),
        GlucoseReading::factory()->for($profile)->make([
            'value_mg_dl' => '150.00',
            'context' => GlucoseContext::PostMeal2h,
            'measured_at' => Carbon::parse('2026-01-02 14:00:00', 'UTC'),
        ]),
    ]);

    $row = insightsFor($profile, collect(), $glucose)['stats']['post_meal_minus_fasting'];

    expect($row['line'])->toBe('Mean 2-hour after-meal sugar was 40 mg/dL above mean fasting.');
});

it('shows after-meal minus fasting in mmol/L when that is the profile unit', function () {
    $profile = userWithProfile([], [
        'timezone' => 'UTC',
        'glucose_unit' => GlucoseUnit::MmolL,
    ])->defaultProfile;

    $glucose = collect([
        GlucoseReading::factory()->for($profile)->make([
            'value_mg_dl' => '90.00',
            'context' => GlucoseContext::Fasting,
            'measured_at' => Carbon::parse('2026-01-01 07:00:00', 'UTC'),
        ]),
        GlucoseReading::factory()->for($profile)->make([
            'value_mg_dl' => '180.18',
            'context' => GlucoseContext::PostMeal2h,
            'measured_at' => Carbon::parse('2026-01-01 14:00:00', 'UTC'),
        ]),
    ]);

    $row = insightsFor($profile, collect(), $glucose)['stats']['post_meal_minus_fasting'];

    expect($row['unit'])->toBe('mmol/L')
        ->and($row['line'])->toContain('mmol/L above mean fasting.');
});

it('appends the new figures to the summary without interpreting them', function () {
    $profile = userWithProfile([], [
        'timezone' => 'UTC',
        'glucose_low_mg_dl' => '70.00',
        'glucose_high_mg_dl' => '180.00',
    ])->defaultProfile;

    $bp = collect([
        BpReading::factory()->for($profile)->make([
            'systolic' => 120,
            'diastolic' => 80,
            'measured_at' => Carbon::parse('2026-01-01 08:00:00', 'UTC'),
        ]),
        BpReading::factory()->for($profile)->make([
            'systolic' => 128,
            'diastolic' => 82,
            'measured_at' => Carbon::parse('2026-01-01 20:00:00', 'UTC'),
        ]),
    ]);
    $glucose = collect([
        GlucoseReading::factory()->for($profile)->make([
            'value_mg_dl' => '100.00',
            'context' => GlucoseContext::Fasting,
            'measured_at' => Carbon::parse('2026-01-01 07:00:00', 'UTC'),
        ]),
        GlucoseReading::factory()->for($profile)->make([
            'value_mg_dl' => '140.00',
            'context' => GlucoseContext::PostMeal2h,
            'measured_at' => Carbon::parse('2026-01-01 14:00:00', 'UTC'),
        ]),
    ]);

    $text = insightsFor($profile, $bp, $glucose)['summary_text'];

    expect($text)->toContain('Systolic standard deviation')
        ->and($text)->toContain('Morning systolic averaged')
        ->and($text)->toContain('inside your targets')
        ->and($text)->toContain('after-meal sugar')
        ->and(strtolower($text))->not->toContain('diagnos')
        ->and(strtolower($text))->not->toContain('treat')
        ->and(strtolower($text))->not->toContain('surge');
});
