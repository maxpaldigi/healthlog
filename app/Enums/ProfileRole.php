<?php

namespace App\Enums;

enum ProfileRole: string
{
    case Owner = 'owner';
    case Caregiver = 'caregiver';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Caregiver => 'Helper',
        };
    }
}
