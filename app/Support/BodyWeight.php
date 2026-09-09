<?php

namespace App\Support;

use App\Enums\WeightUnit;

final class BodyWeight
{
    public const LB_PER_KG = '2.20462262';

    public static function toKg(string $value, WeightUnit $unit): string
    {
        if ($unit === WeightUnit::Kg) {
            return self::scale($value);
        }

        return self::scale(bcdiv($value, self::LB_PER_KG, 4));
    }

    public static function fromKg(string $kg, WeightUnit $unit): string
    {
        if ($unit === WeightUnit::Kg) {
            return self::scale($kg);
        }

        return self::scale(bcmul($kg, self::LB_PER_KG, 4));
    }

    public static function isWithinStoredRange(string $kg): bool
    {
        return bccomp($kg, '10', 2) >= 0 && bccomp($kg, '400', 2) <= 0;
    }

    private static function scale(string $value): string
    {
        $adjustment = bccomp($value, '0', 8) >= 0 ? '0.005' : '-0.005';

        return bcadd($value, $adjustment, 2);
    }
}
