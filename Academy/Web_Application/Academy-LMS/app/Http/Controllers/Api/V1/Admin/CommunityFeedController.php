<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Communities\Models\Community;
use App\Services\Admin\AdminCommunityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunityFeedController extends AdminApiController
{
    public function __construct(private readonly AdminCommunityService $service)
    {
    }

    public function index(Request $request, Community $community): JsonResponse
    {
        $filter = $request->query('filter', 'new');
        $perPage = (int) $request->integer('page_size', 25);
        $perPage = max(5, min($perPage, 50));
        $cursor = $request->query('after');

        $result = $this->service->loadFeed(
            $community,
            $request->user(),
            $filter,
            $perPage,
            $cursor
        );

        return $this->paginated($result['paginator'], [
            'total' => $result['total'],
        ]);
    }
}
