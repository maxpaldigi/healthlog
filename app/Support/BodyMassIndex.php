<?php

namespace App\Support;

final class BodyMassIndex
{
    /**
     * kg / m² from the stored height and weight.
     * Display only — never a diagnosis.
     */
    public static function format(?string $kg, ?string $cm): ?string
    {
        if ($kg === null || $kg === '' || $cm === null || $cm === '') {
            return null;
        }

        if (bccomp($cm, '0', 2) <= 0) {
            return null;
        }

        $metres = bcdiv($cm, '100', 4);
        $squared = bcmul($metres, $metres, 6);

        if (bccomp($squared, '0', 6) <= 0) {
            return null;
        }

        return bcadd(bcdiv($kg, $squared, 4), '0.05', 1);
    }
}
