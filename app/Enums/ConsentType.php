<?php

namespace App\Enums;

enum ConsentType: string
{
    case HealthData = 'health_data';
    case ShareLink = 'share_link';
}
