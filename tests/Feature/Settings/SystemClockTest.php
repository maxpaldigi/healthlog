<?php

use App\Models\User;
use App\Support\SystemTimezone;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

it('uses the device time zone from the request for today', function () {
    $this->travelTo(Carbon::parse('2026-09-09 23:30:00', 'UTC'));

    $user = userWithProfile([], ['timezone' => 'Asia/Kolkata']);

    $this->actingAs($user)
        ->withHeader('X-Timezone', 'America/New_York')
        ->get(route('bp.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('defaults.measured_at', '2026-09-09T19:30'));

    expect(SystemTimezone::isValid('America/New_York'))->toBeTrue();

    $this->travelBack();
});

it('saves the computer time zone when a person registers', function () {
    $this->post(route('register.store'), [
        'name' => 'Pat',
        'email' => 'pat@example.com',
        'timezone' => 'Asia/Kolkata',
        'detected_timezone' => 'Europe/London',
        'glucose_unit' => 'mg_dl',
        'weight_unit' => 'kg',
    ])->assertRedirect(route('login.check-email'));

    expect(User::query()->where('email', 'pat@example.com')->first()?->defaultProfile?->timezone)
        ->toBe('Europe/London');
});
