<?php

namespace App\Actions\Readings;

use App\Actions\Readings\Concerns\NormalizesReadingNote;
use App\Enums\ReadingSource;
use App\Enums\ReadingTag;
use App\Models\BpReading;
use App\Models\Profile;
use Illuminate\Support\Carbon;

class StoreBpReading
{
    use NormalizesReadingNote;

    /**
     * @param  array{systolic: int, diastolic: int, pulse?: int|null, measured_at: string, tag: string, note?: string|null, source?: string}  $data
     */
    public function handle(Profile $profile, array $data): BpReading
    {
        return $profile->bpReadings()->create([
            'systolic' => $data['systolic'],
            'diastolic' => $data['diastolic'],
            'pulse' => $data['pulse'] ?? null,
            'measured_at' => Carbon::parse($data['measured_at'], $profile->clockTimezone())->utc(),
            'tag' => ReadingTag::from($data['tag']),
            'note' => self::note($data['note'] ?? null),
            'source' => ReadingSource::tryFrom($data['source'] ?? '') ?? ReadingSource::Manual,
        ]);
    }
}
