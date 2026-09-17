<?php

namespace App\Actions\Auth;

use App\Models\LoginToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class IssueApiTokenFromMagicLink
{
    /**
     * Consume a one-time login token and issue a Sanctum API token.
     * Signature is not required: possession of the 64-char secret is enough.
     *
     * @return array{user: User, token: string}|null
     */
    public function handle(string $plainToken, string $deviceName = 'flutter'): ?array
    {
        return DB::transaction(function () use ($plainToken, $deviceName): ?array {
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

            $token = $user->createToken($deviceName)->plainTextToken;

            return [
                'user' => $user->fresh(),
                'token' => $token,
            ];
        });
    }
}
