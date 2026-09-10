<?php

namespace App\Actions\Food;

use App\Enums\FoodSource;
use App\Models\FoodLog;
use App\Models\Profile;
use App\Support\OpenFoodFacts;
use Illuminate\Support\Carbon;

class StoreFoodLog
{
    public function __construct(private OpenFoodFacts $openFoodFacts) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Profile $profile, array $data): FoodLog
    {
        $source = FoodSource::from((string) $data['source']);
        $portion = bcadd((string) $data['portion_g'], '0', 2);
        $attributes = [
            'source' => $source,
            'portion_g' => $portion,
            'portion_label' => $data['portion_label'],
            'eaten_at' => Carbon::parse($data['eaten_at'], $profile->clockTimezone())->utc(),
            'source_ref' => null,
            'sodium_mg_100g' => null,
            'sugars_g_100g' => null,
            'carbs_g_100g' => null,
            'nutriscore' => null,
        ];

        if ($source === FoodSource::OpenFoodFacts) {
            $food = $this->openFoodFacts->byBarcode((string) ($data['source_ref'] ?? ''));

            if ($food === null) {
                throw new \InvalidArgumentException('That barcode was not found in Open Food Facts.');
            }

            $attributes['source_ref'] = $food->barcode;
            $attributes['label'] = $food->name;
            $attributes['sodium_mg_100g'] = $food->sodiumMg100g;
            $attributes['sugars_g_100g'] = $food->sugarsG100g;
            $attributes['carbs_g_100g'] = $food->carbsG100g;
            $attributes['nutriscore'] = $food->nutriscore;
        } else {
            $label = trim((string) ($data['label'] ?? ''));
            $attributes['label'] = $label !== '' ? mb_substr($label, 0, 180) : 'Food';
        }

        return $profile->foodLogs()->create($attributes);
    }
}
