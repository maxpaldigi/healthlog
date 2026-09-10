<?php

use App\Actions\Readings\ExtractReadingFromGemini;
use App\Exceptions\CouldNotReadPhotoException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

it('asks for a Gemini key when photo reading is not set up', function () {
    config(['services.gemini.key' => null]);
    putenv('GEMINI_API_KEY');
    unset($_ENV['GEMINI_API_KEY'], $_SERVER['GEMINI_API_KEY']);

    $photo = UploadedFile::fake()->image('meter.jpg', 80, 80);

    expect(fn () => app(ExtractReadingFromGemini::class)->handle(
        $photo->getRealPath(),
        'image/jpeg',
    ))->toThrow(CouldNotReadPhotoException::class, 'Photo reading is not set up yet');
});

it('reads meter numbers from the free Gemini vision API', function () {
    config(['services.gemini.key' => 'test-gemini-key']);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'device_type' => 'blood_pressure_monitor',
                            'systolic' => 132,
                            'diastolic' => 84,
                            'pulse' => 72,
                            'glucose' => null,
                            'glucose_unit_on_screen' => null,
                            'confidence' => 'high',
                            'notes' => 'Omron LCD',
                        ], JSON_THROW_ON_ERROR),
                    ]],
                ],
            ]],
        ]),
    ]);

    $photo = UploadedFile::fake()->image('meter.jpg', 80, 80);

    $reading = app(ExtractReadingFromGemini::class)->handle(
        $photo->getRealPath(),
        'image/jpeg',
    );

    expect($reading->deviceType)->toBe('blood_pressure_monitor')
        ->and($reading->systolic)->toBe(132)
        ->and($reading->diastolic)->toBe(84)
        ->and($reading->pulse)->toBe(72);

    Http::assertSentCount(1);
});

it('does not walk a long model list after Gemini times out', function () {
    config([
        'services.gemini.key' => 'test-gemini-key',
        'services.gemini.model' => 'gemini-2.5-flash',
    ]);

    $attempts = 0;

    Http::fake(function () use (&$attempts) {
        $attempts++;

        throw new ConnectionException('Operation timed out');
    });

    $photo = UploadedFile::fake()->image('meter.jpg', 80, 80);

    expect(fn () => app(ExtractReadingFromGemini::class)->handle(
        $photo->getRealPath(),
        'image/jpeg',
    ))->toThrow(CouldNotReadPhotoException::class, 'Could not read that photo in time');

    expect($attempts)->toBe(1);
});

it('tries one fallback model when the preferred model is missing', function () {
    config([
        'services.gemini.key' => 'test-gemini-key',
        'services.gemini.model' => 'gemini-2.5-flash',
    ]);

    Http::fake(function (Request $request) {
        if (str_contains($request->url(), 'gemini-2.5-flash')) {
            return Http::response(['error' => ['message' => 'not found']], 404);
        }

        return Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'text' => json_encode([
                            'device_type' => 'glucometer',
                            'systolic' => null,
                            'diastolic' => null,
                            'pulse' => null,
                            'glucose' => 118,
                            'glucose_unit_on_screen' => 'mg/dL',
                            'confidence' => 'high',
                            'notes' => 'Accu-Chek',
                        ], JSON_THROW_ON_ERROR),
                    ]],
                ],
            ]],
        ]);
    });

    $photo = UploadedFile::fake()->image('meter.jpg', 80, 80);

    $reading = app(ExtractReadingFromGemini::class)->handle(
        $photo->getRealPath(),
        'image/jpeg',
    );

    expect($reading->deviceType)->toBe('glucometer')
        ->and($reading->glucose)->toBe('118');

    Http::assertSentCount(2);
});
