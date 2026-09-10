<?php

namespace App\Support;

final class NutritionFacts
{
    /**
     * EU FIC "high salt" mark: 1.5 g salt / 100 g ≈ 600 mg sodium / 100 g.
     * Display only — never a diagnosis.
     */
    public const SODIUM_HIGH_MG_100G = '600';

    /**
     * EU FIC "high sugars" mark: 22.5 g / 100 g.
     * Display only — never a diagnosis.
     */
    public const SUGARS_HIGH_G_100G = '22.5';

    /**
     * Plain-language flag when sodium per 100 g meets the published high mark.
     * Null when missing or unremarkable. Display only — never a diagnosis.
     */
    public static function sodiumFlag(?string $mgPer100g): ?string
    {
        if ($mgPer100g === null || self::isBlank($mgPer100g)) {
            return null;
        }

        if (bccomp($mgPer100g, self::SODIUM_HIGH_MG_100G, 2) < 0) {
            return null;
        }

        return self::display($mgPer100g).' mg sodium per 100 g (EU high-salt mark: 600 mg)';
    }

    /**
     * Plain-language flag when sugars per 100 g meet the published high mark.
     * Null when missing or unremarkable. Display only — never a diagnosis.
     */
    public static function sugarsFlag(?string $gPer100g): ?string
    {
        if ($gPer100g === null || self::isBlank($gPer100g)) {
            return null;
        }

        if (bccomp($gPer100g, self::SUGARS_HIGH_G_100G, 2) < 0) {
            return null;
        }

        return self::display($gPer100g).' g sugars per 100 g (EU high-sugar mark: 22.5 g)';
    }

    public static function whoSaltReference(): string
    {
        return 'WHO reference: under 5 g salt (about 2000 mg sodium) a day.';
    }

    public static function display(string $value): string
    {
        if (! str_contains($value, '.')) {
            return $value;
        }

        return rtrim(rtrim($value, '0'), '.');
    }

    private static function isBlank(string $value): bool
    {
        return bccomp($value, '0', 2) === 0;
    }
}
