<?php

use App\Support\BloodPressureColor;
use App\Support\Contrast;

it('measures contrast between two colours', function () {
    expect(Contrast::ratio('#000000', '#ffffff'))->toBe(21.0)
        ->and(Contrast::meetsTextAa('#ffffff', '#000000'))->toBeTrue()
        ->and(Contrast::meetsTextAa('#bbbbbb', '#ffffff'))->toBeFalse();
});

it('meets WCAG AA text contrast for every blood pressure colour token', function () {
    foreach (BloodPressureColor::tokens() as $token) {
        expect(Contrast::ratio($token['foreground'], $token['background']))
            ->toBeGreaterThanOrEqual(4.5);
    }
});
