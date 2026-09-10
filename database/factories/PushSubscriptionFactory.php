<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\PushSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PushSubscription>
 */
class PushSubscriptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'endpoint' => 'https://push.example.test/'.fake()->unique()->uuid(),
            'p256dh' => str_repeat('A', 87),
            'auth' => str_repeat('B', 22),
            'user_agent' => 'PHPUnit',
            'last_used_at' => null,
        ];
    }
}
