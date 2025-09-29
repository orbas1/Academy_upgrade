<?php

declare(strict_types=1);

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $scopes = array_keys((array) config('search.scopes', []));
        $scopes[] = 'all';

        return [
            'query' => ['nullable', 'string', 'max:200'],
            'scope' => ['required', 'string', Rule::in($scopes)],
            'filters' => ['nullable', 'array'],
            'filters.*' => ['nullable'],
            'sort' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'visibility_token' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'visibility_token.required' => 'A signed visibility token is required to search protected content.',
        ];
    }
}

