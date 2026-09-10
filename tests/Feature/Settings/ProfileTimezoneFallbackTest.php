<?php

use App\Models\Profile;
use App\Support\SystemTimezone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

it('uses the detected time zone when the profile has none stored', function () {
    $this->travelTo(Carbon::parse('2026-09-10 12:00:00', 'UTC'));

    $user = userWithProfile([], ['timezone' => null]);

    $this->actingAs($user)
        ->withHeader('X-Timezone', 'Europe/Lisbon')
        ->get(route('bp.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('defaults.measured_at', '2026-09-10T13:00'));

    expect($user->defaultProfile->clockTimezone())->toBe('Europe/Lisbon')
        ->and(SystemTimezone::isValid('Europe/Lisbon'))->toBeTrue();

    $this->travelBack();
});

it('falls back to the app time zone when nothing is detected', function () {
    $this->app->instance('request', Request::create('/'));

    $profile = new Profile(['timezone' => null]);

    expect($profile->clockTimezone())->toBe((string) config('app.timezone'));
});
