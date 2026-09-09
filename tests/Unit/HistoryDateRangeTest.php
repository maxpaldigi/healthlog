<?php

use App\Support\HistoryDateRange;
use Illuminate\Support\Carbon;

it('labels a date range in plain words', function () {
    $from = Carbon::parse('2026-08-09', 'Asia/Kolkata');
    $to = Carbon::parse('2026-09-08', 'Asia/Kolkata');

    expect(HistoryDateRange::rangeLabel($from, $to))->toBe('9 August to 8 September 2026')
        ->and(HistoryDateRange::rangeLabel($to, $to))->toBe('8 September 2026')
        ->and(HistoryDateRange::rangeLabel(
            Carbon::parse('2025-12-20', 'Asia/Kolkata'),
            $to,
        ))->toBe('20 December 2025 to 8 September 2026');
});

it('builds last 7 days, last 30 days, and this month presets', function () {
    $today = Carbon::parse('2026-09-08', 'Asia/Kolkata');
    $presets = collect(HistoryDateRange::presets($today))->keyBy('key');

    expect($presets['7d']['from'])->toBe('2026-09-02')
        ->and($presets['7d']['to'])->toBe('2026-09-08')
        ->and($presets['30d']['from'])->toBe('2026-08-09')
        ->and($presets['30d']['to'])->toBe('2026-09-08')
        ->and($presets['month']['from'])->toBe('2026-09-01')
        ->and($presets['month']['to'])->toBe('2026-09-08');
});

it('names the previous and next months and blocks the future', function () {
    $today = Carbon::parse('2026-09-08', 'Asia/Kolkata');
    $from = Carbon::parse('2026-09-01', 'Asia/Kolkata');
    $nav = HistoryDateRange::monthNav($today, $from, $today);

    expect($nav['label'])->toBe('September 2026')
        ->and($nav['can_next'])->toBeFalse()
        ->and($nav['prev_label'])->toBe('Aug')
        ->and($nav['next_label'])->toBe('Oct')
        ->and($nav['prev_from'])->toBe('2026-08-01')
        ->and($nav['prev_to'])->toBe('2026-08-31');
});
