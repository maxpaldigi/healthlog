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

it('includes every day in the range so today is visible with its readings', function () {
    $this->travelTo(Carbon::parse('2026-09-09 18:20:00', 'Europe/London'));

    $user = userWithProfile([], [
        'timezone' => 'Europe/London',
        'name' => 'Deepak',
    ]);

    BpReading::factory()->for($user->defaultProfile)->create([
        'systolic' => 132,
        'diastolic' => 84,
        'measured_at' => Carbon::parse('2026-09-09 18:15:00', 'Europe/London')->utc(),
    ]);

    $this->actingAs($user)
        ->get(route('report', ['from' => '2026-09-03', 'to' => '2026-09-09']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('report.days', 7)
            ->where('report.days.0.date', '2026-09-03')
            ->where('report.days.6.date', '2026-09-09')
            ->where('report.days.6.date_label', 'Today')
            ->where('report.days.6.slots.evening.title', '132 / 84'));

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

it('includes range numbers on the visit report', function () {
    $this->travelTo(Carbon::parse('2026-01-02 12:00:00', 'UTC'));

    $user = userWithProfile([], [
        'timezone' => 'UTC',
        'name' => 'Ramesh',
        'glucose_low_mg_dl' => '70.00',
        'glucose_high_mg_dl' => '180.00',
    ]);
    $profile = $user->defaultProfile;

    BpReading::factory()->for($profile)->create([
        'systolic' => 120,
        'diastolic' => 80,
        'measured_at' => Carbon::parse('2026-01-01 08:00:00', 'UTC'),
    ]);
    BpReading::factory()->for($profile)->create([
        'systolic' => 128,
        'diastolic' => 82,
        'measured_at' => Carbon::parse('2026-01-01 20:00:00', 'UTC'),
    ]);
    GlucoseReading::factory()->for($profile)->create([
        'value_mg_dl' => '100.00',
        'context' => GlucoseContext::Fasting,
        'measured_at' => Carbon::parse('2026-01-01 07:00:00', 'UTC'),
    ]);
    GlucoseReading::factory()->for($profile)->create([
        'value_mg_dl' => '140.00',
        'context' => GlucoseContext::PostMeal2h,
        'measured_at' => Carbon::parse('2026-01-01 14:00:00', 'UTC'),
    ]);

    $this->actingAs($user)
        ->get(route('report', ['from' => '2026-01-01', 'to' => '2026-01-01']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('report.insights.stats.bp_variability.sd_systolic', '5.7')
            ->where('report.insights.stats.morning_minus_evening.difference', -8)
            ->where('report.insights.stats.time_in_range.percent', 100)
            ->where('report.insights.stats.post_meal_minus_fasting.line', 'Mean 2-hour after-meal sugar was 40 mg/dL above mean fasting.')
            ->has('report.insights.stats_lines', 4));

    $this->travelBack();
});

it('downloads a pdf of the visit report', function () {
    $this->travelTo(Carbon::parse('2026-09-08 12:00:00', 'Asia/Kolkata'));

    $user = userWithProfile([], ['timezone' => 'Asia/Kolkata', 'name' => 'Ramesh']);
    BpReading::factory()->for($user->defaultProfile)->create([
        'systolic' => 120,
        'diastolic' => 80,
        'measured_at' => Carbon::parse('2026-09-08 08:00:00', 'Asia/Kolkata')->utc(),
    ]);

    $pdf = $this->actingAs($user)
        ->get(route('report.pdf', ['from' => '2026-09-08', 'to' => '2026-09-08']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($pdf->getContent())->toStartWith('%PDF');

    $this->travelBack();
});

it('downloads a pdf from a share link and rejects an expired one', function () {
    $this->travelTo(Carbon::parse('2026-09-08 12:00:00', 'UTC'));

    $user = userWithProfile([], ['timezone' => 'UTC', 'name' => 'Meena']);
    $profile = $user->defaultProfile;
    BpReading::factory()->for($profile)->create([
        'systolic' => 118,
        'diastolic' => 76,
        'measured_at' => Carbon::parse('2026-09-08 08:00:00', 'UTC'),
    ]);

    $link = $profile->shareLinks()->create([
        'token' => 'sharetokenforthevisitpdf1sharetokenforthevisitpdf',
        'starts_on' => '2026-09-08',
        'ends_on' => '2026-09-08',
        'expires_at' => now()->addDay(),
    ]);

    $pdf = $this->get(route('shared.pdf', $link->token))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($pdf->getContent())->toStartWith('%PDF');

    $link->update(['expires_at' => now()->subMinute()]);

    $this->get(route('shared.pdf', $link->token))->assertStatus(410);

    $this->travelBack();
});

it('downloads fhir json of the same date range', function () {
    $this->travelTo(Carbon::parse('2026-09-08 12:00:00', 'Asia/Kolkata'));

    $user = userWithProfile(['email' => 'parent@example.com'], [
        'timezone' => 'Asia/Kolkata',
        'name' => 'Ramesh',
        'date_of_birth' => '1950-01-15',
    ]);
    BpReading::factory()->for($user->defaultProfile)->create([
        'systolic' => 120,
        'diastolic' => 80,
        'measured_at' => Carbon::parse('2026-09-08 08:00:00', 'Asia/Kolkata')->utc(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('report.fhir', ['from' => '2026-09-08', 'to' => '2026-09-08']))
        ->assertOk()
        ->assertHeader('content-type', 'application/fhir+json; charset=UTF-8');

    $json = $response->getContent();

    expect($json)
        ->toContain('"resourceType":"Bundle"')
        ->toContain('8480-6')
        ->toContain('8462-4')
        ->not->toContain('parent@example.com')
        ->not->toContain('1950-01-15');

    $this->travelBack();
});

it('downloads fhir json from a share link and rejects an expired one', function () {
    $this->travelTo(Carbon::parse('2026-09-08 12:00:00', 'UTC'));

    $user = userWithProfile([], ['timezone' => 'UTC', 'name' => 'Meena']);
    $profile = $user->defaultProfile;
    BpReading::factory()->for($profile)->create([
        'systolic' => 118,
        'diastolic' => 76,
        'measured_at' => Carbon::parse('2026-09-08 08:00:00', 'UTC'),
    ]);

    $link = $profile->shareLinks()->create([
        'token' => 'sharetokenforthevisitfhir1sharetokenforthevisitfhir',
        'starts_on' => '2026-09-08',
        'ends_on' => '2026-09-08',
        'expires_at' => now()->addDay(),
    ]);

    $json = $this->get(route('shared.fhir', $link->token))
        ->assertOk()
        ->assertHeader('content-type', 'application/fhir+json; charset=UTF-8')
        ->getContent();

    expect($json)->toContain('8480-6');

    $link->update(['expires_at' => now()->subMinute()]);

    $this->get(route('shared.fhir', $link->token))->assertStatus(410);

    $this->travelBack();
});
