<?php

use App\Support\FoodPortions;

it('uses named servings when the product has one, otherwise 100 g', function () {
    $withServing = FoodPortions::presets('80');
    $without = FoodPortions::presets(null);

    expect($withServing[2]['grams'])->toBe('80.00')
        ->and($withServing[1]['grams'])->toBe('40.00')
        ->and($without[2]['grams'])->toBe('100.00')
        ->and($without[0]['label'])->toBe('A taste');
});
