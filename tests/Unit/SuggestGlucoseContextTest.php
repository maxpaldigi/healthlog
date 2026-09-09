<?php

use App\Enums\GlucoseContext;
use App\Support\SuggestGlucoseContext;
use Illuminate\Support\Carbon;

it('pre-selects glucose context from local time', function (string $time, GlucoseContext $expected) {
    $local = Carbon::parse("2026-09-08 {$time}", 'Asia/Kolkata');

    expect(SuggestGlucoseContext::forLocalTime($local))->toBe($expected);
})->with([
    'early morning fasting' => ['07:30:00', GlucoseContext::Fasting],
    'late fasting window' => ['09:59:00', GlucoseContext::Fasting],
    'midday after meal' => ['13:00:00', GlucoseContext::PostMeal2h],
    'evening after meal' => ['20:30:00', GlucoseContext::PostMeal2h],
    'late evening bedtime' => ['22:15:00', GlucoseContext::Bedtime],
    'late morning random' => ['11:00:00', GlucoseContext::Random],
]);
