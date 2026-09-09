<?php

use App\Enums\WeightUnit;
use App\Support\BodyWeight;

it('converts pounds to kilograms for storage', function () {
    $stored = BodyWeight::toKg('154.32', WeightUnit::Lb);

    expect(BodyWeight::isWithinStoredRange($stored))->toBeTrue()
        ->and(BodyWeight::fromKg($stored, WeightUnit::Lb))->toBe('154.32');
});
