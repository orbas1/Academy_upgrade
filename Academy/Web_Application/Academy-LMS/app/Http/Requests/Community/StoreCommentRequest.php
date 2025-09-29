<?php

declare(strict_types=1);

namespace App\Http\Requests\Community;

class StoreCommentRequest extends CommunityFormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required', 'string'],
            'parent_id' => ['nullable', 'integer'],
        ];
    }
}
