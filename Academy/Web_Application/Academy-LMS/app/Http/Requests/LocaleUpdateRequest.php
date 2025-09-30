<?php

namespace App\Http\Requests;

use App\Rules\InternalRedirect;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

class LocaleUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'locale' => [
                'required',
                'string',
                Rule::in(array_keys(Config::get('localization.supported_locales', []))),
            ],
            'redirect_to' => ['nullable', 'string', new InternalRedirect()],
        ];
    }
}
