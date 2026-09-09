<?php

namespace App\Enums;

enum GlucoseContext: string
{
    case Fasting = 'fasting';
    case PreMeal = 'pre_meal';
    case PostMeal1h = 'post_meal_1h';
    case PostMeal2h = 'post_meal_2h';
    case Bedtime = 'bedtime';
    case Random = 'random';

    public function label(): string
    {
        return match ($this) {
            self::Fasting => 'Fasting',
            self::PreMeal => 'Before meal',
            self::PostMeal1h => 'After meal 1h',
            self::PostMeal2h => 'After meal 2h',
            self::Bedtime => 'Bedtime',
            self::Random => 'Random',
        };
    }
}
