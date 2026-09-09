<?php

namespace App\Support;

use App\Enums\GlucoseUnit;

final class Glucose
{
    public const MMOL_TO_MG_DL = '18.0182';

    /**
     * Convert a glucose value to canonical mg/dL storage.
     */
    public static function toMgDl(string $value, GlucoseUnit $unit): string
    {
        if ($unit === GlucoseUnit::MgDl) {
            return self::scale($value);
        }

        return self::scale(bcmul($value, self::MMOL_TO_MG_DL, 4));
    }

    /**
     * Convert a stored mg/dL value to the given display unit.
     */
    public static function fromMgDl(string $mgDl, GlucoseUnit $unit): string
    {
        if ($unit === GlucoseUnit::MgDl) {
            return self::scale($mgDl);
        }

        return self::scale(bcdiv($mgDl, self::MMOL_TO_MG_DL, 4));
    }

    /**
     * Stored glucose must stay between 20 and 700 mg/dL.
     */
    public static function isWithinStoredRange(string $mgDl): bool
    {
        return bccomp($mgDl, '20', 2) >= 0 && bccomp($mgDl, '700', 2) <= 0;
    }

    private static function scale(string $value): string
    {
        $adjustment = bccomp($value, '0', 8) >= 0 ? '0.005' : '-0.005';

        return bcadd($value, $adjustment, 2);
    }
}
