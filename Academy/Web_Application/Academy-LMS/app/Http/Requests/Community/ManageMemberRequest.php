<?php

declare(strict_types=1);

namespace App\Http\Requests\Community;

use App\Enums\Community\CommunityMemberRole;
use App\Enums\Community\CommunityMemberStatus;
use Illuminate\Validation\Rule;

class ManageMemberRequest extends CommunityFormRequest
{
    public function rules(): array
    {
        return [
            'role' => ['sometimes', 'string', Rule::in(array_map(fn ($case) => $case->value, CommunityMemberRole::cases()))],
            'status' => ['sometimes', 'string', Rule::in(array_map(fn ($case) => $case->value, CommunityMemberStatus::cases()))],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
