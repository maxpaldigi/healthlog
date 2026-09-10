<?php

namespace App\Http\Requests\Food;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreFoodPhotoRequest extends FormRequest
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
            'photo' => ['required', 'file', 'max:10240'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $file = $this->file('photo');

            if ($file === null) {
                return;
            }

            $mime = (string) $file->getMimeType();
            $extension = strtolower((string) $file->getClientOriginalExtension());
            $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/heic', 'image/heif'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'heic', 'heif'];

            if (! in_array($mime, $allowedMimes, true) && ! in_array($extension, $allowedExtensions, true)) {
                $validator->errors()->add('photo', 'Use a JPEG, PNG, or HEIC photo under 10 MB.');
            }
        });
    }
}
