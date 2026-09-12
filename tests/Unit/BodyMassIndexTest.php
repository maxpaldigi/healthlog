<?php

use App\Support\BodyHeight;
use App\Support\BodyMassIndex;

it('formats bmi from kilograms and centimetres', function () {
    expect(BodyMassIndex::format('70', '175'))->toBe('22.9')
        ->and(BodyMassIndex::format('70', '170'))->toBe('24.2');
});

it('returns nothing without both numbers', function () {
    expect(BodyMassIndex::format(null, '170'))->toBeNull()
        ->and(BodyMassIndex::format('70', null))->toBeNull()
        ->and(BodyMassIndex::format('70', '0'))->toBeNull();
});

it('converts feet and inches to centimetres', function () {
    expect(BodyHeight::fromFeetAndInches('5', '7'))->toBe('170.18')
        ->and(BodyHeight::isWithinStoredRange('170.18'))->toBeTrue()
        ->and(BodyHeight::isWithinStoredRange('40'))->toBeFalse();
});
