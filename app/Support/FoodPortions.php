<?php

namespace App\Support;

final class FoodPortions
{
    /**
     * Presets in grams. Prefer a named serving from the product when present.
     *
     * @return list<array{key: string, label: string, grams: string}>
     */
    public static function presets(?string $servingG): array
    {
        $serving = self::usableServing($servingG);

        return [
            ['key' => 'taste', 'label' => 'A taste', 'grams' => '30.00'],
            ['key' => 'half', 'label' => 'Half a serving', 'grams' => bcmul($serving, '0.5', 2)],
            ['key' => 'serving', 'label' => 'One serving', 'grams' => $serving],
            ['key' => 'bowl', 'label' => 'A bowl', 'grams' => '200.00'],
            ['key' => 'plate', 'label' => 'A plate', 'grams' => '300.00'],
        ];
    }

    private static function usableServing(?string $servingG): string
    {
        if ($servingG === null || bccomp($servingG, '1', 2) < 0) {
            return '100.00';
        }

        if (bccomp($servingG, '2000', 2) > 0) {
            return '100.00';
        }

        return bcadd($servingG, '0', 2);
    }
}
