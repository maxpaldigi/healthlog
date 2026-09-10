<?php

namespace App\Http\Requests\Food;

use App\Enums\FoodSource;
use App\Support\CurrentProfile;
use App\Support\OpenFoodFacts;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFoodLogRequest extends FormRequest
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
            'source' => ['required', Rule::enum(FoodSource::class)],
            'source_ref' => ['nullable', 'string', 'max:20', 'required_if:source,'.FoodSource::OpenFoodFacts->value],
            'label' => ['nullable', 'string', 'max:180', 'required_if:source,'.FoodSource::Custom->value],
            'portion_g' => ['required', 'numeric', 'min:1', 'max:2000'],
            'portion_label' => ['required', 'string', 'max:40'],
            'eaten_at' => ['required', 'date_format:Y-m-d\TH:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function () use ($validator): void {
            $this->validateEatenAt($validator);
            $this->validateBarcode($validator);
        });
    }

    private function validateEatenAt(Validator $validator): void
    {
        $raw = $this->input('eaten_at');

        if (! is_string($raw) || $raw === '') {
            return;
        }

        $profile = app(CurrentProfile::class)->handle($this->user(), $this);
        $eatenAt = Carbon::parse($raw, $profile->clockTimezone());
        $now = $profile->nowInTimezone();

        if ($eatenAt->greaterThan($now->addMinute())) {
            $validator->errors()->add('eaten_at', 'The time cannot be in the future.');
        }

        if ($eatenAt->lessThan($now->subYears(2))) {
            $validator->errors()->add('eaten_at', 'The time cannot be more than 2 years ago.');
        }
    }

    private function validateBarcode(Validator $validator): void
    {
        if (FoodSource::tryFrom((string) $this->input('source')) !== FoodSource::OpenFoodFacts) {
            return;
        }

        $food = app(OpenFoodFacts::class)->byBarcode((string) $this->input('source_ref'));

        if ($food === null) {
            $validator->errors()->add('source_ref', 'No product found for that barcode.');
        }
    }
}
