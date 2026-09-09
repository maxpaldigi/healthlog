<?php

namespace App\Actions\Auth;

use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConsumeMagicLink
{
    public function handle(Request $request, string $plainToken): ?User
    {
        if (! $request->hasValidSignature()) {
            return null;
        }

        return DB::transaction(function () use ($request, $plainToken): ?User {
            $loginToken = LoginToken::query()
                ->where('token_hash', LoginToken::hash($plainToken))
                ->whereNull('consumed_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($loginToken === null) {
                return null;
            }

            $loginToken->update(['consumed_at' => now()]);

            $user = $loginToken->user;

            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            Auth::login($user, remember: true);
            $request->session()->regenerate();

            return $user;
        });
    }
}
