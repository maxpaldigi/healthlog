<?php

namespace App\Support;

use App\Models\User;
use InvalidArgumentException;

final class SuperAdmin
{
    public static function email(): string
    {
        $email = strtolower(trim((string) config('auth.super_admin_email')));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('A valid SUPER_ADMIN_EMAIL is required.');
        }

        return $email;
    }

    public static function matches(User|string $user): bool
    {
        $email = $user instanceof User ? $user->email : $user;

        return strcasecmp(trim($email), self::email()) === 0;
    }

    public static function is(User $user): bool
    {
        return $user->is_super_admin === true;
    }
}
