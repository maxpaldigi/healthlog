<?php

namespace App\Http\Requests\Readings;

class UpdateGlucoseReadingRequest extends StoreGlucoseReadingRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('glucoseReading')) ?? false;
    }
}
