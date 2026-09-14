<?php

it('does not load Google Analytics on this computer by default', function () {
    config(['services.google_analytics.measurement_id' => 'G-LFH02VZ86Y']);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('googletagmanager.com/gtag/js', false)
        ->assertDontSee('analytics-consent', false);
});

it('loads Google Analytics only in production when the id is set', function () {
    $this->app['env'] = 'production';
    config(['services.google_analytics.measurement_id' => 'G-LFH02VZ86Y']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-LFH02VZ86Y', false)
        ->assertSee('analytics_storage: \'denied\'', false)
        ->assertSee('analytics-consent', false)
        ->assertSee('OK, count visits', false);
});

it('uses the HealthyMe measurement id by default', function () {
    expect(config('services.google_analytics.measurement_id'))->toBe('G-LFH02VZ86Y');
});
