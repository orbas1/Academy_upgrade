<?php

namespace App\Http\Requests;

use App\Support\Localization\LocaleManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocaleSwitchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $localeManager = app(LocaleManager::class);

        return [
            'locale' => [
                'required',
                'string',
                Rule::in($localeManager->supportedLocaleCodes()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'locale.required' => __('locale.validation.required'),
            'locale.in' => __('locale.validation.unsupported'),
        ];
    }
}
