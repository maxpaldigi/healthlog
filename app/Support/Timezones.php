<?php

namespace App\Support;

use DateTimeZone;
use Locale;

final class Timezones
{
    /**
     * One zone per country when that country spans several clocks.
     *
     * @var array<string, string>
     */
    private const PREFERRED = [
        'AR' => 'America/Argentina/Buenos_Aires',
        'AU' => 'Australia/Sydney',
        'BR' => 'America/Sao_Paulo',
        'CA' => 'America/Toronto',
        'CD' => 'Africa/Kinshasa',
        'CL' => 'America/Santiago',
        'CN' => 'Asia/Shanghai',
        'EC' => 'America/Guayaquil',
        'ES' => 'Europe/Madrid',
        'ID' => 'Asia/Jakarta',
        'KZ' => 'Asia/Almaty',
        'MX' => 'America/Mexico_City',
        'MY' => 'Asia/Kuala_Lumpur',
        'NZ' => 'Pacific/Auckland',
        'PF' => 'Pacific/Tahiti',
        'PT' => 'Europe/Lisbon',
        'RU' => 'Europe/Moscow',
        'UA' => 'Europe/Kyiv',
        'US' => 'America/New_York',
    ];

    /**
     * Every country, labeled with the country name only.
     *
     * @return array<string, string>
     */
    public static function common(?string $include = null): array
    {
        $byCountry = [];

        foreach (DateTimeZone::listIdentifiers(DateTimeZone::ALL) as $id) {
            if (self::shouldSkip($id)) {
                continue;
            }

            $code = self::countryCode($id);

            if ($code === null) {
                continue;
            }

            $byCountry[$code][] = $id;
        }

        $zones = [];

        foreach ($byCountry as $code => $ids) {
            $preferred = self::PREFERRED[$code] ?? null;
            $id = $preferred !== null && in_array($preferred, $ids, true)
                ? $preferred
                : $ids[0];

            $zones[$id] = self::countryNameFromCode($code);
        }

        if ($include !== null && $include !== '' && ! isset($zones[$include])) {
            $code = self::countryCode($include);
            $zones[$include] = $code !== null
                ? self::countryNameFromCode($code)
                : $include;
        }

        asort($zones, SORT_NATURAL | SORT_FLAG_CASE);

        return $zones;
    }

    /**
     * Map any IANA zone to the country option we show in the list.
     *
     * @return array<string, string>
     */
    public static function aliases(): array
    {
        $representative = [];

        foreach (array_keys(self::common()) as $id) {
            $code = self::countryCode($id);

            if ($code !== null) {
                $representative[$code] = $id;
            }
        }

        $aliases = [];

        foreach (DateTimeZone::listIdentifiers(DateTimeZone::ALL) as $id) {
            $code = self::countryCode($id);

            if ($code !== null && isset($representative[$code])) {
                $aliases[$id] = $representative[$code];
            }
        }

        return $aliases;
    }

    private static function shouldSkip(string $id): bool
    {
        return $id === 'UTC'
            || str_starts_with($id, 'Etc/')
            || str_starts_with($id, 'GMT')
            || str_starts_with($id, 'Factory');
    }

    private static function countryCode(string $id): ?string
    {
        try {
            $location = (new DateTimeZone($id))->getLocation();
        } catch (\Exception) {
            return null;
        }

        $code = $location['country_code'] ?? null;

        if (! is_string($code) || $code === '' || $code === '??') {
            return null;
        }

        return $code;
    }

    private static function countryNameFromCode(string $code): string
    {
        if (class_exists(Locale::class)) {
            $name = Locale::getDisplayRegion('_'.$code, 'en');

            if (is_string($name) && $name !== '' && $name !== $code) {
                return $name;
            }
        }

        return $code;
    }
}
