<?php

namespace App\Actions\Readings;

use App\Contracts\ExtractsDeviceReading;
use App\Exceptions\CouldNotReadPhotoException;
use App\Support\ExtractedReading;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;

class ExtractReadingFromGemini implements ExtractsDeviceReading
{
    /**
     * @var list<string>
     */
    private const FALLBACK_MODELS = [
        'gemini-2.5-flash',
        'gemini-2.0-flash',
        'gemini-2.5-flash-lite',
        'gemini-flash-latest',
    ];

    public function handle(string $absolutePath, string $mediaType, ?string $expect = null): ExtractedReading
    {
        $apiKey = $this->apiKey();

        if (blank($apiKey)) {
            throw CouldNotReadPhotoException::make(
                'Photo reading is not set up yet. Please type the numbers.',
            );
        }

        $prepared = $this->prepareImage($absolutePath, $mediaType);
        $lastError = 'Gemini did not return meter numbers.';

        foreach ($this->models() as $model) {
            try {
                $response = $this->request($apiKey, $model, $prepared['data'], $prepared['media_type'], $expect);
            } catch (ConnectionException $exception) {
                report($exception);
                $lastError = 'Could not reach Gemini.';

                continue;
            }

            if ($response->status() === 404) {
                $lastError = 'Gemini model '.$model.' was not found.';

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

        throw CouldNotReadPhotoException::make($lastError.' Please type the numbers.');
    }

    /**
     * @return list<string>
     */
    private function models(): array
    {
        $preferred = $this->runtime('GEMINI_MODEL')
            ?? config('services.gemini.model')
            ?? 'gemini-2.0-flash';

        return array_values(array_unique([
            (string) $preferred,
            ...self::FALLBACK_MODELS,
        ]));
    }

    private function apiKey(): ?string
    {
        $key = config('services.gemini.key') ?: $this->runtime('GEMINI_API_KEY');

        return filled($key) ? (string) $key : null;
    }

    private function runtime(string $name): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        return is_string($value) && $value !== '' ? $value : null;
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

        $resized = $this->downsizeJpeg($binary, $mediaType);

        return [
            'data' => str_replace(["\r", "\n"], '', base64_encode($resized['binary'])),
            'media_type' => $resized['media_type'],
        ];
    }

    /**
     * @return array{binary: string, media_type: string}
     */
    private function downsizeJpeg(string $binary, string $mediaType): array
    {
        if (! function_exists('imagecreatefromstring') || strlen($binary) < 400_000) {
            return ['binary' => $binary, 'media_type' => $mediaType];
        }

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            return ['binary' => $binary, 'media_type' => $mediaType];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $max = 1280;

        if ($width <= $max && $height <= $max) {
            imagedestroy($image);

            return ['binary' => $binary, 'media_type' => $mediaType];
        }

        $scale = $max / max($width, $height);
        $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale));
        imagedestroy($image);

        if ($resized === false) {
            return ['binary' => $binary, 'media_type' => $mediaType];
        }

        ob_start();
        imagejpeg($resized, null, 82);
        $jpeg = (string) ob_get_clean();
        imagedestroy($resized);

        return ['binary' => $jpeg !== '' ? $jpeg : $binary, 'media_type' => 'image/jpeg'];
    }

    private function request(
        string $apiKey,
        string $model,
        string $data,
        string $mediaType,
        ?string $expect,
    ): Response {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent';

        return Http::timeout(45)
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
