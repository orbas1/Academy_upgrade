<?php

declare(strict_types=1);

namespace App\Http\Requests\Community;

class StoreCommunityRequest extends CommunityFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255'],
            'visibility' => ['required', 'string'],
        ];
    }
}
