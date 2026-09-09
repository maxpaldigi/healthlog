<?php

namespace App\Actions\Auth;

use App\Enums\GlucoseUnit;
use App\Enums\WeightUnit;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterUser
{
    /**
     * @param  array{name: string, email: string, timezone: string, glucose_unit: string, weight_unit: string}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => null,
            ]);

            Profile::create([
                'user_id' => $user->id,
                'name' => $data['name'],
                'timezone' => $data['timezone'],
                'glucose_unit' => GlucoseUnit::from($data['glucose_unit']),
                'weight_unit' => WeightUnit::from($data['weight_unit']),
                'is_default' => true,
            ]);

            return $user;
        });
    }
}
