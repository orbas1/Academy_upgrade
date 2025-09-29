<?php

namespace App\Http\Requests\Storage;

use Illuminate\Foundation\Http\FormRequest;

class RestoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'profile' => ['required', 'string'],
            'key' => ['required', 'string'],
            'days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }
}
