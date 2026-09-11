<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Support\SuperAdmin;
use Illuminate\Support\Facades\DB;

class EnsureSuperAdmin
{
    public function handle(): User
    {
        $email = SuperAdmin::email();

        return DB::transaction(function () use ($email): User {
            $user = User::query()
                ->whereRaw('lower(email) = ?', [$email])
                ->first();

            if ($user === null) {
                $user = User::query()->create([
                    'name' => 'Max Pal',
                    'email' => $email,
                    'password' => null,
                ]);
            }

            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()]);
            }

            $user->forceFill(['is_super_admin' => true])->save();

            User::query()
                ->where('id', '!=', $user->id)
                ->where('is_super_admin', true)
                ->update(['is_super_admin' => false]);

            return $user->fresh() ?? $user;
        });
    }
}
