<?php

namespace App\Actions\Readings;

use App\Contracts\ExtractsDeviceReading;
use App\Exceptions\CouldNotReadPhotoException;
use App\Support\ExtractedReading;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use JsonException;

class ExtractReadingFromGemini implements ExtractsDeviceReading
{
    public function handle(string $absolutePath, string $mediaType, ?string $expect = null): ExtractedReading
    {
        $apiKey = config('services.gemini.key');

        if (blank($apiKey)) {
            throw CouldNotReadPhotoException::make(
                'Photo reading is not set up yet. Please type the numbers.',
            );
        }

        $binary = file_get_contents($absolutePath);

        if ($binary === false || $binary === '') {
            throw CouldNotReadPhotoException::make();
        }

        $model = (string) config('services.gemini.model', 'gemini-2.5-flash');
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent';

        try {
            $response = Http::timeout(45)
                ->acceptJson()
                ->asJson()
                ->withQueryParameters(['key' => $apiKey])
                ->post($url, [
                    'contents' => [[
                        'parts' => [
                            [
                                'inline_data' => [
                                    'mime_type' => $mediaType,
                                    'data' => str_replace(["\r", "\n"], '', base64_encode($binary)),
                                ],
                            ],
                            [
                                'text' => ExtractReadingFromImage::promptFor($expect)
                                    ."\n\nReply with JSON only. Use this shape: "
                                    .'{"device_type":"blood_pressure_monitor|glucometer|unknown","systolic":null,"diastolic":null,"pulse":null,"glucose":null,"glucose_unit_on_screen":null,"confidence":"high|medium|low","notes":""}',
                            ],
                        ],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0,
                        'responseMimeType' => 'application/json',
                    ],
                ]);
        } catch (ConnectionException $exception) {
            report($exception);

            throw CouldNotReadPhotoException::make();
        }

        if ($response->failed()) {
            report(new CouldNotReadPhotoException(
                'Gemini photo read failed: '.$response->status().' '.$response->body(),
            ));

            throw CouldNotReadPhotoException::make();
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (! is_string($text) || trim($text) === '') {
            throw CouldNotReadPhotoException::make();
        }

        $payload = $this->decodeJson($text);

        if ($payload === null) {
            throw CouldNotReadPhotoException::make();
        }

        return ExtractedReading::fromArray($payload);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(string $text): ?array
    {
        $trimmed = trim($text);
        $trimmed = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $trimmed) ?? $trimmed;

        try {
            $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            report($exception);

            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }
}
