<?php

namespace App\Http\Requests\Readings;

class UpdateBpReadingRequest extends StoreBpReadingRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('bpReading')) ?? false;
    }
}
