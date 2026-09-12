<?php

namespace App\Http\Requests\Clinic;

use App\Support\BodyHeight;
use App\Support\BodyWeight;
use App\Support\CurrentProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBodyCheckInRequest extends FormRequest
{
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
            'height_unit' => ['nullable', Rule::in(['cm', 'ft'])],
            'height_cm' => ['nullable', 'numeric'],
            'height_ft' => ['nullable', 'numeric'],
            'height_in' => ['nullable', 'numeric'],
            'weight' => ['nullable', 'numeric'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function () use ($validator): void {
            $height = $this->heightCm();
            $weight = $this->input('weight');

            if ($height === null && ($weight === null || $weight === '')) {
                $validator->errors()->add(
                    'height_cm',
                    'Add a height or a weight, or skip for now.',
                );

                return;
            }

            if ($height !== null && ! BodyHeight::isWithinStoredRange($height)) {
                $validator->errors()->add(
                    'height_cm',
                    'Height must be between 50 and 250 cm.',
                );
            }

            if ($weight === null || $weight === '') {
                return;
            }

            $profile = app(CurrentProfile::class)->handle($this->user(), $this);
            $kg = BodyWeight::toKg((string) $weight, $profile->weight_unit);

            if (! BodyWeight::isWithinStoredRange($kg)) {
                $unit = $profile->weight_unit->label();
                $range = $profile->weight_unit->value === 'lb' ? '22 and 880' : '10 and 400';

                $validator->errors()->add(
                    'weight',
                    "Weight must be between {$range} {$unit}.",
                );
            }
        });
    }

    public function heightCm(): ?string
    {
        if ($this->input('height_unit') === 'ft') {
            $feet = $this->input('height_ft');
            $inches = $this->input('height_in');

            if ($feet === null || $feet === '') {
                return null;
            }

            return BodyHeight::fromFeetAndInches(
                (string) $feet,
                $inches === null || $inches === '' ? '0' : (string) $inches,
            );
        }

        $cm = $this->input('height_cm');

        if ($cm === null || $cm === '') {
            return null;
        }

        return BodyHeight::fromCentimetres((string) $cm);
    }
}
