<?php

use App\Models\Profile;
use App\Support\GlucoseRange;

it('flags sugar only against the persons own targets', function () {
    $profile = new Profile([
        'glucose_low_mg_dl' => '70.00',
        'glucose_high_mg_dl' => '180.00',
    ]);

    expect(GlucoseRange::label($profile, '95.00'))->toBeNull()
        ->and(GlucoseRange::label($profile, '64.00'))->toBe('Low')
        ->and(GlucoseRange::label($profile, '210.00'))->toBe('High');
});

it('does not flag sugar when no targets are set', function () {
    $profile = new Profile;

    expect(GlucoseRange::label($profile, '240.00'))->toBeNull();
});
