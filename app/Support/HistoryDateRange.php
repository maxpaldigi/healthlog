<?php

namespace App\Support;

use App\Models\Profile;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class HistoryDateRange
{
    /**
     * @return array{
     *     from: CarbonInterface,
     *     to: CarbonInterface,
     *     filters: array<string, mixed>,
     *     presets: list<array<string, mixed>>,
     *     month: array<string, mixed>
     * }
     */
    public static function make(Profile $profile, ?string $from, ?string $to): array
    {
        $today = $profile->nowInTimezone()->startOfDay();
        $earliest = $today->copy()->subYears(2)->startOfDay();

        $fromLocal = $from !== null
            ? Carbon::parse($from, $profile->clockTimezone())->startOfDay()
            : $today->copy()->subDays(30);
        $toLocal = $to !== null
            ? Carbon::parse($to, $profile->clockTimezone())->startOfDay()
            : $today->copy();

        if ($fromLocal->greaterThan($toLocal)) {
            [$fromLocal, $toLocal] = [$toLocal->copy(), $fromLocal->copy()];
        }

        if ($fromLocal->lessThan($earliest)) {
            $fromLocal = $earliest->copy();
        }

        if ($toLocal->greaterThan($today)) {
            $toLocal = $today->copy();
        }

        $presets = self::presets($today);
        $preset = null;

        foreach ($presets as $option) {
            if ($option['from'] === $fromLocal->toDateString() && $option['to'] === $toLocal->toDateString()) {
                $preset = $option['key'];
                break;
            }
        }

        return [
            'from' => $fromLocal->copy()->startOfDay()->utc(),
            'to' => $toLocal->copy()->endOfDay()->utc(),
            'filters' => [
                'from' => $fromLocal->toDateString(),
                'to' => $toLocal->toDateString(),
                'from_label' => self::shortLabel($fromLocal, $today),
                'to_label' => self::shortLabel($toLocal, $today),
                'range_label' => self::rangeLabel($fromLocal, $toLocal),
                'today' => $today->toDateString(),
                'earliest' => $earliest->toDateString(),
                'preset' => $preset,
            ],
            'presets' => $presets,
            'month' => self::monthNav($today, $fromLocal, $toLocal),
        ];
    }

    /**
     * @return list<array{key: string, label: string, from: string, to: string}>
     */
    public static function presets(CarbonInterface $today): array
    {
        $today = $today->copy()->startOfDay();

        return [
            [
                'key' => '7d',
                'label' => '7 days',
                'from' => $today->copy()->subDays(6)->toDateString(),
                'to' => $today->toDateString(),
            ],
            [
                'key' => '30d',
                'label' => '30 days',
                'from' => $today->copy()->subDays(30)->toDateString(),
                'to' => $today->toDateString(),
            ],
            [
                'key' => 'month',
                'label' => 'This month',
                'from' => $today->copy()->startOfMonth()->toDateString(),
                'to' => $today->toDateString(),
            ],
        ];
    }

    /**
     * @return array{label: string, prev_label: string, next_label: string, prev_from: string, prev_to: string, next_from: string, next_to: string, can_next: bool}
     */
    public static function monthNav(CarbonInterface $today, CarbonInterface $from, CarbonInterface $to): array
    {
        $today = $today->copy()->startOfDay();
        $focus = $from->copy()->startOfMonth();

        if (! $from->isSameMonth($to)) {
            $focus = $to->copy()->startOfMonth();
        }

        $previous = $focus->copy()->subMonthNoOverflow();
        $next = $focus->copy()->addMonthNoOverflow();

        return [
            'label' => $focus->format('F Y'),
            'prev_label' => $previous->format('M'),
            'next_label' => $next->format('M'),
            'prev_from' => $previous->copy()->startOfMonth()->toDateString(),
            'prev_to' => $previous->copy()->endOfMonth()->toDateString(),
            'next_from' => $next->copy()->startOfMonth()->toDateString(),
            'next_to' => $next->copy()->endOfMonth()->greaterThan($today)
                ? $today->toDateString()
                : $next->copy()->endOfMonth()->toDateString(),
            'can_next' => $next->lessThanOrEqualTo($today),
        ];
    }

    public static function rangeLabel(CarbonInterface $from, CarbonInterface $to): string
    {
        if ($from->isSameDay($to)) {
            return $from->format('j F Y');
        }

        if ($from->isSameYear($to)) {
            return $from->format('j F').' to '.$to->format('j F Y');
        }

        return $from->format('j F Y').' to '.$to->format('j F Y');
    }

    public static function shortLabel(CarbonInterface $date, CarbonInterface $today): string
    {
        return $date->year === $today->year
            ? $date->format('j F')
            : $date->format('j F Y');
    }
}
