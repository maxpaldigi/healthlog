<?php

namespace Database\Factories;

use App\Enums\ConsentType;
use App\Models\Consent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consent>
 */
class ConsentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => ConsentType::HealthData,
            'granted_at' => now(),
            'revoked_at' => null,
            'ip_address' => '127.0.0.1',
            'policy_version' => Consent::POLICY_VERSION,
        ];
    }
}
