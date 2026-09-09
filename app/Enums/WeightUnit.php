<?php

namespace App\Enums;

enum WeightUnit: string
{
    case Kg = 'kg';
    case Lb = 'lb';

    public function label(): string
    {
        return match ($this) {
            self::Kg => 'kg',
            self::Lb => 'lb',
        };
    }
}
