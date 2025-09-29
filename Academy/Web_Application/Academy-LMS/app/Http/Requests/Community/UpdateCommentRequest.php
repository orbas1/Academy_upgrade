<?php

declare(strict_types=1);

namespace App\Http\Requests\Community;

class UpdateCommentRequest extends CommunityFormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['sometimes', 'string'],
        ];
    }
}
