<?php

namespace App\Support;

final class DemoAccount
{
    public const EMAIL = 'demo@healthlog.test';

    /**
     * Instant demo login never runs in production, even if the env flag is set.
     * Staging stays off unless APP_ENV is local. Tests may opt in via config.
     */
    public static function instantLoginEnabled(): bool
    {
        if (app()->isProduction()) {
            return false;
        }

        if (! (bool) config('auth.demo_instant_login')) {
            return false;
        }

        return app()->environment(['local', 'testing']);
    }

    public static function matches(string $email): bool
    {
        return strcasecmp(trim($email), self::EMAIL) === 0;
    }

    public static function email(): string
    {
        return self::EMAIL;
    }
}
