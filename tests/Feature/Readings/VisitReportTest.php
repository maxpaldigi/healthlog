<?php

use App\Enums\GlucoseContext;
use App\Enums\ReadingTag;
use App\Models\BpReading;
use App\Models\GlucoseReading;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

it('shows a printable clinic visit report with slot table, notes, and range labels', function () {
    $this->travelTo(Carbon::parse('2026-09-08 12:00:00', 'Asia/Kolkata'));

    $user = userWithProfile([], [
        'timezone' => 'Asia/Kolkata',
        'name' => 'Ramesh',
        'glucose_high_mg_dl' => 180,
    ]);
    $profile = $user->defaultProfile;

    BpReading::factory()->for($profile)->create([
        'systolic' => 148,
        'diastolic' => 92,
        'note' => 'After climbing stairs',
        'measured_at' => Carbon::parse('2026-09-07 08:00:00', 'Asia/Kolkata')->utc(),
    ]);
    GlucoseReading::factory()->for($profile)->create([
        'value_mg_dl' => 210,
        'context' => GlucoseContext::Fasting,
        'note' => 'Skipped breakfast',
        'measured_at' => Carbon::parse('2026-09-07 07:30:00', 'Asia/Kolkata')->utc(),
    ]);

    $this->actingAs($user)
        ->get(route('report', ['from' => '2026-09-07', 'to' => '2026-09-08']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('readings/Report')
            ->where('report.profile.name', 'Ramesh')
            ->where('report.range.label', '7 September to 8 September 2026')
            ->where('report.days.0.slots.morning.title', '148 / 92')
            ->where('report.days.0.slots.morning.range_label', 'High')
            ->where('report.days.0.slots.morning.note', 'After climbing stairs')
            ->where('report.days.0.sugar.0.range_label', 'High')
            ->where('report.days.0.sugar.0.note', 'Skipped breakfast')
            ->where('shared', false));

    $this->travelBack();
});

it('saves an optional note on blood pressure and sugar', function () {
    $user = userWithProfile();
    $when = $user->defaultProfile->nowInTimezone()->subMinutes(10)->format('Y-m-d\TH:i');

    $this->actingAs($user)
        ->post(route('bp.store'), [
            'systolic' => 132,
            'diastolic' => 84,
            'measured_at' => $when,
            'tag' => ReadingTag::None->value,
            'note' => 'Felt dizzy',
        ])
        ->assertRedirect(route('home'));

    $this->actingAs($user)
        ->post(route('glucose.store'), [
            'value' => '118',
            'context' => GlucoseContext::Random->value,
            'measured_at' => $when,
            'note' => 'After tea',
        ])
        ->assertRedirect(route('home'));

    expect(BpReading::query()->first()->note)->toBe('Felt dizzy')
        ->and(GlucoseReading::query()->first()->note)->toBe('After tea');
});

it('downloads a csv of the same date range', function () {
    $this->travelTo(Carbon::parse('2026-09-08 12:00:00', 'Asia/Kolkata'));

    $user = userWithProfile([], ['timezone' => 'Asia/Kolkata']);
    BpReading::factory()->for($user->defaultProfile)->create([
        'systolic' => 120,
        'diastolic' => 80,
        'note' => 'At clinic',
        'measured_at' => Carbon::parse('2026-09-08 08:00:00', 'Asia/Kolkata')->utc(),
    ]);

    $csv = $this->actingAs($user)
        ->get(route('report.export', ['from' => '2026-09-08', 'to' => '2026-09-08']))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->streamedContent();

    expect($csv)
        ->toContain('Blood pressure')
        ->toContain('At clinic')
        ->toContain('120 / 80');

    $this->travelBack();
});
