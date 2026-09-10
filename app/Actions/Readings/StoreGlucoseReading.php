<?php

namespace App\Actions\Readings;

use App\Actions\Food\LinkFoodToGlucose;
use App\Actions\Readings\Concerns\NormalizesReadingNote;
use App\Enums\GlucoseContext;
use App\Enums\ReadingSource;
use App\Models\GlucoseReading;
use App\Models\Profile;
use App\Support\Glucose;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

class StoreGlucoseReading
{
    use NormalizesReadingNote;

    public function __construct(private LinkFoodToGlucose $linkFoodToGlucose) {}

    /**
     * @param  array{value: mixed, context: string, measured_at: string, note?: string|null, source?: string, client_uuid?: string|null}  $data
     */
    public function handle(Profile $profile, array $data): GlucoseReading
    {
        $attributes = [
            'value_mg_dl' => Glucose::toMgDl((string) $data['value'], $profile->glucose_unit),
            'context' => GlucoseContext::from($data['context']),
            'measured_at' => Carbon::parse($data['measured_at'], $profile->clockTimezone())->utc(),
            'note' => self::note($data['note'] ?? null),
            'source' => ReadingSource::tryFrom($data['source'] ?? '') ?? ReadingSource::Manual,
        ];

        $clientUuid = $data['client_uuid'] ?? null;

        if (! is_string($clientUuid) || $clientUuid === '') {
            $reading = $profile->glucoseReadings()->create($attributes);
            $this->linkFoodToGlucose->handle($profile);

            return $reading;
        }

        $attributes['client_uuid'] = $clientUuid;

        try {
            $reading = $profile->glucoseReadings()->updateOrCreate(
                ['client_uuid' => $clientUuid],
                $attributes,
            );
        } catch (UniqueConstraintViolationException) {
            $reading = $profile->glucoseReadings()->where('client_uuid', $clientUuid)->firstOrFail();
            $reading->update($attributes);
            $reading = $reading->refresh();
        }

        $this->linkFoodToGlucose->handle($profile);

        return $reading;
    }
}
