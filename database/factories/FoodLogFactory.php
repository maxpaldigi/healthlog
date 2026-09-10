<?php

namespace Database\Factories;

use App\Enums\FoodSource;
use App\Models\FoodLog;
use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FoodLog>
 */
class FoodLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'profile_id' => Profile::factory(),
            'source' => FoodSource::Custom,
            'source_ref' => null,
            'label' => 'Roti',
            'portion_g' => '100.00',
            'portion_label' => 'One serving',
            'eaten_at' => now(),
        ];
    }
}
