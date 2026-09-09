<?php

namespace App\Support;

use DateTimeZone;
use Illuminate\Http\Request;

final class SystemTimezone
{
    public static function detected(?Request $request = null): ?string
    {
        $request ??= request();

        if ($request === null) {
            return null;
        }

        foreach ([
            $request->header('X-Timezone'),
            $request->input('detected_timezone'),
            $request->cookie('system_timezone'),
            $request->hasSession() ? $request->session()->get('system_timezone') : null,
        ] as $value) {
            if (self::isValid($value)) {
                return $value;
            }
        }

        return null;
    }

    public static function isValid(mixed $value): bool
    {
        return is_string($value)
            && $value !== ''
            && in_array($value, DateTimeZone::listIdentifiers(DateTimeZone::ALL), true);
    }
}
