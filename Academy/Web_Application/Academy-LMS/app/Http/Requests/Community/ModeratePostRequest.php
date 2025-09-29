<?php

declare(strict_types=1);

namespace App\Http\Requests\Community;

class ModeratePostRequest extends CommunityFormRequest
{
    public function rules(): array
    {
        return [
            'action' => ['required', 'string'],
            'reason' => ['nullable', 'string'],
        ];
    }
}
