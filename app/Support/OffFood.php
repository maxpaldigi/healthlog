<?php

namespace App\Support;

final class OffFood
{
    public function __construct(
        public string $barcode,
        public string $name,
        public ?string $brands,
        public ?string $sodiumMg100g,
        public ?string $sugarsG100g,
        public ?string $carbsG100g,
        public ?string $nutriscore,
        public ?string $servingG,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $flags = array_values(array_filter([
            NutritionFacts::sodiumFlag($this->sodiumMg100g),
            NutritionFacts::sugarsFlag($this->sugarsG100g),
        ]));

        return [
            'barcode' => $this->barcode,
            'name' => $this->name,
            'brands' => $this->brands,
            'sodium_mg_100g' => $this->sodiumMg100g,
            'sugars_g_100g' => $this->sugarsG100g,
            'carbs_g_100g' => $this->carbsG100g,
            'nutriscore' => $this->nutriscore,
            'serving_g' => $this->servingG,
            'flags' => $flags,
            'portions' => FoodPortions::presets($this->servingG),
            'attribution' => 'Data from Open Food Facts (ODbL)',
            'who_reference' => NutritionFacts::whoSaltReference(),
        ];
    }
}
