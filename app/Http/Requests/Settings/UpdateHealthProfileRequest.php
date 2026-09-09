<?php

namespace App\Http\Requests\Settings;

use App\Enums\GlucoseUnit;
use App\Enums\WeightUnit;
use App\Support\Glucose;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateHealthProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('profile')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'timezone' => ['required', 'timezone:all'],
            'glucose_unit' => ['required', Rule::enum(GlucoseUnit::class)],
            'weight_unit' => ['required', Rule::enum(WeightUnit::class)],
            'glucose_low' => ['nullable', 'numeric'],
            'glucose_high' => ['nullable', 'numeric'],
            'reminders_enabled' => ['sometimes', 'boolean'],
            'reminder_morning' => ['required', 'date_format:H:i'],
            'reminder_afternoon' => ['required', 'date_format:H:i'],
            'reminder_evening' => ['required', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function () use ($validator): void {
            $profile = $this->route('profile');
            $unit = GlucoseUnit::tryFrom((string) $this->input('glucose_unit')) ?? $profile?->glucose_unit;

            if ($unit === null) {
                return;
            }

            $low = $this->convertedTarget('glucose_low', $unit);
            $high = $this->convertedTarget('glucose_high', $unit);

            if ($low !== null && ! Glucose::isWithinStoredRange($low)) {
                $validator->errors()->add('glucose_low', 'That low sugar target is outside a usable range.');
            }

            if ($high !== null && ! Glucose::isWithinStoredRange($high)) {
                $validator->errors()->add('glucose_high', 'That high sugar target is outside a usable range.');
            }

            if ($low !== null && $high !== null && bccomp($low, $high, 2) >= 0) {
                $validator->errors()->add('glucose_high', 'The high sugar target must be above the low target.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function profileAttributes(): array
    {
        $unit = GlucoseUnit::from($this->validated('glucose_unit'));

        return [
            'name' => $this->validated('name'),
            'timezone' => $this->validated('timezone'),
            'glucose_unit' => $unit,
            'weight_unit' => WeightUnit::from($this->validated('weight_unit')),
            'glucose_low_mg_dl' => $this->convertedTarget('glucose_low', $unit),
            'glucose_high_mg_dl' => $this->convertedTarget('glucose_high', $unit),
            'reminders_enabled' => $this->boolean('reminders_enabled'),
            'reminder_morning' => $this->validated('reminder_morning'),
            'reminder_afternoon' => $this->validated('reminder_afternoon'),
            'reminder_evening' => $this->validated('reminder_evening'),
        ];
    }

    private function convertedTarget(string $key, GlucoseUnit $unit): ?string
    {
        $raw = $this->input($key);

        if ($raw === null || $raw === '') {
            return null;
        }

        return Glucose::toMgDl((string) $raw, $unit);
    }
}
