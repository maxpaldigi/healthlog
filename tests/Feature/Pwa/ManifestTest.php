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
