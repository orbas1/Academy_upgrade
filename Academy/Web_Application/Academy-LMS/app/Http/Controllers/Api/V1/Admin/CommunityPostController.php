<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Communities\Models\Community;
use App\Services\Admin\AdminCommunityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\In;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CommunityPostController extends AdminApiController
{
    public function __construct(private readonly AdminCommunityService $service)
    {
    }

    public function store(Request $request, Community $community): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1'],
            'visibility' => ['nullable', new In(['community', 'public', 'paid'])],
            'scheduled_at' => ['nullable', 'date'],
            'paywall_tier_id' => ['nullable', 'integer'],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
        ]);

        $feedItem = $this->service->createPost($community, $request->user(), $validated);

        return $this->ok($feedItem, [
            'message' => 'Post queued for publishing.',
        ], SymfonyResponse::HTTP_CREATED);
    }
}
