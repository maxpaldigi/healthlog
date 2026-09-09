<?php

namespace App\Support;

final class Timezones
{
    /**
     * @return array<string, string>
     */
    public static function common(): array
    {
        return [
            'Asia/Kolkata' => 'India (Asia/Kolkata)',
            'Asia/Dubai' => 'Dubai (Asia/Dubai)',
            'Asia/Singapore' => 'Singapore (Asia/Singapore)',
            'Asia/Tokyo' => 'Tokyo (Asia/Tokyo)',
            'Europe/London' => 'London (Europe/London)',
            'Europe/Paris' => 'Paris (Europe/Paris)',
            'America/New_York' => 'New York (America/New_York)',
            'America/Chicago' => 'Chicago (America/Chicago)',
            'America/Los_Angeles' => 'Los Angeles (America/Los_Angeles)',
            'America/Toronto' => 'Toronto (America/Toronto)',
            'Australia/Sydney' => 'Sydney (Australia/Sydney)',
            'UTC' => 'UTC',
        ];
    }
}
