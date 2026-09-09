<?php

use App\Enums\BpDaySlot;
use Illuminate\Support\Carbon;

it('assigns blood pressure slots from local time', function (string $time, BpDaySlot $expected) {
    $local = Carbon::parse("2026-09-08 {$time}", 'Asia/Kolkata');

    expect(BpDaySlot::fromLocalTime($local))->toBe($expected);
})->with([
    'midnight morning' => ['00:00:00', BpDaySlot::Morning],
    'late morning' => ['11:59:00', BpDaySlot::Morning],
    'noon afternoon' => ['12:00:00', BpDaySlot::Afternoon],
    'late afternoon' => ['16:59:00', BpDaySlot::Afternoon],
    'early evening' => ['17:00:00', BpDaySlot::Evening],
    'night' => ['23:30:00', BpDaySlot::Evening],
]);
