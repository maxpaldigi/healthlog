<?php

namespace App\Support;

final class BodyHeight
{
    public const CM_PER_INCH = '2.54';

    public static function fromCentimetres(string $cm): string
    {
        return self::scale($cm);
    }

    public static function fromFeetAndInches(string $feet, string $inches): string
    {
        $totalInches = bcadd(bcmul($feet, '12', 4), $inches, 4);

        return self::scale(bcmul($totalInches, self::CM_PER_INCH, 4));
    }

    public static function isWithinStoredRange(string $cm): bool
    {
        return bccomp($cm, '50', 2) >= 0 && bccomp($cm, '250', 2) <= 0;
    }

    private static function scale(string $value): string
    {
        $adjustment = bccomp($value, '0', 8) >= 0 ? '0.005' : '-0.005';

        return bcadd($value, $adjustment, 2);
    }
}
