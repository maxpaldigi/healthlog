<?php

use App\Enums\ConsentType;
use App\Models\Consent;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('does not register without a separate health-data consent', function () {
    $this->from(route('register'))
        ->post(route('register.store'), registerAttributes([
            'health_data_consent' => null,
        ]))
        ->assertRedirect(route('register'))
        ->assertSessionHasErrors('health_data_consent');

    expect(User::query()->where('email', 'test@example.com')->exists())->toBeFalse();
});

it('records health-data consent when a person registers', function () {
    $this->post(route('register.store'), registerAttributes())
        ->assertRedirect(route('login.check-email'));

    $user = User::query()->where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasGrantedConsent(ConsentType::HealthData))->toBeTrue();

    $consent = Consent::query()->where('user_id', $user->id)->first();

    expect($consent)->not->toBeNull()
        ->and($consent->type)->toBe(ConsentType::HealthData)
        ->and($consent->policy_version)->toBe(Consent::POLICY_VERSION)
        ->and($consent->revoked_at)->toBeNull();
});

it('sends a person without health-data consent to the consent screen', function () {
    $user = userWithProfile([], [], false);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertRedirect(route('consent.health-data'));

    $this->actingAs($user)
        ->get(route('bp.create'))
        ->assertRedirect(route('consent.health-data'));
});

it('does not complete onboarding without health-data consent', function () {
    $user = userWithProfile([
        'onboarding_completed_at' => null,
    ], [], false);

    $this->actingAs($user)
        ->post(route('onboarding.complete'))
        ->assertRedirect(route('consent.health-data'));

    expect($user->fresh()->onboarding_completed_at)->toBeNull();
});

it('records health-data consent from the consent screen', function () {
    $user = userWithProfile([
        'onboarding_completed_at' => null,
    ], [], false);

    $this->actingAs($user)
        ->get(route('consent.health-data'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('consent/HealthData')
            ->where('policyVersion', Consent::POLICY_VERSION));

    $this->actingAs($user)
        ->post(route('consent.health-data.store'), [
            'health_data_consent' => '1',
        ])
        ->assertRedirect(route('home'));

    expect($user->fresh()->hasGrantedConsent(ConsentType::HealthData))->toBeTrue();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Home'));
});
