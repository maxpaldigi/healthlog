<?php

use App\Support\DemoAccount;

it('keeps the demo email fixed so a real account cannot be targeted', function () {
    config(['auth.demo_login_email' => 'someone@example.com']);

    expect(DemoAccount::email())->toBe('demo@healthlog.test')
        ->and(DemoAccount::matches('demo@healthlog.test'))->toBeTrue()
        ->and(DemoAccount::matches('someone@example.com'))->toBeFalse();
});

it('refuses instant login in production even when the flag is on', function () {
    $this->app['env'] = 'production';
    config(['auth.demo_instant_login' => true]);

    expect(app()->isProduction())->toBeTrue()
        ->and(DemoAccount::instantLoginEnabled())->toBeFalse();
});

it('refuses instant login on staging even when the flag is on', function () {
    $this->app['env'] = 'staging';
    config(['auth.demo_instant_login' => true]);

    expect(DemoAccount::instantLoginEnabled())->toBeFalse();
});

it('allows instant login in tests when the flag is on', function () {
    config(['auth.demo_instant_login' => true]);

    expect(DemoAccount::instantLoginEnabled())->toBeTrue();
});

it('refuses instant login when the flag is off', function () {
    config(['auth.demo_instant_login' => false]);

    expect(DemoAccount::instantLoginEnabled())->toBeFalse();
});
