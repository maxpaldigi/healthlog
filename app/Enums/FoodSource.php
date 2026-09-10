<?php

namespace App\Enums;

enum FoodSource: string
{
    case OpenFoodFacts = 'open_food_facts';
    case Custom = 'custom';
}
