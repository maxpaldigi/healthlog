<?php

use App\Models\BpReading;
use App\Models\GlucoseReading;
use App\Support\Glucose;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

it('shows an empty trends dashboard when there are no readings', function () {
    $user = userWithProfile();

    $this->actingAs($user)
        ->get(route('trends'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('readings/Trends')
            ->where('insights.summary.bp_count', 0)
            ->where('insights.summary.glucose_count', 0)
            ->where('insights.summary_text', 'No readings in this date range yet.')
            ->has('presets', 3)
            ->has('filters.range_label'));
});

it('summarises blood pressure and sugar trends for the selected range', function () {
    $this->travelTo(Carbon::parse('2026-09-08 12:00:00', 'Asia/Kolkata'));

    $user = userWithProfile([], ['timezone' => 'Asia/Kolkata']);
    $profile = $user->defaultProfile;

    BpReading::factory()->for($profile)->create([
        'systolic' => 120,
        'diastolic' => 80,
        'measured_at' => Carbon::parse('2026-09-07 08:00:00', 'Asia/Kolkata')->utc(),
    ]);
    BpReading::factory()->for($profile)->create([
        'systolic' => 130,
        'diastolic' => 84,
        'measured_at' => Carbon::parse('2026-09-07 14:00:00', 'Asia/Kolkata')->utc(),
    ]);
    BpReading::factory()->for($profile)->create([
        'systolic' => 128,
        'diastolic' => 82,
        'measured_at' => Carbon::parse('2026-09-07 20:00:00', 'Asia/Kolkata')->utc(),
    ]);
    BpReading::factory()->for($profile)->create([
        'systolic' => 122,
        'diastolic' => 78,
        'measured_at' => Carbon::parse('2026-09-08 08:00:00', 'Asia/Kolkata')->utc(),
    ]);
    GlucoseReading::factory()->for($profile)->create([
        'value_mg_dl' => 100,
        'measured_at' => Carbon::parse('2026-09-07 07:30:00', 'Asia/Kolkata')->utc(),
    ]);
    GlucoseReading::factory()->for($profile)->create([
        'value_mg_dl' => 120,
        'measured_at' => Carbon::parse('2026-09-08 07:30:00', 'Asia/Kolkata')->utc(),
    ]);

    $this->actingAs($user)
        ->get(route('trends', ['from' => '2026-09-07', 'to' => '2026-09-08']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('readings/Trends')
            ->where('insights.summary.bp_count', 4)
            ->where('insights.summary.glucose_count', 2)
            ->where('insights.summary.average_bp', '125 / 81')
            ->where('insights.summary.average_glucose', Glucose::fromMgDl('110.00', $profile->glucose_unit).' mg/dL')
            ->where('insights.summary.days_with_three_bp', 1)
            ->where('insights.summary.days_with_bp', 2)
            ->has('insights.bp_trend', 2)
            ->has('insights.glucose_trend', 2)
            ->where('insights.bp_slots.0.count', 2)
            ->where('insights.bp_slots.1.count', 1)
            ->where('insights.bp_slots.2.count', 1));

    $this->travelBack();
});

it('keeps older readings out of the selected trends range', function () {
    $this->travelTo(Carbon::parse('2026-09-08 12:00:00', 'Asia/Kolkata'));

    $user = userWithProfile([], ['timezone' => 'Asia/Kolkata']);
    $profile = $user->defaultProfile;

    BpReading::factory()->for($profile)->create([
        'measured_at' => Carbon::parse('2026-07-01 08:00:00', 'Asia/Kolkata')->utc(),
    ]);
    BpReading::factory()->for($profile)->create([
        'systolic' => 118,
        'diastolic' => 76,
        'measured_at' => Carbon::parse('2026-09-08 08:00:00', 'Asia/Kolkata')->utc(),
    ]);

    $this->actingAs($user)
        ->get(route('trends', ['from' => '2026-09-01', 'to' => '2026-09-08']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('insights.summary.bp_count', 1)
            ->where('insights.summary.latest_bp', '118 / 76')
            ->where('filters.preset', 'month'));

    $this->travelBack();
});
