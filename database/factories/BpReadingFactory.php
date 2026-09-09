<?php

namespace Database\Factories;

use App\Enums\Arm;
use App\Enums\BodyPosition;
use App\Enums\ReadingSource;
use App\Enums\ReadingTag;
use App\Models\BpReading;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BpReading>
 */
class BpReadingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $diastolic = fake()->numberBetween(60, 95);
        $systolic = fake()->numberBetween($diastolic + 20, min(180, $diastolic + 60));

        return [
            'profile_id' => Profile::factory(),
            'systolic' => $systolic,
            'diastolic' => $diastolic,
            'pulse' => fake()->optional()->numberBetween(55, 100),
            'measured_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'arm' => Arm::Unknown,
            'body_position' => BodyPosition::Sitting,
            'tag' => ReadingTag::None,
            'note' => null,
            'source' => ReadingSource::Manual,
        ];
    }
}
