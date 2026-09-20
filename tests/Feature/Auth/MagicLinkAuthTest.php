<?php

use App\Actions\Auth\EnterDemoAccount;
use App\Enums\ConsentType;
use App\Enums\GlucoseUnit;
use App\Enums\WeightUnit;
use App\Models\LoginToken;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the login and register screens', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Login')
            ->where('demoEmail', null));
    $this->get(route('register'))->assertOk();
});

it('shows demo login on this computer when the flag is on', function () {
    config(['auth.demo_instant_login' => true]);

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('demoEmail', 'demo@healthlog.test'));

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Login')
            ->where('demoEmail', 'demo@healthlog.test'));
});

it('never shows or accepts demo login in production', function () {
    $this->app['env'] = 'production';
    config(['auth.demo_instant_login' => true]);
    Notification::fake();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->where('demoEmail', null));

    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Login')
            ->where('demoEmail', null));

    $this->post(route('login.store'), [
        'email' => 'demo@healthlog.test',
    ])->assertRedirect(route('login.check-email'));

    $this->assertGuest();
    Notification::assertNothingSent();
});

it('opens the app for the demo email without sending mail or recording consent', function () {
    config(['auth.demo_instant_login' => true]);
    Notification::fake();

    $this->post(route('login.store'), [
        'email' => 'demo@healthlog.test',
    ])->assertRedirect(route('home'));

    $this->assertAuthenticated();
    expect(auth()->user()?->email)->toBe('demo@healthlog.test')
        ->and(auth()->user()?->hasGrantedConsent(ConsentType::HealthData))->toBeFalse();

    $this->get(route('home'))->assertRedirect(route('consent.health-data'));

    Notification::assertNothingSent();
});

it('does not open the app for the demo email when instant login is off', function () {
    config(['auth.demo_instant_login' => false]);
    Notification::fake();

    $this->post(route('login.store'), [
        'email' => 'demo@healthlog.test',
    ])->assertRedirect(route('login.check-email'));

    $this->assertGuest();
    Notification::assertNothingSent();
});

it('does not record health-data consent when entering the demo account', function () {
    config(['auth.demo_instant_login' => true]);

    $user = app(EnterDemoAccount::class)->handle();

    expect($user->email)->toBe('demo@healthlog.test')
        ->and($user->hasGrantedConsent(ConsentType::HealthData))->toBeFalse()
        ->and($user->defaultProfile)->not->toBeNull();
});

it('does not instant-login a real account email', function () {
    config(['auth.demo_instant_login' => true]);
    Notification::fake();

    $user = User::factory()->create(['email' => 'parent@example.com']);

    $this->post(route('login.store'), [
        'email' => $user->email,
    ])->assertRedirect(route('login.check-email'));

    $this->assertGuest();
    Notification::assertSentTo($user, MagicLinkNotification::class);
});

it('registers a passwordless user with a default profile and sends a magic link', function () {
    Notification::fake();

    $this->post(route('register.store'), registerAttributes())
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
        ->assertRedirect(route('home'))
        ->assertCookie(Auth::guard()->getRecallerName());

    $this->assertAuthenticatedAs($user->fresh());
    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

it('opens the app bridge on mobile instead of consuming the token', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email]);
    $notification = Notification::sent($user, MagicLinkNotification::class)->first();
    expect($notification)->not->toBeNull();

    $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36')
        ->get($notification->url)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/OpenApp')
            ->where('appUrl', fn (string $url): bool => str_starts_with($url, 'healthyme://login/magic/'))
            ->has('webContinueUrl'));

    expect(LoginToken::query()->whereNull('consumed_at')->count())->toBe(1);
});

it('still logs in on the website when continue=web is used', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('login.store'), ['email' => $user->email]);
    $notification = Notification::sent($user, MagicLinkNotification::class)->first();

    preg_match('#/login/magic/([^?]+)#', $notification->url, $matches);
    $plain = rawurldecode($matches[1]);
    parse_str(parse_url($notification->url, PHP_URL_QUERY) ?: '', $query);

    $webContinueUrl = URL::temporarySignedRoute(
        'login.magic',
        Carbon::createFromTimestamp((int) $query['expires']),
        ['token' => $plain, 'continue' => 'web'],
    );

    $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36')
        ->get($webContinueUrl)
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
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
            ->where('demoLogin', false)
            ->where('loginUrl', fn (?string $url): bool => is_string($url) && str_contains($url, '/login/magic/')));
});

it('does not 500 when the login email cannot be sent', function () {
    Event::listen(NotificationSending::class, function (): void {
        throw new RuntimeException('Request to Resend API failed.');
    });

    $this->post(route('register.store'), registerAttributes([
        'email' => 'mailfail@example.com',
    ]))->assertRedirect(route('login.check-email'));

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

    $this->get(route('login.check-email'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/CheckEmail')
            ->missing('accountExists')
            ->missing('unknownEmail')
            ->where('loginUrl', null)
            ->where('mailFailed', false));
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
