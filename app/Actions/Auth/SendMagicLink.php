<?php

namespace App\Actions\Auth;

use App\Models\LoginToken;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class SendMagicLink
{
    public function handle(string $email): void
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            // Same work as a hit so missing accounts are not cheaper to probe.
            LoginToken::hash(Str::random(64));

            return;
        }

        $user->loginTokens()
            ->whereNull('consumed_at')
            ->delete();

        $plainToken = Str::random(64);

        $user->loginTokens()->create([
            'token_hash' => LoginToken::hash($plainToken),
            'expires_at' => now()->addMinutes(15),
        ]);

        $url = URL::temporarySignedRoute(
            'login.magic',
            now()->addMinutes(15),
            ['token' => $plainToken],
        );

        $sent = true;

        try {
            $user->notify(new MagicLinkNotification($url));
        } catch (Throwable $e) {
            report($e);
            $sent = false;
            session()->flash('mail_failed', true);
        }

        $sendsRealMail = ! in_array(config('mail.default'), ['log', 'array'], true);

        if (! $sent || ! $sendsRealMail) {
            session()->flash('dev_login_url', $url);
        }
    }
}
