<?php

namespace App\Actions\Auth;

use App\Enums\ConsentType;
use App\Enums\GlucoseUnit;
use App\Enums\WeightUnit;
use App\Models\Profile;
use App\Models\User;
use App\Support\SystemTimezone;
use Illuminate\Support\Facades\DB;

class RegisterUser
{
    /**
     * @param  array{name: string, email: string, timezone?: string|null, detected_timezone?: string|null, glucose_unit: string, weight_unit: string}  $data
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
                'timezone' => $this->timezone($data),
                'glucose_unit' => GlucoseUnit::from($data['glucose_unit']),
                'weight_unit' => WeightUnit::from($data['weight_unit']),
                'is_default' => true,
            ]);

            app(RecordConsent::class)->handle($user, ConsentType::HealthData);

            return $user;
        });
    }

    /**
     * @param  array{timezone?: string|null, detected_timezone?: string|null}  $data
     */
    private function timezone(array $data): string
    {
        foreach ([$data['detected_timezone'] ?? null, $data['timezone'] ?? null, SystemTimezone::detected()] as $value) {
            if (is_string($value) && SystemTimezone::isValid($value)) {
                return $value;
            }
        }

        return (string) config('app.timezone');
    }
}
