<?php

use App\Support\AppLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

it('uses the signed-in locale when it is enabled', function () {
    config(['locales.enabled' => ['en' => 'English', 'xx' => 'Test']]);

    $user = userWithProfile(['locale' => 'xx']);
    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);
    $request->cookies->set(AppLocale::COOKIE, 'en');

    expect(AppLocale::resolve($request))->toBe('xx');
});

it('uses the cookie when the user has not picked a language', function () {
    config(['locales.enabled' => ['en' => 'English', 'xx' => 'Test']]);

    $user = userWithProfile(['locale' => null]);
    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn () => $user);
    $request->cookies->set(AppLocale::COOKIE, 'xx');

    expect(AppLocale::resolve($request))->toBe('xx');
});

it('ignores a locale that is not enabled', function () {
    $request = Request::create('/', 'GET', server: [
        'HTTP_ACCEPT_LANGUAGE' => 'zz,en;q=0.5',
    ]);
    $request->cookies->set(AppLocale::COOKIE, 'zz');

    expect(AppLocale::resolve($request))->toBe('en')
        ->and(AppLocale::isEnabled('zz'))->toBeFalse();
});

it('uses Accept-Language only when that tag is enabled', function () {
    config(['locales.enabled' => ['en' => 'English', 'xx' => 'Test']]);

    $request = Request::create('/', 'GET', server: [
        'HTTP_ACCEPT_LANGUAGE' => 'xx,en;q=0.8',
    ]);

    expect(AppLocale::resolve($request))->toBe('xx');
});

it('fills missing translation keys from English', function () {
    config(['locales.enabled' => ['en' => 'English', 'xx' => 'Test']]);

    $catalog = AppLocale::catalog('xx');

    expect($catalog['nav.home'])->toBe('Home')
        ->and($catalog['food.photo_hint'])->toBe(AppLocale::get('food.photo_hint'));
});

it('replaces placeholders in catalog strings', function () {
    expect(AppLocale::get('home.hello', ['name' => 'Ada']))->toBe('Hello, Ada');
});
