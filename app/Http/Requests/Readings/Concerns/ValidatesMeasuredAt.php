<?php

namespace App\Http\Requests\Readings\Concerns;

use App\Support\CurrentProfile;
use Illuminate\Support\Carbon;

trait ValidatesMeasuredAt
{
    protected function validateMeasuredAtAgainstProfile(): void
    {
        $this->validator->after(function (): void {
            $raw = $this->input('measured_at');

            if (! is_string($raw) || $raw === '') {
                return;
            }

            $profile = app(CurrentProfile::class)->handle($this->user(), $this);
            $measuredAt = Carbon::parse($raw, $profile->timezone);
            $now = $profile->nowInTimezone();

            if ($measuredAt->greaterThan($now->addMinute())) {
                $this->validator->errors()->add('measured_at', 'The reading time cannot be in the future.');
            }

            if ($measuredAt->lessThan($now->subYears(2))) {
                $this->validator->errors()->add('measured_at', 'The reading time cannot be more than 2 years ago.');
            }
        });
    }
}
