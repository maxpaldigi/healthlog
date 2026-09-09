<?php

use App\Actions\Readings\ExtractReadingFromGemini;
use App\Exceptions\CouldNotReadPhotoException;
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
});
