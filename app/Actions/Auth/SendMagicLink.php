<?php

namespace App\Actions\Auth;

use App\Models\LoginToken;
use App\Models\User;
use App\Notifications\MagicLinkNotification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class SendMagicLink
{
    public function handle(string $email): void
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
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

        $user->notify(new MagicLinkNotification($url));

        if (! app()->isProduction()) {
            session()->flash('dev_login_url', $url);
        }
    }
}
