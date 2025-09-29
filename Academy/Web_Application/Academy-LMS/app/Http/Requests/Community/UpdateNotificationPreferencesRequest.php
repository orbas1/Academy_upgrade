<?php

declare(strict_types=1);

namespace App\Http\Requests\Community;

class UpdateNotificationPreferencesRequest extends CommunityFormRequest
{
    public function rules(): array
    {
        return [
            'channel_email' => ['sometimes', 'boolean'],
            'channel_push' => ['sometimes', 'boolean'],
            'channel_in_app' => ['sometimes', 'boolean'],
            'digest_frequency' => ['sometimes', 'string', 'in:daily,weekly,off'],
            'muted_events' => ['sometimes', 'array'],
            'muted_events.*' => ['string'],
            'locale' => ['sometimes', 'string', 'max:12'],
        ];
    }
}
