<?php

declare(strict_types=1);

namespace App\Http\Requests\Community;

class UpdateGeoBoundsRequest extends CommunityFormRequest
{
    public function rules(): array
    {
        return [
            'polygon' => ['required', 'array'],
            'polygon.*' => ['array'],
            'privacy' => ['nullable', 'array'],
        ];
    }
}
