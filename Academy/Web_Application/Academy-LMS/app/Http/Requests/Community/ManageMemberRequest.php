<?php

declare(strict_types=1);

namespace App\Http\Requests\Community;

class ManageMemberRequest extends CommunityFormRequest
{
    public function rules(): array
    {
        return [
            'role' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string'],
            'message' => ['nullable', 'string'],
        ];
    }
}
