<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Requests\Community\StorePostRequest;
use App\Http\Requests\Community\UpdatePostRequest;
use App\Models\Community\Community;
use App\Models\Community\CommunityPost;
use App\Services\Community\PostService;
use Illuminate\Http\JsonResponse;

class CommunityPostController extends CommunityApiController
{
    public function __construct(private readonly PostService $posts)
    {
    }

    public function store(StorePostRequest $request, Community $community): JsonResponse
    {
        return $this->ok([
            'community_id' => $community->getKey(),
            'payload' => $request->validated(),
        ], 201);
    }

    public function update(UpdatePostRequest $request, Community $community, CommunityPost $post): JsonResponse
    {
        return $this->ok([
            'community_id' => $community->getKey(),
            'post_id' => $post->getKey(),
            'payload' => $request->validated(),
        ]);
    }

    public function destroy(Community $community, CommunityPost $post): JsonResponse
    {
        return $this->ok([
            'community_id' => $community->getKey(),
            'post_id' => $post->getKey(),
        ], 204);
    }
}
