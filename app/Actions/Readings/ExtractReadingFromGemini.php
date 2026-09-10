<?php

namespace App\Actions\Readings;

use App\Contracts\ExtractsDeviceReading;
use App\Exceptions\CouldNotReadPhotoException;
use App\Support\DownsizeVisionImage;
use App\Support\ExtractedReading;
use App\Support\GeminiVision;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;

class ExtractReadingFromGemini implements ExtractsDeviceReading
{
    public function handle(string $absolutePath, string $mediaType, ?string $expect = null): ExtractedReading
    {
        $apiKey = GeminiVision::apiKey();

        if (blank($apiKey)) {
            throw CouldNotReadPhotoException::make(
                'Photo reading is not set up yet. Please type the numbers.',
            );
        }

        $prepared = $this->prepareImage($absolutePath, $mediaType);
        $lastError = 'Gemini did not return meter numbers.';

        foreach (GeminiVision::models() as $model) {
            try {
                $response = $this->request($apiKey, $model, $prepared['data'], $prepared['media_type'], $expect);
            } catch (ConnectionException $exception) {
                report($exception);

                throw CouldNotReadPhotoException::make(
                    'Could not read that photo in time. Please type the numbers.',
                );
            }

            if ($response->status() === 404) {
                Log::warning('Gemini model was not found', ['model' => $model]);

                continue;
            }

            if ($response->failed()) {
                Log::error('Gemini photo read failed', [
                    'model' => $model,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);
                $lastError = 'Gemini rejected the photo ('.$response->status().').';

                continue;
            }

            $text = $this->responseText($response->json());

            if ($text === null) {
                Log::error('Gemini photo read had no text', [
                    'model' => $model,
                    'finish_reason' => data_get($response->json(), 'candidates.0.finishReason'),
                    'block_reason' => data_get($response->json(), 'promptFeedback.blockReason'),
                ]);
                $lastError = 'Gemini blocked or skipped that photo.';

                continue;
            }

            $payload = $this->decodeJson($text);

            if ($payload === null) {
                $lastError = 'Gemini returned unreadable meter data.';

                continue;
            }

            return ExtractedReading::fromArray($payload);
        }

        Log::error('Gemini photo read exhausted models', ['last_error' => $lastError]);

        throw CouldNotReadPhotoException::make(
            "Couldn't read that photo. Please type the numbers.",
        );
    }

    /**
     * @return array{data: string, media_type: string}
     */
    private function prepareImage(string $absolutePath, string $mediaType): array
    {
        $binary = file_get_contents($absolutePath);

        if ($binary === false || $binary === '') {
            throw CouldNotReadPhotoException::make();
        }

        $resized = DownsizeVisionImage::handle($binary, $mediaType);

        return [
            'data' => str_replace(["\r", "\n"], '', base64_encode($resized['binary'])),
            'media_type' => $resized['media_type'],
        ];
    }

    private function request(
        string $apiKey,
        string $model,
        string $data,
        string $mediaType,
        ?string $expect,
    ): Response {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent';

        return Http::connectTimeout(GeminiVision::CONNECT_TIMEOUT_SECONDS)
            ->timeout(GeminiVision::TIMEOUT_SECONDS)
            ->acceptJson()
            ->asJson()
            ->withHeaders(['x-goog-api-key' => $apiKey])
            ->post($url, [
                'contents' => [[
                    'parts' => [
                        [
                            'inlineData' => [
                                'mimeType' => $mediaType,
                                'data' => $data,
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
    }

    /**
     * @param  array<string, mixed>|null  $json
     */
    private function responseText(mixed $json): ?string
    {
        $text = data_get($json, 'candidates.0.content.parts.0.text');

        return is_string($text) && trim($text) !== '' ? $text : null;
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
