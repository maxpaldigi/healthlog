<?php

use App\Enums\GlucoseContext;
use App\Enums\ReadingSource;
use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Models\Hba1cResult;
use App\Models\Profile;
use App\Models\User;
use App\Models\Weight;
use Illuminate\Database\QueryException;

function seedProfile(): Profile
{
    return Profile::factory()->for(User::factory())->create();
}

it('rejects blood pressure values outside the check constraints', function (array $attributes) {
    $profile = seedProfile();

    expect(fn () => BpReading::create([
        'profile_id' => $profile->id,
        'systolic' => 120,
        'diastolic' => 80,
        'pulse' => 70,
        'measured_at' => now(),
        'source' => ReadingSource::Manual,
        ...$attributes,
    ]))->toThrow(QueryException::class);
})->with([
    'systolic too low' => [['systolic' => 50]],
    'systolic too high' => [['systolic' => 310]],
    'diastolic too low' => [['diastolic' => 20]],
    'systolic not greater than diastolic' => [['systolic' => 80, 'diastolic' => 80]],
    'pulse too high' => [['pulse' => 230]],
]);

it('rejects glucose values outside the check constraints', function () {
    $profile = seedProfile();

    expect(fn () => GlucoseReading::create([
        'profile_id' => $profile->id,
        'value_mg_dl' => 10,
        'context' => GlucoseContext::Fasting,
        'measured_at' => now(),
        'source' => ReadingSource::Manual,
    ]))->toThrow(QueryException::class);

    expect(fn () => GlucoseReading::create([
        'profile_id' => $profile->id,
        'value_mg_dl' => 750,
        'context' => GlucoseContext::Fasting,
        'measured_at' => now(),
        'source' => ReadingSource::Manual,
    ]))->toThrow(QueryException::class);
});

it('rejects hba1c and weight values outside the check constraints', function () {
    $profile = seedProfile();

    expect(fn () => Hba1cResult::create([
        'profile_id' => $profile->id,
        'value' => 2.5,
        'tested_at' => now()->toDateString(),
    ]))->toThrow(QueryException::class);

    expect(fn () => Weight::create([
        'profile_id' => $profile->id,
        'kg' => 5,
        'measured_at' => now(),
    ]))->toThrow(QueryException::class);
});

it('scopes readings to a period', function () {
    $profile = seedProfile();

    $inside = BpReading::factory()->for($profile)->create([
        'measured_at' => now()->subDays(3),
    ]);

    BpReading::factory()->for($profile)->create([
        'measured_at' => now()->subDays(20),
    ]);

    $ids = BpReading::query()
        ->forPeriod(now()->subDays(7), now())
        ->pluck('id');

    expect($ids)->toContain($inside->id)->toHaveCount(1);
});
