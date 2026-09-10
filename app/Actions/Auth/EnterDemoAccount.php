<?php

namespace App\Actions\Auth;

use App\Enums\GlucoseUnit;
use App\Enums\WeightUnit;
use App\Models\Profile;
use App\Models\User;
use App\Support\DemoAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EnterDemoAccount
{
    public function handle(): User
    {
        if (! DemoAccount::instantLoginEnabled()) {
            throw new RuntimeException('Demo login is not available.');
        }

        $user = DB::transaction(function (): User {
            $user = User::query()->firstOrCreate(
                ['email' => DemoAccount::EMAIL],
                [
                    'name' => 'Priya Sharma',
                    'password' => null,
                    'email_verified_at' => now(),
                ],
            );

            if ($user->email !== DemoAccount::EMAIL) {
                throw new RuntimeException('Demo login is not available.');
            }

            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            if ($user->defaultProfile === null) {
                Profile::query()->create([
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'date_of_birth' => '1974-03-12',
                    'timezone' => 'Asia/Kolkata',
                    'glucose_unit' => GlucoseUnit::MgDl,
                    'weight_unit' => WeightUnit::Kg,
                    'is_default' => true,
                ]);
            }

            return $user->fresh(['defaultProfile']) ?? $user;
        });

        Auth::login($user, remember: true);

        return $user;
    }
}
