<?php

use App\Enums\ShareLinkScope;
use App\Enums\TargetSetBy;
use App\Models\BpReading;
use App\Models\ClinicianTarget;
use App\Models\ShareLink;
use App\Support\GlucoseRange;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

it('creates a view link as read-only by default', function () {
    $user = userWithProfile();

    $this->actingAs($user)
        ->post(route('share-links.store'), [
            'from' => now()->toDateString(),
            'to' => now()->toDateString(),
            'days' => 7,
        ])
        ->assertRedirect();

    $link = ShareLink::query()->first();

    expect($link?->scope)->toBe(ShareLinkScope::Read);

    $this->get(route('shared.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('can_respond', false));
});

it('lets a guest leave a note and sugar targets on a reply link', function () {
    $this->travelTo(Carbon::parse('2026-09-10 12:00:00', 'UTC'));

    $user = userWithProfile([], [
        'timezone' => 'UTC',
        'name' => 'Meena',
        'glucose_low_mg_dl' => '80.00',
        'glucose_high_mg_dl' => '160.00',
        'glucose_target_set_by' => TargetSetBy::Self,
    ]);
    $profile = $user->defaultProfile;
    BpReading::factory()->for($profile)->create([
        'systolic' => 118,
        'diastolic' => 76,
        'measured_at' => Carbon::parse('2026-09-10 08:00:00', 'UTC'),
    ]);

    $this->actingAs($user)
        ->post(route('share-links.store'), [
            'from' => '2026-09-10',
            'to' => '2026-09-10',
            'days' => 7,
            'scope' => ShareLinkScope::Respond->value,
        ])
        ->assertRedirect();

    $this->post(route('logout'));

    $link = ShareLink::query()->first();

    expect($link?->scope)->toBe(ShareLinkScope::Respond);

    $this->get(route('shared.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('shared/Report')
            ->where('can_respond', true)
            ->where('respond.glucose_unit', 'mg/dL'));

    $this->from(route('shared.show', $link->token))
        ->post(route('shared.respond', $link->token), [
            'acknowledged' => '1',
            'instruction' => 'Log sugar before breakfast.',
            'glucose_low' => '70',
            'glucose_high' => '180',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $profile->refresh();
    $row = ClinicianTarget::query()->first();

    expect($row)->not->toBeNull()
        ->and($row->instruction)->toBe('Log sugar before breakfast.')
        ->and((string) $row->glucose_low_mg_dl)->toBe('70.00')
        ->and($profile->glucose_target_set_by)->toBe(TargetSetBy::Clinician)
        ->and((string) $profile->glucose_low_mg_dl)->toBe('70.00')
        ->and((string) $profile->glucose_high_mg_dl)->toBe('180.00')
        ->and(GlucoseRange::label($profile, '210.00'))->toBe('High')
        ->and($row->ip_address)->not->toBeNull();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('clinicNote.instruction', 'Log sugar before breakfast.')
            ->where('clinicNote.targets_line', 'Clinic sugar targets: 70.00–180.00 mg/dL.'));

    $this->actingAs($user)
        ->get(route('report', ['from' => '2026-09-10', 'to' => '2026-09-10']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('report.clinic_note.instruction', 'Log sugar before breakfast.'));

    $this->travelBack();
});

it('rejects a reply on a view-only or expired link', function () {
    $user = userWithProfile();
    $profile = $user->defaultProfile;

    $read = $profile->shareLinks()->create([
        'token' => ShareLink::makeToken(),
        'scope' => ShareLinkScope::Read,
        'starts_on' => now()->toDateString(),
        'ends_on' => now()->toDateString(),
        'expires_at' => now()->addDay(),
    ]);

    $this->post(route('shared.respond', $read->token), [
        'acknowledged' => '1',
        'instruction' => 'Hello',
    ])->assertForbidden();

    $expired = $profile->shareLinks()->create([
        'token' => ShareLink::makeToken(),
        'scope' => ShareLinkScope::Respond,
        'starts_on' => now()->toDateString(),
        'ends_on' => now()->toDateString(),
        'expires_at' => now()->subMinute(),
    ]);

    $this->post(route('shared.respond', $expired->token), [
        'acknowledged' => '1',
        'instruction' => 'Hello',
    ])->assertStatus(410);
});

it('requires both sugar targets or neither', function () {
    $user = userWithProfile();
    $link = $user->defaultProfile->shareLinks()->create([
        'token' => ShareLink::makeToken(),
        'scope' => ShareLinkScope::Respond,
        'starts_on' => now()->toDateString(),
        'ends_on' => now()->toDateString(),
        'expires_at' => now()->addDay(),
    ]);

    $this->from(route('shared.show', $link->token))
        ->post(route('shared.respond', $link->token), [
            'acknowledged' => '1',
            'glucose_low' => '70',
        ])
        ->assertSessionHasErrors('glucose_high');
});
