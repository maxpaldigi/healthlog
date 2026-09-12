<?php

use App\Support\AppLocale;
use Inertia\Testing\AssertableInertia as Assert;

it('shares english as the only enabled locale by default', function () {
    $this->actingAs(userWithProfile())
        ->get(route('appearance.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('locale', 'en')
            ->where('locales', [['value' => 'en', 'label' => 'English']])
            ->has('translations'));
});

it('ignores an unknown locale cookie', function () {
    $this->actingAs(userWithProfile())
        ->withUnencryptedCookie(AppLocale::COOKIE, 'zz')
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
});

it('persists an enabled locale on the user and the cookie', function () {
    config(['locales.enabled' => ['en' => 'English', 'xx' => 'Test']]);

    $user = userWithProfile();

    $this->actingAs($user)
        ->from(route('appearance.edit'))
        ->patch(route('appearance.locale'), ['locale' => 'xx'])
        ->assertRedirect(route('appearance.edit'))
        ->assertPlainCookie(AppLocale::COOKIE, 'xx');

    expect($user->fresh()->locale)->toBe('xx');

    $this->actingAs($user->fresh())
        ->get(route('appearance.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('locale', 'xx'));
});

it('rejects a locale that is not enabled', function () {
    $user = userWithProfile();

    $this->actingAs($user)
        ->from(route('appearance.edit'))
        ->patch(route('appearance.locale'), ['locale' => 'zz'])
        ->assertRedirect(route('appearance.edit'))
        ->assertSessionHasErrors('locale');

    expect($user->fresh()->locale)->toBeNull();
});

it('keeps catalog copy descriptive', function () {
    foreach (glob(lang_path('*.json')) ?: [] as $file) {
        $text = strtolower((string) file_get_contents($file));

        expect($text)->not->toContain('diagnos')
            ->and($text)->not->toContain('treat')
            ->and($text)->not->toContain('prescri')
            ->and($text)->not->toContain('medical advice')
            ->and($text)->not->toContain('should eat')
            ->and($text)->not->toContain('avoid this');
    }
});
