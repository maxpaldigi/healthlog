<?php

use App\Support\Timezones;

it('lists each country once without city names', function () {
    $zones = Timezones::common();

    expect($zones)->toHaveKey('Asia/Kolkata')
        ->and($zones['Asia/Kolkata'])->toBe('India')
        ->and($zones['Europe/London'])->toBe('United Kingdom')
        ->and($zones['Africa/Lagos'])->toBe('Nigeria')
        ->and($zones['America/Sao_Paulo'])->toBe('Brazil')
        ->and($zones)->not->toHaveKey('UTC')
        ->and(count($zones))->toBeGreaterThan(200)
        ->and(count($zones))->toBe(count(array_unique(array_values($zones))));

    foreach ($zones as $id => $label) {
        expect($label)->not->toContain('—')
            ->and($label)->not->toContain('Kolkata')
            ->and($label)->not->toContain('London')
            ->and(timezone_open($id))->not->toBeFalse();
    }
});

it('maps a city time zone to that country option', function () {
    expect(Timezones::aliases()['America/Los_Angeles'])->toBe('America/New_York')
        ->and(Timezones::aliases()['Europe/London'])->toBe('Europe/London');
});
