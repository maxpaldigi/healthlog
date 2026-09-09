<?php

namespace Database\Factories;

use App\Enums\GlucoseContext;
use App\Enums\ReadingSource;
use App\Models\GlucoseReading;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GlucoseReading>
 */
class GlucoseReadingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'value_mg_dl' => fake()->randomFloat(2, 70, 220),
            'context' => fake()->randomElement(GlucoseContext::cases()),
            'measured_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'note' => null,
            'source' => ReadingSource::Manual,
        ];
    }
}
