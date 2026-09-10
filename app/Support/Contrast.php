<?php

namespace App\Support;

final class Contrast
{
    /**
     * WCAG 2 relative-luminance contrast ratio between two #RRGGBB colours.
     */
    public static function ratio(string $hexA, string $hexB): float
    {
        $lighter = max(self::luminance($hexA), self::luminance($hexB));
        $darker = min(self::luminance($hexA), self::luminance($hexB));

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    public static function meetsTextAa(string $foreground, string $background): bool
    {
        return self::ratio($foreground, $background) >= 4.5;
    }

    public static function meetsUiAa(string $foreground, string $background): bool
    {
        return self::ratio($foreground, $background) >= 3.0;
    }

    private static function luminance(string $hex): float
    {
        $rgb = self::rgb($hex);

        return 0.2126 * self::linear($rgb[0])
            + 0.7152 * self::linear($rgb[1])
            + 0.0722 * self::linear($rgb[2]);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private static function rgb(string $hex): array
    {
        $value = strtoupper(ltrim($hex, '#'));

        if (strlen($value) !== 6 || ! ctype_xdigit($value)) {
            throw new \InvalidArgumentException("Expected #RRGGBB, got {$hex}.");
        }

        return [
            hexdec(substr($value, 0, 2)) / 255,
            hexdec(substr($value, 2, 2)) / 255,
            hexdec(substr($value, 4, 2)) / 255,
        ];
    }

    private static function linear(float $channel): float
    {
        if ($channel <= 0.04045) {
            return $channel / 12.92;
        }

        return (($channel + 0.055) / 1.055) ** 2.4;
    }
}
