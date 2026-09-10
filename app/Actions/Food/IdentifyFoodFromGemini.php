<?php

namespace App\Actions\Food;

use App\Contracts\ExtractsFoodFromPhoto;
use App\Exceptions\CouldNotReadPhotoException;
use App\Support\DownsizeVisionImage;
use App\Support\GeminiVision;
use App\Support\IdentifiedFood;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JsonException;

class IdentifyFoodFromGemini implements ExtractsFoodFromPhoto
{
    public const PROMPT = <<<'PROMPT'
You are naming food in a photograph for a personal food log. Name only what is visible. Never give health, diet, or medical comments. Never suggest eating or avoiding anything.

Reply with JSON only in this shape:
{"label":"short common name","barcode":null,"confidence":"high"}

Rules:
- label: the food name a shopper would search. If a packet, use the product name printed on it. Otherwise a short common name (apple, roti, plain yogurt). Empty string if you cannot tell.
- barcode: digits from a barcode on packaging if clearly readable, otherwise null.
- confidence: high, medium, or low.
- Do not include nutrition values, serving advice, or extra keys.
PROMPT;

    public function handle(string $absolutePath, string $mediaType): IdentifiedFood
    {
        $apiKey = GeminiVision::apiKey();

        if (blank($apiKey)) {
            throw CouldNotReadPhotoException::make(
                'Photo reading is not set up yet. Search by name or type it.',
            );
        }

        $prepared = $this->prepareImage($absolutePath, $mediaType);
        $lastError = 'Gemini did not name that food.';

        foreach (GeminiVision::models() as $model) {
            try {
                $response = $this->request($apiKey, $model, $prepared['data'], $prepared['media_type']);
            } catch (ConnectionException $exception) {
                report($exception);

                throw CouldNotReadPhotoException::make(
                    'Could not read that photo in time. Search by name or type it.',
                );
            }

            if ($response->status() === 404) {
                Log::warning('Gemini food model was not found', ['model' => $model]);

                continue;
            }

            if ($response->failed()) {
                Log::error('Gemini food photo failed', [
                    'model' => $model,
                    'status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 500),
                ]);
                $lastError = 'Gemini rejected the photo ('.$response->status().').';

                continue;
            }

            $text = $this->responseText($response->json());

            if ($text === null) {
                Log::error('Gemini food photo had no text', [
                    'model' => $model,
                    'finish_reason' => data_get($response->json(), 'candidates.0.finishReason'),
                    'block_reason' => data_get($response->json(), 'promptFeedback.blockReason'),
                ]);
                $lastError = 'Gemini blocked or skipped that photo.';

                continue;
            }

            $payload = $this->decodeJson($text);

            if ($payload === null) {
                $lastError = 'Gemini returned an unreadable food name.';

                continue;
            }

            $identified = IdentifiedFood::fromArray($payload);

            if ($identified->label !== '') {
                return $identified;
            }

            $lastError = 'Gemini could not tell what that food is.';
        }

        Log::error('Gemini food photo exhausted models', ['last_error' => $lastError]);

        throw CouldNotReadPhotoException::make(
            "Couldn't tell what that food is. Search by name or type it.",
        );
    }

    /**
     * @return array{data: string, media_type: string}
     */
    private function prepareImage(string $absolutePath, string $mediaType): array
    {
        $binary = file_get_contents($absolutePath);

        if ($binary === false || $binary === '') {
            throw CouldNotReadPhotoException::make(
                "Couldn't tell what that food is. Search by name or type it.",
            );
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
                            'text' => self::PROMPT,
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
