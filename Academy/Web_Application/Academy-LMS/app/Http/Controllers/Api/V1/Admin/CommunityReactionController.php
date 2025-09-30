<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityPost;
use App\Services\Admin\AdminCommunityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\In;

class CommunityReactionController extends AdminApiController
{
    public function __construct(private readonly AdminCommunityService $service)
    {
    }

    public function store(Request $request, Community $community, CommunityPost $post): JsonResponse
    {
        abort_unless((int) $post->community_id === (int) $community->getKey(), 404);

        $validated = $request->validate([
            'reaction' => ['nullable', new In(['none', 'like', 'love', 'insightful', 'celebrate'])],
        ]);

        $reaction = $validated['reaction'] ?? null;
        if ($reaction === 'none') {
            $reaction = null;
        }

        $feedItem = $this->service->toggleReaction($post, $request->user(), $reaction);

        return $this->ok($feedItem);
    }
}
