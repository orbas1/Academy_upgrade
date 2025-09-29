<?php

declare(strict_types=1);

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSavedSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('search.saved') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $indexes = array_keys((array) config('search.meilisearch.indexes', []));
        $flags = array_keys((array) config('search.admin_tools.flag_filters', []));

        return [
            'name' => ['required', 'string', 'max:120'],
            'index' => ['required', 'string', Rule::in($indexes)],
            'query' => ['nullable', 'string', 'max:200'],
            'filters' => ['nullable', 'array'],
            'filters.*' => ['string', 'max:255'],
            'flags' => ['nullable', 'array'],
            'flags.*' => ['string', Rule::in($flags)],
            'sort' => ['nullable', 'array'],
            'sort.*' => ['string', 'max:120'],
            'is_shared' => ['nullable', 'boolean'],
        ];
    }
}

