<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Search;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSavedSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        $scopes = array_keys((array) config('search.scopes', []));

        return [
            'name' => ['required', 'string', 'max:120'],
            'scope' => ['required', Rule::in($scopes)],
            'query' => ['nullable', 'string', 'max:200'],
            'filters' => ['nullable'],
            'sort' => ['nullable', 'string', 'max:100'],
            'frequency' => ['required', 'string', Rule::in(['none', 'hourly', 'daily', 'weekly'])],
        ];
    }
}

