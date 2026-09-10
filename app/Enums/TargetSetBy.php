<?php

namespace App\Enums;

enum TargetSetBy: string
{
    case Self = 'self';
    case Clinician = 'clinician';
}
