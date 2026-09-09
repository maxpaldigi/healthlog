<?php

use App\Enums\GlucoseUnit;
use App\Enums\WeightUnit;
use App\Models\LoginToken;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the login and register screens', function () {
    $this->get(route('login'))->assertOk();
    $this->get(route('register'))->assertOk();
});

it('registers a passwordless user with a default profile and sends a magic link', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'timezone' => 'Asia/Kolkata',
        'glucose_unit' => GlucoseUnit::MgDl->value,
        'weight_unit' => WeightUnit::Kg->value,
    ])
        ->assertRedirect(route('login.check-email'));

    $this->assertGuest();

    $user = User::query()->where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->password)->toBeNull()
        ->and($user->email_verified_at)->toBeNull();

    $profile = $user->defaultProfile;

    expect($profile)->not->toBeNull()
        ->and($profile->name)->toBe('Test User')
        ->and($profile->timezone)->toBe('Asia/Kolkata')
        ->and($profile->glucose_unit)->toBe(GlucoseUnit::MgDl)
        ->and($profile->weight_unit)->toBe(WeightUnit::Kg)
        ->and($profile->is_default)->toBeTrue();

    Notification::assertSentTo($user, MagicLinkNotification::class);
});

it('logs the user in and verifies email when a valid magic link is used', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create([
        'email' => 'priya@example.com',
        'password' => null,
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
    ])->assertRedirect(route('login.check-email'));

    $url = null;

    Notification::assertSentTo($user, MagicLinkNotification::class, function (MagicLinkNotification $notification) use (&$url): bool {
        $url = $notification->url;

        return true;
    });

    expect($url)->not->toBeNull();

    $this->get($url)
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user->fresh());
    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

it('does not allow a magic link to be reused', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email]);

    $url = Notification::sent($user, MagicLinkNotification::class)->first()->url;

    $this->get($url)->assertRedirect(route('home'));
    $this->post(route('logout'));

    $this->get($url)
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');

    $this->assertGuest();
});

it('rejects an expired or tampered magic link', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email]);

    $url = Notification::sent($user, MagicLinkNotification::class)->first()->url;

    $this->travel(16)->minutes();

    $this->get($url)
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');

    $this->assertGuest();

    $tampered = $url.'x';

    $this->get($tampered)
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('shows an open-login-link button after a known email is submitted', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
    ])->assertRedirect(route('login.check-email'));

    $this->get(route('login.check-email'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/CheckEmail')
            ->where('sendsRealMail', false)
            ->where('loginUrl', fn (?string $url): bool => is_string($url) && str_contains($url, '/login/magic/')));
});

it('does not 500 when the login email cannot be sent', function () {
    Event::listen(NotificationSending::class, function (): void {
        throw new RuntimeException('Request to Resend API failed.');
    });

    $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'mailfail@example.com',
        'timezone' => 'Asia/Kolkata',
        'glucose_unit' => GlucoseUnit::MgDl->value,
        'weight_unit' => WeightUnit::Kg->value,
    ])->assertRedirect(route('login.check-email'));

    $this->assertDatabaseHas('users', ['email' => 'mailfail@example.com']);

    $this->get(route('login.check-email'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/CheckEmail')
            ->where('mailFailed', true)
            ->where('loginUrl', fn (?string $url): bool => is_string($url) && str_contains($url, '/login/magic/')));
});

it('returns the same response for unknown emails and sends no notification', function () {
    Notification::fake();

    $this->post(route('login.store'), [
        'email' => 'nobody@example.com',
    ])->assertRedirect(route('login.check-email'));

    Notification::assertNothingSent();
    expect(LoginToken::query()->count())->toBe(0);
});

it('logs an authenticated user out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('home'));

    $this->assertGuest();
});

it('does not leak a signed url for a missing token', function () {
    $url = URL::temporarySignedRoute(
        'login.magic',
        now()->addMinutes(15),
        ['token' => 'missing-token'],
    );

    $this->get($url)
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
