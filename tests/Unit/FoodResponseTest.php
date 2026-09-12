<?php

use App\Enums\GlucoseContext;
use App\Enums\GlucoseUnit;
use App\Enums\TargetSetBy;
use App\Models\BpReading;
use App\Models\FoodLog;
use App\Models\GlucoseReading;
use App\Support\FoodResponse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('hides a food until it has five 2-hour sugar readings', function () {
    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    seedLinkedMeals($profile, 'Rice', ['210.00', '210.00', '210.00', '210.00']);

    expect(FoodResponse::afterMeals($profile))->toBe([]);
});

it('compares rice and roti with means and sample counts', function () {
    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    seedLinkedMeals($profile, 'Rice', array_fill(0, 7, '210.00'));
    seedLinkedMeals($profile, 'Roti', array_fill(0, 9, '155.00'));

    $rows = FoodResponse::afterMeals($profile);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['line'])->toBe('After Rice: 210 mg/dL avg (7 readings).')
        ->and($rows[1]['line'])->toBe('After Roti: 155 mg/dL avg (9 readings).');
});

it('groups the same barcode and the same name ignoring case', function () {
    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    seedLinkedMeals($profile, 'Basmati Rice', array_fill(0, 3, '200.00'), '890123');
    seedLinkedMeals($profile, 'Rice', array_fill(0, 2, '220.00'), '890123');
    seedLinkedMeals($profile, 'roti', ['150.00', '160.00']);
    seedLinkedMeals($profile, 'Roti', ['140.00', '150.00', '160.00']);

    $rows = FoodResponse::afterMeals($profile);

    expect($rows)->toHaveCount(2)
        ->and($rows[0]['label'])->toBe('Basmati Rice')
        ->and($rows[0]['sample_count'])->toBe(5)
        ->and($rows[0]['average'])->toBe('208')
        ->and($rows[1]['label'])->toBe('Roti')
        ->and($rows[1]['sample_count'])->toBe(5);
});

it('shows sugar averages in the profile unit', function () {
    $user = userWithProfile([], ['timezone' => 'UTC', 'glucose_unit' => GlucoseUnit::MmolL]);
    $profile = $user->defaultProfile;

    seedLinkedMeals($profile, 'Rice', array_fill(0, 5, '180.18'));

    $rows = FoodResponse::afterMeals($profile);

    expect($rows[0]['unit'])->toBe('mmol/L')
        ->and($rows[0]['line'])->toContain('mmol/L avg (5 readings).');
});

it('ignores linked readings that are not 2-hour post-meal', function () {
    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    seedLinkedMeals($profile, 'Rice', array_fill(0, 4, '210.00'));

    $reading = GlucoseReading::factory()->for($profile)->create([
        'context' => GlucoseContext::PostMeal1h,
        'value_mg_dl' => '300.00',
        'measured_at' => Carbon::parse('2026-08-20 14:00:00', 'UTC'),
    ]);
    FoodLog::factory()->for($profile)->create([
        'label' => 'Rice',
        'eaten_at' => Carbon::parse('2026-08-20 13:00:00', 'UTC'),
        'glucose_reading_id' => $reading->id,
    ]);

    expect(FoodResponse::afterMeals($profile))->toBe([]);
});

it('hides morning blood pressure until five next-morning pairings', function () {
    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    foreach (range(0, 3) as $index) {
        $evening = Carbon::parse('2026-08-01 20:00:00', 'UTC')->addDays($index);
        FoodLog::factory()->for($profile)->create([
            'label' => 'Roti',
            'eaten_at' => $evening,
        ]);
        BpReading::factory()->for($profile)->create([
            'systolic' => 132,
            'diastolic' => 80,
            'measured_at' => $evening->copy()->addHours(12),
        ]);
    }

    expect(FoodResponse::morningBloodPressure($profile))->toBe([]);
});

it('averages next-morning systolic for foods eaten the evening before', function () {
    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;
    $systolic = [130, 132, 134, 128, 136, 132];

    foreach ($systolic as $index => $value) {
        $evening = Carbon::parse('2026-08-01 20:00:00', 'UTC')->addDays($index);
        FoodLog::factory()->for($profile)->create([
            'label' => 'Roti',
            'eaten_at' => $evening,
        ]);
        BpReading::factory()->for($profile)->create([
            'systolic' => $value,
            'diastolic' => 80,
            'measured_at' => $evening->copy()->addHours(12),
        ]);
    }

    FoodLog::factory()->for($profile)->create([
        'label' => 'Roti',
        'eaten_at' => Carbon::parse('2026-08-01 12:00:00', 'UTC'),
    ]);

    $rows = FoodResponse::morningBloodPressure($profile);

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['line'])->toBe('After Roti: morning 132 avg (6 readings).');
});

it('stays descriptive and never tells the person to avoid a food', function () {
    $payload = FoodResponse::make(userWithProfile([], ['timezone' => 'UTC'])->defaultProfile);

    expect($payload['intro'])->toBe('Your numbers after these foods. Check with your clinic.')
        ->and($payload['hint'])->toContain('2-hour sugar reading')
        ->and(strtolower($payload['intro'].$payload['hint']))->not->toContain('avoid')
        ->and(strtolower($payload['intro'].$payload['hint']))->not->toContain('diagnos')
        ->and(strtolower($payload['intro'].$payload['hint']))->not->toContain('treat');
});

it('hides a photo history average until five 2-hour sugar readings', function () {
    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    seedLinkedMeals($profile, 'Rice', ['210.00', '210.00', '210.00', '210.00']);

    $history = FoodResponse::forFood($profile, 'Rice');

    expect($history['after_meal'])->toBeNull()
        ->and($history['hint'])->toBe(FoodResponse::PHOTO_HINT)
        ->and(strtolower($history['hint'] ?? ''))->not->toContain('should')
        ->and(strtolower($history['hint'] ?? ''))->not->toContain('avoid')
        ->and(strtolower($history['hint'] ?? ''))->not->toContain('eat this');
});

it('shows this person\'s average after the named food once there are five readings', function () {
    $user = userWithProfile([], [
        'timezone' => 'UTC',
        'glucose_low_mg_dl' => '70.00',
        'glucose_high_mg_dl' => '180.00',
        'glucose_target_set_by' => TargetSetBy::Clinician,
    ]);
    $profile = $user->defaultProfile;

    seedLinkedMeals($profile, 'Rice', array_fill(0, 7, '210.00'));

    $history = FoodResponse::forFood($profile, 'rice');

    expect($history['after_meal'])->toBe('After Rice: 210 mg/dL avg (7 readings).')
        ->and($history['hint'])->toBeNull()
        ->and($history['clinic_targets'])->toBe('Clinic sugar range on this log: 70–180 mg/dL.');
});

it('matches photo history by barcode even when the printed name differs', function () {
    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    seedLinkedMeals($profile, 'Basmati Rice', array_fill(0, 5, '200.00'), '890123');

    $history = FoodResponse::forFood($profile, 'Rice', '890123');

    expect($history['after_meal'])->toBe('After Basmati Rice: 200 mg/dL avg (5 readings).');
});
