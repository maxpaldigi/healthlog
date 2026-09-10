<?php

namespace App\Actions\Readings;

use App\Actions\Readings\Concerns\NormalizesReadingNote;
use App\Enums\ReadingSource;
use App\Enums\ReadingTag;
use App\Models\BpReading;
use App\Models\Profile;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

class StoreBpReading
{
    use NormalizesReadingNote;

    /**
     * @param  array{systolic: int, diastolic: int, pulse?: int|null, measured_at: string, tag: string, note?: string|null, source?: string, client_uuid?: string|null}  $data
     */
    public function handle(Profile $profile, array $data): BpReading
    {
        $attributes = [
            'systolic' => $data['systolic'],
            'diastolic' => $data['diastolic'],
            'pulse' => $data['pulse'] ?? null,
            'measured_at' => Carbon::parse($data['measured_at'], $profile->clockTimezone())->utc(),
            'tag' => ReadingTag::from($data['tag']),
            'note' => self::note($data['note'] ?? null),
            'source' => ReadingSource::tryFrom($data['source'] ?? '') ?? ReadingSource::Manual,
        ];

        $clientUuid = $data['client_uuid'] ?? null;

        if (! is_string($clientUuid) || $clientUuid === '') {
            return $profile->bpReadings()->create($attributes);
        }

        $attributes['client_uuid'] = $clientUuid;

        try {
            return $profile->bpReadings()->updateOrCreate(
                ['client_uuid' => $clientUuid],
                $attributes,
            );
        } catch (UniqueConstraintViolationException) {
            $reading = $profile->bpReadings()->where('client_uuid', $clientUuid)->firstOrFail();
            $reading->update($attributes);

            return $reading->refresh();
        }
    }
}
