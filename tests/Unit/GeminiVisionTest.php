<?php

use App\Support\GeminiVision;

it('uses the configured model then current flash aliases', function () {
    config(['services.gemini.model' => 'gemini-2.5-flash']);

    expect(GeminiVision::models())->toBe([
        'gemini-2.5-flash',
        'gemini-flash-latest',
        'gemini-3.5-flash-lite',
    ]);
});

it('does not duplicate an alias that is already preferred', function () {
    config(['services.gemini.model' => 'gemini-flash-latest']);

    expect(GeminiVision::models())->toBe([
        'gemini-flash-latest',
        'gemini-3.5-flash-lite',
    ]);
});
