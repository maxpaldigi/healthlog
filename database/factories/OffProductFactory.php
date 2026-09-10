<?php

namespace Database\Factories;

use App\Models\OffProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OffProduct>
 */
class OffProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'barcode' => fake()->unique()->numerify('#############'),
            'name' => 'Basmati rice',
            'brands' => null,
            'sodium_mg_100g' => '5.00',
            'sugars_g_100g' => '0.20',
            'carbs_g_100g' => '77.00',
            'nutriscore' => 'a',
            'serving_g' => '150.00',
            'fetched_at' => now(),
        ];
    }
}
