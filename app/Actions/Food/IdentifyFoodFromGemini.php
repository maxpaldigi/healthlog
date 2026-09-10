<?php

namespace App\Actions\Food;

use App\Contracts\ExtractsFoodFromPhoto;
use App\Exceptions\CouldNotReadPhotoException;
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

    /**
     * @var list<string>
     */
    private const FALLBACK_MODELS = [
        'gemini-2.5-flash',
        'gemini-2.0-flash',
        'gemini-2.5-flash-lite',
        'gemini-flash-latest',
    ];

    public function handle(string $absolutePath, string $mediaType): IdentifiedFood
    {
        $apiKey = $this->apiKey();

        if (blank($apiKey)) {
            throw CouldNotReadPhotoException::make(
                'Photo reading is not set up yet. Search by name or type it.',
            );
        }

        $prepared = $this->prepareImage($absolutePath, $mediaType);
        $lastError = 'Gemini did not name that food.';

        foreach ($this->models() as $model) {
            try {
                $response = $this->request($apiKey, $model, $prepared['data'], $prepared['media_type']);
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

        throw CouldNotReadPhotoException::make($lastError.' Search by name or type it.');
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
            throw CouldNotReadPhotoException::make(
                "Couldn't tell what that food is. Search by name or type it.",
            );
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
