<?php

use App\Support\BloodPressureColor;

it('uses plain language for out-of-range blood pressure', function () {
    expect(BloodPressureColor::label(118, 76))->toBeNull()
        ->and(BloodPressureColor::label(124, 76))->toBe('Raised')
        ->and(BloodPressureColor::label(132, 84))->toBe('Raised')
        ->and(BloodPressureColor::label(148, 92))->toBe('High')
        ->and(BloodPressureColor::label(184, 122))->toBe('Check with your clinic');
});
