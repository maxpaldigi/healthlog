<?php

namespace App\Http\Requests\Auth;

use App\Enums\GlucoseUnit;
use App\Enums\WeightUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterUserRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'timezone' => ['required', 'timezone:all'],
            'detected_timezone' => ['nullable', 'timezone:all'],
            'glucose_unit' => ['required', Rule::enum(GlucoseUnit::class)],
            'weight_unit' => ['required', Rule::enum(WeightUnit::class)],
            'accept_terms' => ['accepted'],
            'health_data_consent' => ['accepted'],
        ];
    }
}
