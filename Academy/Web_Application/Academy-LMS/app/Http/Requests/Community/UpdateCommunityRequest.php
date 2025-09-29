<?php

declare(strict_types=1);

namespace App\Http\Requests\Community;

class UpdateCommunityRequest extends CommunityFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'tagline' => ['sometimes', 'string', 'max:255'],
            'visibility' => ['sometimes', 'string'],
            'settings' => ['sometimes', 'array'],
        ];
    }
}
