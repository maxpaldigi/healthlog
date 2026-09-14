<?php

it('shows the cookie banner on this computer for preview', function () {
    $this->app['env'] = 'local';
    config(['services.google_analytics.measurement_id' => 'G-LFH02VZ86Y']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('cookie-consent', false)
        ->assertSee('Accept analytics cookies', false)
        ->assertSee('Reject analytics cookies', false)
        ->assertDontSee('googletagmanager.com/gtag/js', false);
});

it('loads Consent Mode v2 and a GDPR cookie banner in production', function () {
    $this->app['env'] = 'production';
    config(['services.google_analytics.measurement_id' => 'G-LFH02VZ86Y']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-LFH02VZ86Y', false)
        ->assertSee("gtag('consent', 'default'", false)
        ->assertSee('analytics_storage: \'denied\'', false)
        ->assertSee('ad_storage: \'denied\'', false)
        ->assertSee('ad_user_data: \'denied\'', false)
        ->assertSee('ad_personalization: \'denied\'', false)
        ->assertSee('cookie-consent', false)
        ->assertSee('Accept analytics cookies', false)
        ->assertSee('Reject analytics cookies', false)
        ->assertSee('Privacy notice', false);
});

it('uses the HealthyMe measurement id by default', function () {
    expect(config('services.google_analytics.measurement_id'))->toBe('G-LFH02VZ86Y');
});
