<?php

namespace App\Http\Requests\Clinic;

use App\Support\CurrentProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class StoreHba1cRequest extends FormRequest
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
            'value' => ['required', 'numeric', 'between:3,20'],
            'tested_at' => ['required', 'date'],
            'lab_name' => ['nullable', 'string', 'max:80'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function () use ($validator): void {
            $raw = $this->input('tested_at');

            if (! is_string($raw) || $raw === '') {
                return;
            }

            $profile = app(CurrentProfile::class)->handle($this->user(), $this);
            $testedAt = Carbon::parse($raw, $profile->timezone)->startOfDay();
            $today = $profile->nowInTimezone()->startOfDay();

            if ($testedAt->greaterThan($today)) {
                $validator->errors()->add('tested_at', 'The test date cannot be in the future.');
            }
        });
    }
}
