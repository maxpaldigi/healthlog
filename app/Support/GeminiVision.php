<?php

namespace App\Support;

final class GeminiVision
{
    public const TIMEOUT_SECONDS = 18;

    public const CONNECT_TIMEOUT_SECONDS = 5;

    /**
     * 404s are instant; timeouts are not. Prefer aliases that still accept
     * generateContent for current Google AI Studio keys.
     *
     * @var list<string>
     */
    private const FALLBACKS = [
        'gemini-flash-latest',
        'gemini-3.5-flash-lite',
    ];

    /**
     * @return list<string>
     */
    public static function models(): array
    {
        $preferred = config('services.gemini.model') ?: 'gemini-flash-latest';

        return array_values(array_unique([
            (string) $preferred,
            ...self::FALLBACKS,
        ]));
    }

    public static function apiKey(): ?string
    {
        $key = config('services.gemini.key') ?: self::runtime('GEMINI_API_KEY');

        return filled($key) ? (string) $key : null;
    }

    private static function runtime(string $name): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
