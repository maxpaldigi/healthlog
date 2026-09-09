<?php

namespace App\Http\Requests\Settings;

use App\Enums\GlucoseUnit;
use App\Enums\WeightUnit;
use App\Models\Profile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHealthProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Profile::class) ?? false;
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
        ];
    }
}
