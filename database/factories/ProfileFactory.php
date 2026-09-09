<?php

namespace Database\Factories;

use App\Enums\GlucoseUnit;
use App\Enums\WeightUnit;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'date_of_birth' => fake()->optional()->date(),
            'timezone' => 'Asia/Kolkata',
            'glucose_unit' => GlucoseUnit::MgDl,
            'weight_unit' => WeightUnit::Kg,
            'is_default' => true,
        ];
    }
}
