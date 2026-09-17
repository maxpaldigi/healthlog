<?php

use App\Enums\ConsentType;
use App\Enums\GlucoseContext;
use App\Enums\ReadingTag;
use App\Models\LoginToken;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\PersonalAccessToken;

it('sends a magic link without revealing whether the email exists', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/magic-link', ['email' => 'nobody@example.com'])
        ->assertOk()
        ->assertJsonPath('message', 'If that email is registered, a login link is on its way.')
        ->assertJsonMissingPath('account_exists');

    Notification::assertNothingSent();
    expect(LoginToken::query()->count())->toBe(0);
});

it('issues a sanctum token from a magic link token', function () {
    Notification::fake();
    $user = userWithProfile(['email' => 'api-user@example.com']);

    $this->postJson('/api/v1/auth/magic-link', ['email' => $user->email])
        ->assertOk()
        ->assertJsonStructure(['login_url']);

    $notification = Notification::sent($user, MagicLinkNotification::class)->first();
    expect($notification)->not->toBeNull();

    preg_match('#/login/magic/([^?]+)#', $notification->url, $matches);
    $plain = rawurldecode($matches[1]);

    $this->postJson('/api/v1/auth/token', [
        'token' => $plain,
        'device_name' => 'flutter-test',
    ])
        ->assertOk()
        ->assertJsonStructure(['token', 'user' => ['id', 'email', 'health_data_consent']])
        ->assertJsonPath('user.email', $user->email);

    expect(PersonalAccessToken::query()->where('tokenable_id', $user->id)->count())->toBe(1);
});

it('rejects a reused magic link token', function () {
    Notification::fake();
    $user = userWithProfile(['email' => 'reuse@example.com']);

    $this->postJson('/api/v1/auth/magic-link', ['email' => $user->email])->assertOk();
    $notification = Notification::sent($user, MagicLinkNotification::class)->first();
    preg_match('#/login/magic/([^?]+)#', $notification->url, $matches);
    $plain = rawurldecode($matches[1]);

    $this->postJson('/api/v1/auth/token', ['token' => $plain])->assertOk();
    $this->postJson('/api/v1/auth/token', ['token' => $plain])
        ->assertStatus(422);
});

it('returns home payload for an authenticated api user', function () {
    $user = userWithProfile();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/home')
        ->assertOk()
        ->assertJsonPath('profile.name', $user->name)
        ->assertJsonPath('todayTarget', 3)
        ->assertJsonStructure(['todaySlots', 'recent', 'reminders']);
});

it('blocks health writes without consent and allows consent grant', function () {
    $user = userWithProfile([], [], consent: false);
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/home')
        ->assertForbidden()
        ->assertJsonPath('consent_required', true);

    $this->withToken($token)
        ->postJson('/api/v1/consent/health-data', ['health_data_consent' => '1'])
        ->assertOk()
        ->assertJsonPath('granted', true);

    expect($user->fresh()->hasGrantedConsent(ConsentType::HealthData))->toBeTrue();

    $this->withToken($token)
        ->getJson('/api/v1/home')
        ->assertOk();
});

it('stores blood pressure through the api', function () {
    $user = userWithProfile();
    $token = $user->createToken('test')->plainTextToken;
    $when = $user->defaultProfile->nowInTimezone()->subMinutes(10)->format('Y-m-d\TH:i');

    $this->withToken($token)
        ->postJson('/api/v1/bp', [
            'systolic' => 128,
            'diastolic' => 82,
            'pulse' => 68,
            'measured_at' => $when,
            'tag' => ReadingTag::None->value,
        ])
        ->assertCreated()
        ->assertJsonPath('reading.type', 'bp')
        ->assertJsonPath('reading.title', '128 / 82');

    expect($user->defaultProfile->bpReadings()->count())->toBe(1);
});

it('stores glucose through the api with a profile header', function () {
    $user = userWithProfile();
    $profile = $user->defaultProfile;
    $token = $user->createToken('test')->plainTextToken;
    $when = $profile->nowInTimezone()->subMinutes(5)->format('Y-m-d\TH:i');

    $this->withToken($token)
        ->withHeader('X-Profile-Id', (string) $profile->id)
        ->postJson('/api/v1/glucose', [
            'value' => 110,
            'context' => GlucoseContext::Random->value,
            'measured_at' => $when,
        ])
        ->assertCreated()
        ->assertJsonPath('reading.type', 'glucose');

    expect($profile->glucoseReadings()->count())->toBe(1);
});

it('revokes the current sanctum token on logout', function () {
    $user = userWithProfile();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    expect(PersonalAccessToken::query()->count())->toBe(0);

    // Feature tests reuse the app container; clear cached auth like a fresh PHP request.
    $this->app['auth']->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/v1/me')
        ->assertUnauthorized();
});
