<?php

namespace Database\Factories;

use App\Enums\ProfileRole;
use App\Models\Profile;
use App\Models\ProfileMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfileMember>
 */
class ProfileMemberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::factory();

        return [
            'profile_id' => Profile::factory(),
            'user_id' => $user,
            'email' => fake()->unique()->safeEmail(),
            'role' => ProfileRole::Caregiver,
            'invited_at' => now(),
            'accepted_at' => now(),
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (): array => [
            'role' => ProfileRole::Owner,
            'accepted_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'user_id' => null,
            'accepted_at' => null,
        ]);
    }
}
