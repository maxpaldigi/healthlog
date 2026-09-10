<?php

use App\Actions\Food\LinkFoodToGlucose;
use App\Actions\Food\StoreFoodLog;
use App\Actions\Readings\StoreGlucoseReading;
use App\Actions\Readings\UpdateGlucoseReading;
use App\Enums\FoodSource;
use App\Enums\GlucoseContext;
use App\Models\FoodLog;
use App\Models\GlucoseReading;
use Illuminate\Support\Carbon;

it('links food eaten in the two hours before a 2-hour sugar reading', function () {
    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    $food = FoodLog::factory()->for($profile)->create([
        'label' => 'Rice',
        'eaten_at' => Carbon::parse('2026-09-08 12:00:00', 'UTC'),
    ]);
    $reading = GlucoseReading::factory()->for($profile)->create([
        'context' => GlucoseContext::PostMeal2h,
        'value_mg_dl' => '210.00',
        'measured_at' => Carbon::parse('2026-09-08 14:00:00', 'UTC'),
    ]);

    app(LinkFoodToGlucose::class)->handle($profile);

    expect($food->fresh()->glucose_reading_id)->toBe($reading->id);
});

it('does not link food eaten at or after the sugar reading', function () {
    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    $food = FoodLog::factory()->for($profile)->create([
        'eaten_at' => Carbon::parse('2026-09-08 14:00:00', 'UTC'),
    ]);
    GlucoseReading::factory()->for($profile)->create([
        'context' => GlucoseContext::PostMeal2h,
        'measured_at' => Carbon::parse('2026-09-08 14:00:00', 'UTC'),
    ]);

    app(LinkFoodToGlucose::class)->handle($profile);

    expect($food->fresh()->glucose_reading_id)->toBeNull();
});

it('does not link food eaten more than two hours before the reading', function () {
    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    $food = FoodLog::factory()->for($profile)->create([
        'eaten_at' => Carbon::parse('2026-09-08 11:59:00', 'UTC'),
    ]);
    GlucoseReading::factory()->for($profile)->create([
        'context' => GlucoseContext::PostMeal2h,
        'measured_at' => Carbon::parse('2026-09-08 14:00:00', 'UTC'),
    ]);

    app(LinkFoodToGlucose::class)->handle($profile);

    expect($food->fresh()->glucose_reading_id)->toBeNull();
});

it('does not link fasting or 1-hour readings', function () {
    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    $food = FoodLog::factory()->for($profile)->create([
        'eaten_at' => Carbon::parse('2026-09-08 12:00:00', 'UTC'),
    ]);
    GlucoseReading::factory()->for($profile)->create([
        'context' => GlucoseContext::Fasting,
        'measured_at' => Carbon::parse('2026-09-08 14:00:00', 'UTC'),
    ]);
    GlucoseReading::factory()->for($profile)->create([
        'context' => GlucoseContext::PostMeal1h,
        'measured_at' => Carbon::parse('2026-09-08 13:00:00', 'UTC'),
    ]);

    app(LinkFoodToGlucose::class)->handle($profile);

    expect($food->fresh()->glucose_reading_id)->toBeNull();
});

it('gives the earlier 2-hour reading the food when two windows overlap', function () {
    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    $food = FoodLog::factory()->for($profile)->create([
        'eaten_at' => Carbon::parse('2026-09-08 12:00:00', 'UTC'),
    ]);
    $first = GlucoseReading::factory()->for($profile)->create([
        'context' => GlucoseContext::PostMeal2h,
        'measured_at' => Carbon::parse('2026-09-08 13:00:00', 'UTC'),
    ]);
    GlucoseReading::factory()->for($profile)->create([
        'context' => GlucoseContext::PostMeal2h,
        'measured_at' => Carbon::parse('2026-09-08 14:00:00', 'UTC'),
    ]);

    app(LinkFoodToGlucose::class)->handle($profile);

    expect($food->fresh()->glucose_reading_id)->toBe($first->id);
});

it('links from a saved food when a matching 2-hour reading already exists', function () {
    $this->travelTo(Carbon::parse('2026-09-08 18:00:00', 'UTC'));

    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    $reading = app(StoreGlucoseReading::class)->handle($profile, [
        'value' => '210',
        'context' => GlucoseContext::PostMeal2h->value,
        'measured_at' => '2026-09-08T14:00',
    ]);

    $log = app(StoreFoodLog::class)->handle($profile, [
        'source' => FoodSource::Custom->value,
        'label' => 'Rice',
        'portion_g' => '150',
        'portion_label' => 'A plate',
        'eaten_at' => '2026-09-08T12:00',
    ]);

    expect($log->glucose_reading_id)->toBe($reading->id);

    $this->travelBack();
});

it('links from a saved 2-hour reading to food already in the window', function () {
    $this->travelTo(Carbon::parse('2026-09-08 18:00:00', 'UTC'));

    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    $log = app(StoreFoodLog::class)->handle($profile, [
        'source' => FoodSource::Custom->value,
        'label' => 'Rice',
        'portion_g' => '150',
        'portion_label' => 'A plate',
        'eaten_at' => '2026-09-08T12:00',
    ]);

    $reading = app(StoreGlucoseReading::class)->handle($profile, [
        'value' => '210',
        'context' => GlucoseContext::PostMeal2h->value,
        'measured_at' => '2026-09-08T14:00',
    ]);

    expect($log->fresh()->glucose_reading_id)->toBe($reading->id);

    $this->travelBack();
});

it('unlinks food when the reading is no longer 2-hour post-meal', function () {
    $this->travelTo(Carbon::parse('2026-09-08 18:00:00', 'UTC'));

    $user = userWithProfile([], ['timezone' => 'UTC']);
    $profile = $user->defaultProfile;

    $log = app(StoreFoodLog::class)->handle($profile, [
        'source' => FoodSource::Custom->value,
        'label' => 'Rice',
        'portion_g' => '150',
        'portion_label' => 'A plate',
        'eaten_at' => '2026-09-08T12:00',
    ]);
    $reading = app(StoreGlucoseReading::class)->handle($profile, [
        'value' => '210',
        'context' => GlucoseContext::PostMeal2h->value,
        'measured_at' => '2026-09-08T14:00',
    ]);

    expect($log->fresh()->glucose_reading_id)->toBe($reading->id);

    app(UpdateGlucoseReading::class)->handle($reading, [
        'value' => '210',
        'context' => GlucoseContext::Fasting->value,
        'measured_at' => '2026-09-08T14:00',
    ]);

    expect($log->fresh()->glucose_reading_id)->toBeNull();

    $this->travelBack();
});
