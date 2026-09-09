<?php

namespace App\Actions\Readings;

use App\Actions\Readings\Concerns\NormalizesReadingNote;
use App\Enums\ReadingTag;
use App\Models\BpReading;
use Illuminate\Support\Carbon;

class UpdateBpReading
{
    use NormalizesReadingNote;

    /**
     * @param  array{systolic: int, diastolic: int, pulse?: int|null, measured_at: string, tag: string, note?: string|null}  $data
     */
    public function handle(BpReading $reading, array $data): BpReading
    {
        $profile = $reading->profile;

        $reading->update([
            'systolic' => $data['systolic'],
            'diastolic' => $data['diastolic'],
            'pulse' => $data['pulse'] ?? null,
            'measured_at' => Carbon::parse($data['measured_at'], $profile->clockTimezone())->utc(),
            'tag' => ReadingTag::from($data['tag']),
            'note' => self::note($data['note'] ?? null),
        ]);

        return $reading->refresh();
    }
}
