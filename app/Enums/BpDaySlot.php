<?php

namespace App\Enums;

use Carbon\CarbonInterface;

enum BpDaySlot: string
{
    case Morning = 'morning';
    case Afternoon = 'afternoon';
    case Evening = 'evening';

    /**
     * Morning 00:00–11:59, afternoon 12:00–16:59, evening 17:00–23:59.
     */
    public static function fromLocalTime(CarbonInterface $local): self
    {
        $hour = $local->hour;

        return match (true) {
            $hour < 12 => self::Morning,
            $hour < 17 => self::Afternoon,
            default => self::Evening,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Morning => 'Morning',
            self::Afternoon => 'Afternoon',
            self::Evening => 'Evening',
        };
    }

    /**
     * @return list<self>
     */
    public static function dayOrder(): array
    {
        return [self::Morning, self::Afternoon, self::Evening];
    }
}
