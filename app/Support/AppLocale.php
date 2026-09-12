<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

final class AppLocale
{
    public const COOKIE = 'locale';

    /**
     * @return array<string, string>
     */
    public static function enabled(): array
    {
        /** @var array<string, string> $enabled */
        $enabled = config('locales.enabled', ['en' => 'English']);

        return $enabled;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::enabled() as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $options;
    }

    public static function isEnabled(?string $locale): bool
    {
        return is_string($locale) && array_key_exists($locale, self::enabled());
    }

    public static function fallback(): string
    {
        $fallback = (string) config('app.locale', 'en');

        return self::isEnabled($fallback) ? $fallback : 'en';
    }

    public static function resolve(Request $request): string
    {
        $user = $request->user();

        if ($user instanceof User && self::isEnabled($user->locale)) {
            return (string) $user->locale;
        }

        $fromCookie = $request->cookie(self::COOKIE);

        if (is_string($fromCookie) && self::isEnabled($fromCookie)) {
            return $fromCookie;
        }

        $fromHeader = $request->getPreferredLanguage(array_keys(self::enabled()));

        if (self::isEnabled($fromHeader)) {
            return $fromHeader;
        }

        return self::fallback();
    }

    /**
     * @return array<string, string>
     */
    public static function catalog(string $locale): array
    {
        $english = self::loadJson('en');

        if ($locale === 'en' || ! self::isEnabled($locale)) {
            return $english;
        }

        return array_merge($english, self::loadJson($locale));
    }

    /**
     * @param  array<string, scalar>  $replace
     */
    public static function get(string $key, array $replace = []): string
    {
        $catalog = self::catalog(app()->getLocale());
        $line = $catalog[$key] ?? $key;

        foreach ($replace as $search => $value) {
            $line = str_replace(':'.$search, (string) $value, $line);
        }

        return $line;
    }

    /**
     * @return array<string, string>
     */
    private static function loadJson(string $locale): array
    {
        $path = lang_path($locale.'.json');

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            return [];
        }

        $catalog = [];

        foreach ($decoded as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $catalog[$key] = $value;
            }
        }

        return $catalog;
    }
}
