<?php

declare(strict_types=1);

namespace App\Http\Requests\Community;

class UpdatePostRequest extends CommunityFormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['sometimes', 'string'],
            'media' => ['sometimes', 'array'],
            'media.*.path' => ['required_with:media', 'string'],
            'visibility' => ['sometimes', 'string'],
        ];
    }
}
