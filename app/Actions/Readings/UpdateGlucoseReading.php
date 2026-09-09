<?php

namespace App\Actions\Readings;

use App\Actions\Readings\Concerns\NormalizesReadingNote;
use App\Enums\GlucoseContext;
use App\Models\GlucoseReading;
use App\Support\Glucose;
use Illuminate\Support\Carbon;

class UpdateGlucoseReading
{
    use NormalizesReadingNote;

    /**
     * @param  array{value: mixed, context: string, measured_at: string, note?: string|null}  $data
     */
    public function handle(GlucoseReading $reading, array $data): GlucoseReading
    {
        $profile = $reading->profile;

        $reading->update([
            'value_mg_dl' => Glucose::toMgDl((string) $data['value'], $profile->glucose_unit),
            'context' => GlucoseContext::from($data['context']),
            'measured_at' => Carbon::parse($data['measured_at'], $profile->clockTimezone())->utc(),
            'note' => self::note($data['note'] ?? null),
        ]);

        return $reading->refresh();
    }
}
