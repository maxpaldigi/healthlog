<?php

it('ships a standalone web app manifest with icons', function () {
    $path = public_path('manifest.json');
    $manifest = json_decode((string) file_get_contents($path), true);

    expect($manifest)->toBeArray()
        ->and($manifest['name'])->toBe('HealthyMe')
        ->and($manifest['short_name'])->toBe('HealthyMe')
        ->and($manifest['display'])->toBe('standalone')
        ->and($manifest['start_url'])->toBe('/')
        ->and($manifest['theme_color'])->toBe('#2A3B8F')
        ->and(file_exists(public_path('icon-192.png')))->toBeTrue()
        ->and(file_exists(public_path('icon-512.png')))->toBeTrue()
        ->and(file_exists(public_path('icon-maskable-512.png')))->toBeTrue()
        ->and(file_exists(public_path('sw.js')))->toBeTrue();
});

it('versions the service worker cache so a new deploy replaces the old app', function () {
    $worker = (string) file_get_contents(public_path('sw.js'));
    $template = (string) file_get_contents(resource_path('pwa/sw.js'));

    expect($template)->toContain('healthyme-__CACHE_VERSION__')
        ->and($worker)->not->toContain('__CACHE_VERSION__')
        ->and($worker)->not->toContain('healthyme-v1')
        ->and($worker)->toContain('self.skipWaiting()')
        ->and($worker)->toContain('clients.claim()')
        ->and($worker)->toContain("url.pathname === '/sw.js'");
});
