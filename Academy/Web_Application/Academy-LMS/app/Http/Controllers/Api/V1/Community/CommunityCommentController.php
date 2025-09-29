<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Requests\Community\StoreCommentRequest;
use App\Http\Requests\Community\UpdateCommentRequest;
use App\Models\Community\Community;
use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityPostComment;
use App\Services\Community\CommentService;
use Illuminate\Http\JsonResponse;

class CommunityCommentController extends CommunityApiController
{
    public function __construct(private readonly CommentService $comments)
    {
    }

    public function store(StoreCommentRequest $request, Community $community, CommunityPost $post): JsonResponse
    {
        return $this->ok([
            'community_id' => $community->getKey(),
            'post_id' => $post->getKey(),
            'payload' => $request->validated(),
        ], 201);
    }

    public function update(UpdateCommentRequest $request, Community $community, CommunityPost $post, CommunityPostComment $comment): JsonResponse
    {
        return $this->ok([
            'community_id' => $community->getKey(),
            'post_id' => $post->getKey(),
            'comment_id' => $comment->getKey(),
            'payload' => $request->validated(),
        ]);
    }

    public function destroy(Community $community, CommunityPost $post, CommunityPostComment $comment): JsonResponse
    {
        return $this->ok([
            'community_id' => $community->getKey(),
            'post_id' => $post->getKey(),
            'comment_id' => $comment->getKey(),
        ], 204);
    }
}
