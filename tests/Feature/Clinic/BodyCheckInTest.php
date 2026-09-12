<?php

use App\Support\BodyCheckIn;
use Inertia\Testing\AssertableInertia as Assert;

it('keeps registration free of height and weight then asks after sign in', function () {
    $this->post(route('register.store'), registerAttributes())
        ->assertRedirect(route('login.check-email'));

    $user = userWithProfile([
        'email' => 'later@example.com',
    ], [
        'body_checkin_pending' => true,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('clinic.body'));

    $this->actingAs($user)
        ->get(route('clinic.body'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('clinic/BodyCheckIn'));
});

it('saves height and weight and shows bmi as a number only', function () {
    $user = userWithProfile([], ['body_checkin_pending' => true]);

    $this->actingAs($user)
        ->post(route('clinic.body.store'), [
            'height_unit' => 'cm',
            'height_cm' => '175',
            'weight' => '70',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home'));

    $profile = $user->defaultProfile->fresh();

    expect((string) $profile->height_cm)->toBe('175.00')
        ->and($profile->body_checkin_pending)->toBeFalse()
        ->and($profile->weights()->count())->toBe(1);

    $this->actingAs($user)
        ->get(route('clinic.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('weights.0.value', '70.00')
            ->where('weights.0.bmi', '22.9'));
});

it('lets the person skip the first ask', function () {
    $user = userWithProfile([], ['body_checkin_pending' => true]);

    $this->actingAs($user)
        ->post(route('clinic.body.skip'))
        ->assertRedirect(route('home'));

    $profile = $user->defaultProfile->fresh();

    expect($profile->body_checkin_pending)->toBeFalse()
        ->and($profile->height_prompt_skipped_at)->not->toBeNull()
        ->and($profile->height_cm)->toBeNull();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Home'));
});

it('asks for weight again after six months', function () {
    $user = userWithProfile([], ['height_cm' => '175']);
    $profile = $user->defaultProfile;

    $profile->weights()->create([
        'kg' => 70,
        'measured_at' => $profile->nowInTimezone()->subMonths(7)->utc(),
    ]);

    expect(BodyCheckIn::weightIsDue($profile->fresh()))->toBeTrue();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('bodyPrompt.key', 'body-weight'));
});

it('does not nag for weight when the last reading is recent', function () {
    $user = userWithProfile([], ['height_cm' => '175']);
    $profile = $user->defaultProfile;

    $profile->weights()->create([
        'kg' => 70,
        'measured_at' => $profile->nowInTimezone()->subMonth()->utc(),
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->where('bodyPrompt', null));
});
