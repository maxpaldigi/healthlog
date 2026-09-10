<?php

namespace App\Support;

use App\Models\Profile;

final class GlucoseRange
{
    /**
     * Plain-language flag against the active targets on the profile
     * (the person's own, or clinic-set numbers saved onto the profile).
     * Null when no targets are set or the value is inside them.
     * Display only — never a diagnosis.
     */
    public static function label(Profile $profile, string $mgDl): ?string
    {
        $low = $profile->glucose_low_mg_dl;
        $high = $profile->glucose_high_mg_dl;

        if ($low !== null && bccomp($mgDl, (string) $low, 2) < 0) {
            return 'Low';
        }

        if ($high !== null && bccomp($mgDl, (string) $high, 2) > 0) {
            return 'High';
        }

        return null;
    }
}
