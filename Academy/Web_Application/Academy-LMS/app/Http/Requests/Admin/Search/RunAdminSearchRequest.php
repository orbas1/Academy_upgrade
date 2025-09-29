<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Search;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RunAdminSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        $scopes = array_keys((array) config('search.scopes', []));

        return [
            'saved_search_id' => ['nullable', 'integer', 'exists:admin_saved_searches,id'],
            'scope' => ['required_without:saved_search_id', Rule::in($scopes)],
            'query' => ['nullable', 'string', 'max:200'],
            'filters' => ['nullable'],
            'sort' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}

