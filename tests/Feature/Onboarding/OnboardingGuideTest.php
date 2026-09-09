<?php

use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

it('opens the guide after a new person registers and signs in', function () {
    $user = userWithProfile([
        'onboarding_completed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('auth.show_guide', true));
});

it('does not auto-open the guide after it has been finished', function () {
    $user = userWithProfile([
        'onboarding_completed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.show_guide', false));
});

it('marks the guide as done so it does not return on the next visit', function () {
    $this->travelTo(Carbon::parse('2026-09-09 18:30:00', 'Europe/London'));

    $user = userWithProfile([
        'onboarding_completed_at' => null,
    ]);

    $this->actingAs($user)
        ->from(route('home'))
        ->post(route('onboarding.complete'))
        ->assertRedirect(route('home'));

    expect($user->fresh()->onboarding_completed_at)->not->toBeNull();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('auth.show_guide', false));

    $this->travelBack();
});

it('does not show the guide to guests', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('auth.show_guide', false));
});
