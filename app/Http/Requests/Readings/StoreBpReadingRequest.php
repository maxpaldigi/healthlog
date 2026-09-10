<?php

namespace App\Http\Requests\Readings;

use App\Enums\ReadingSource;
use App\Enums\ReadingTag;
use App\Http\Requests\Readings\Concerns\ValidatesClientUuid;
use App\Http\Requests\Readings\Concerns\ValidatesMeasuredAt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBpReadingRequest extends FormRequest
{
    use ValidatesClientUuid, ValidatesMeasuredAt;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'systolic' => ['required', 'integer', 'between:60,300'],
            'diastolic' => ['required', 'integer', 'between:30,200', 'lt:systolic'],
            'pulse' => ['nullable', 'integer', 'between:30,220'],
            'measured_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'tag' => ['required', Rule::enum(ReadingTag::class)],
            'note' => ['nullable', 'string', 'max:240'],
            'source' => ['nullable', Rule::enum(ReadingSource::class)],
            ...$this->clientUuidRules(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->prepareClientUuid();
    }

    public function withValidator(Validator $validator): void
    {
        $this->validator = $validator;
        $this->validateMeasuredAtAgainstProfile();
    }
}
