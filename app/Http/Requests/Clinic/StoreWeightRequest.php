<?php

namespace App\Http\Requests\Clinic;

use App\Http\Requests\Readings\Concerns\ValidatesMeasuredAt;
use App\Support\BodyWeight;
use App\Support\CurrentProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreWeightRequest extends FormRequest
{
    use ValidatesMeasuredAt;

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
            'measured_at' => ['required', 'date_format:Y-m-d\TH:i'],
        ];
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
            $kg = BodyWeight::toKg((string) $raw, $profile->weight_unit);

            if (! BodyWeight::isWithinStoredRange($kg)) {
                $unit = $profile->weight_unit->label();
                $range = $profile->weight_unit->value === 'lb' ? '22 and 880' : '10 and 400';

                $this->validator->errors()->add(
                    'value',
                    "Weight must be between {$range} {$unit}.",
                );
            }
        });
    }
}
