<?php

namespace App\Actions\Readings;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\APITimeoutException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\BadRequestException;
use Anthropic\Core\Exceptions\ConflictException;
use Anthropic\Core\Exceptions\InternalServerException;
use Anthropic\Core\Exceptions\NotFoundException;
use Anthropic\Core\Exceptions\PermissionDeniedException;
use Anthropic\Core\Exceptions\RateLimitException;
use Anthropic\Core\Exceptions\UnprocessableEntityException;
use App\Contracts\ExtractsDeviceReading;
use App\Exceptions\CouldNotReadPhotoException;
use App\Support\ExtractedReading;
use JsonException;

class ExtractReadingFromImage implements ExtractsDeviceReading
{
    public const PROMPT = <<<'PROMPT'
You are reading the digital display of a home medical device from a photograph. Report only what is actually printed on the screen.

If it is a blood pressure monitor, there are usually three numbers: systolic (largest, top), diastolic (middle), and pulse (bottom, often marked with a heart symbol). Systolic is always greater than diastolic.

If it is a glucometer, there is usually a single number, and the unit mg/dL or mmol/L may be printed next to it.

Rules: Do not guess. If a digit is unclear, blurred, cut off, or obscured by glare, set that field to null and set confidence to "low". If the image does not show a medical device display at all, set device_type to "unknown" and every value to null. Never estimate, infer, or complete a partially visible number. Use the notes field to say briefly what you saw or why you could not read it.
PROMPT;

    public function __construct(private Client $client) {}

    public function handle(string $absolutePath, string $mediaType, ?string $expect = null): ExtractedReading
    {
        $binary = file_get_contents($absolutePath);

        if ($binary === false || $binary === '') {
            throw CouldNotReadPhotoException::make();
        }

        $data = str_replace(["\r", "\n"], '', base64_encode($binary));

        try {
            $message = $this->client->messages->create(
                maxTokens: 1000,
                messages: [[
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $mediaType,
                                'data' => $data,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => self::promptFor($expect),
                        ],
                    ],
                ]],
                model: (string) config('services.anthropic.model', 'claude-opus-5'),
                outputConfig: [
                    'effort' => 'low',
                    'format' => [
                        'type' => 'json_schema',
                        'schema' => self::schema(),
                    ],
                ],
            );
        } catch (RateLimitException) {
            throw CouldNotReadPhotoException::make();
        } catch (APITimeoutException) {
            throw CouldNotReadPhotoException::make();
        } catch (BadRequestException) {
            throw CouldNotReadPhotoException::make();
        } catch (AuthenticationException) {
            throw CouldNotReadPhotoException::make();
        } catch (PermissionDeniedException) {
            throw CouldNotReadPhotoException::make();
        } catch (NotFoundException) {
            throw CouldNotReadPhotoException::make();
        } catch (ConflictException) {
            throw CouldNotReadPhotoException::make();
        } catch (UnprocessableEntityException) {
            throw CouldNotReadPhotoException::make();
        } catch (InternalServerException) {
            throw CouldNotReadPhotoException::make();
        } catch (APIStatusException) {
            throw CouldNotReadPhotoException::make();
        } catch (APIConnectionException) {
            throw CouldNotReadPhotoException::make();
        } catch (APIException) {
            throw CouldNotReadPhotoException::make();
        }

        return $this->readingFromMessage($message->parsedOutput(), $message->content);
    }

    /**
     * @param  iterable<int, object>  $content
     */
    private function readingFromMessage(mixed $parsed, iterable $content): ExtractedReading
    {
        if (is_array($parsed) && ! array_key_exists('error', $parsed) && isset($parsed['device_type'])) {
            return ExtractedReading::fromArray($parsed);
        }

        foreach ($content as $block) {
            $text = $block->text ?? null;

            if (! is_string($text) || $text === '') {
                continue;
            }

            try {
                $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                continue;
            }

            if (is_array($decoded)) {
                return ExtractedReading::fromArray($decoded);
            }
        }

        throw CouldNotReadPhotoException::make();
    }

    public static function promptFor(?string $expect): string
    {
        if ($expect === 'glucose') {
            return self::PROMPT."\n\nThis photo was taken of a home blood glucose meter. Prefer device_type glucometer when a single glucose value is visible.";
        }

        if ($expect === 'bp') {
            return self::PROMPT."\n\nThis photo was taken of a home blood pressure monitor. Prefer device_type blood_pressure_monitor when systolic and diastolic are visible.";
        }

        return self::PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    public static function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'device_type' => [
                    'type' => 'string',
                    'enum' => ['blood_pressure_monitor', 'glucometer', 'unknown'],
                ],
                'systolic' => ['type' => ['integer', 'null']],
                'diastolic' => ['type' => ['integer', 'null']],
                'pulse' => ['type' => ['integer', 'null']],
                'glucose' => ['type' => ['number', 'null']],
                'glucose_unit_on_screen' => [
                    'type' => ['string', 'null'],
                    'enum' => ['mg/dL', 'mmol/L', null],
                ],
                'confidence' => [
                    'type' => 'string',
                    'enum' => ['high', 'medium', 'low'],
                ],
                'notes' => ['type' => 'string'],
            ],
            'required' => ['device_type', 'confidence', 'notes'],
            'additionalProperties' => false,
        ];
    }
}
