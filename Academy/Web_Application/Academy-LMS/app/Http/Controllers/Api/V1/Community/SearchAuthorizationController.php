<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Domain\Search\Services\SearchVisibilityService;
use App\Domain\Search\Services\SearchVisibilityTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchAuthorizationController extends CommunityApiController
{
    public function __construct(
        private readonly SearchVisibilityService $visibilityService,
        private readonly SearchVisibilityTokenService $tokenService
    ) {
    }

    public function token(Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();
        $context = $this->visibilityService->forUser($user);
        $payload = $this->tokenService->issue($context);

        return $this->ok($payload);
    }
}

