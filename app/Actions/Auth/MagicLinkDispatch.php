<?php

namespace App\Actions\Auth;

/**
 * Result of attempting to send a magic link. Always safe to return to clients —
 * never indicates whether the email exists.
 */
final class MagicLinkDispatch
{
    public function __construct(
        public readonly ?string $devLoginUrl = null,
        public readonly bool $mailFailed = false,
    ) {}
}
