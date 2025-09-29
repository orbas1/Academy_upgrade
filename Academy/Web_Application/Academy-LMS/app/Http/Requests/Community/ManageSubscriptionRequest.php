<?php

declare(strict_types=1);

namespace App\Http\Requests\Community;

class ManageSubscriptionRequest extends CommunityFormRequest
{
    public function rules(): array
    {
        return [
            'payment_intent' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
