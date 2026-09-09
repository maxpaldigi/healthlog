<?php

namespace App\Enums;

enum ReadingTag: string
{
    case None = 'none';
    case BeforeMeds = 'before_meds';
    case AfterMeds = 'after_meds';
    case AfterExercise = 'after_exercise';
    case Stressed = 'stressed';

    public function label(): string
    {
        return match ($this) {
            self::None => 'None',
            self::BeforeMeds => 'Before meds',
            self::AfterMeds => 'After meds',
            self::AfterExercise => 'After exercise',
            self::Stressed => 'Stressed',
        };
    }
}
