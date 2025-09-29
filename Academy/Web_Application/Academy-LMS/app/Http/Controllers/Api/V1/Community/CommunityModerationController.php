<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Requests\Community\ModeratePostRequest;
use App\Models\Community\Community;
use App\Models\Community\CommunityPost;
use App\Services\Community\PostService;
use Illuminate\Http\JsonResponse;

class CommunityModerationController extends CommunityApiController
{
    public function __construct(private readonly PostService $posts)
    {
    }

    public function moderate(ModeratePostRequest $request, Community $community, CommunityPost $post): JsonResponse
    {
        return $this->ok([
            'community_id' => $community->getKey(),
            'post_id' => $post->getKey(),
            'action' => $request->validated(),
        ]);
    }
}
