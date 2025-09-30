<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Communities\Models\Community;
use App\Services\Admin\AdminCommunityService;
use Illuminate\Http\JsonResponse;

class CommunityMetricsController extends AdminApiController
{
    public function __construct(private readonly AdminCommunityService $service)
    {
    }

    public function show(Community $community): JsonResponse
    {
        return $this->ok($this->service->loadMetrics($community));
    }
}
