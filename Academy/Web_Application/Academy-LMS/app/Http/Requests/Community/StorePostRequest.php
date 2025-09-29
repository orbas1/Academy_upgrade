<?php

declare(strict_types=1);

namespace App\Http\Requests\Community;

class StorePostRequest extends CommunityFormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', 'string'],
            'body' => ['nullable', 'string'],
            'media' => ['array'],
            'media.*.path' => ['required_with:media', 'string'],
        ];
    }
}
