<?php

namespace App\Http\Requests\Readings;

use App\Enums\GlucoseContext;
use App\Enums\ReadingSource;
use App\Http\Requests\Readings\Concerns\ValidatesClientUuid;
use App\Http\Requests\Readings\Concerns\ValidatesMeasuredAt;
use App\Support\CurrentProfile;
use App\Support\Glucose;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGlucoseReadingRequest extends FormRequest
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
            'value' => ['required', 'numeric'],
            'context' => ['required', Rule::enum(GlucoseContext::class)],
            'measured_at' => ['required', 'date_format:Y-m-d\TH:i'],
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

        $this->validator->after(function (): void {
            $raw = $this->input('value');

            if ($raw === null || $raw === '') {
                return;
            }

            $profile = app(CurrentProfile::class)->handle($this->user(), $this);
            $mgDl = Glucose::toMgDl((string) $raw, $profile->glucose_unit);

            if (bccomp($mgDl, '20', 2) < 0 || bccomp($mgDl, '700', 2) > 0) {
                $unit = $profile->glucose_unit->label();
                $range = $profile->glucose_unit->value === 'mmol_l' ? '1.1 and 38.9' : '20 and 700';

                $this->validator->errors()->add(
                    'value',
                    "The glucose value must be between {$range} {$unit}.",
                );
            }
        });
    }
}
