<?php

use App\Models\BpReading;
use App\Models\ShareLink;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

it('creates an expiring read-only share link for the visit report', function () {
    $this->travelTo(Carbon::parse('2026-09-08 12:00:00', 'Asia/Kolkata'));

    $user = userWithProfile([], ['timezone' => 'Asia/Kolkata', 'name' => 'Meena']);
    BpReading::factory()->for($user->defaultProfile)->create([
        'systolic' => 118,
        'diastolic' => 76,
        'measured_at' => Carbon::parse('2026-09-08 08:00:00', 'Asia/Kolkata')->utc(),
    ]);

    $this->actingAs($user)
        ->from(route('report', ['from' => '2026-09-01', 'to' => '2026-09-08']))
        ->post(route('share-links.store'), [
            'from' => '2026-09-01',
            'to' => '2026-09-08',
            'days' => 7,
        ])
        ->assertRedirect()
        ->assertSessionHas('share_url');

    $link = ShareLink::query()->first();

    expect($link)->not->toBeNull()
        ->and($link->starts_on->toDateString())->toBe('2026-09-01')
        ->and($link->ends_on->toDateString())->toBe('2026-09-08');

    $this->get(route('shared.show', $link->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('shared/Report')
            ->where('shared', true)
            ->where('report.profile.name', 'Meena')
            ->where('report.days.7.slots.morning.title', '118 / 76'));

    $this->travelBack();
});

it('rejects an expired share link', function () {
    $user = userWithProfile();
    $link = $user->defaultProfile->shareLinks()->create([
        'token' => ShareLink::makeToken(),
        'starts_on' => now()->toDateString(),
        'ends_on' => now()->toDateString(),
        'expires_at' => now()->subMinute(),
    ]);

    $this->get(route('shared.show', $link->token))->assertStatus(410);
});
