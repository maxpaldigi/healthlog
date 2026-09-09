<?php

namespace App\Actions\Readings;

use App\Actions\Readings\Concerns\NormalizesReadingNote;
use App\Enums\GlucoseContext;
use App\Enums\ReadingSource;
use App\Models\GlucoseReading;
use App\Models\Profile;
use App\Support\Glucose;
use Illuminate\Support\Carbon;

class StoreGlucoseReading
{
    use NormalizesReadingNote;

    /**
     * @param  array{value: mixed, context: string, measured_at: string, note?: string|null, source?: string}  $data
     */
    public function handle(Profile $profile, array $data): GlucoseReading
    {
        return $profile->glucoseReadings()->create([
            'value_mg_dl' => Glucose::toMgDl((string) $data['value'], $profile->glucose_unit),
            'context' => GlucoseContext::from($data['context']),
            'measured_at' => Carbon::parse($data['measured_at'], $profile->timezone)->utc(),
            'note' => self::note($data['note'] ?? null),
            'source' => ReadingSource::tryFrom($data['source'] ?? '') ?? ReadingSource::Manual,
        ]);
    }
}
