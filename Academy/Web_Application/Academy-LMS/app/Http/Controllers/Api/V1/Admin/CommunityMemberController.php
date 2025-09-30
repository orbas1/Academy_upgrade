<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Communities\Models\Community;
use App\Services\Admin\AdminCommunityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunityMemberController extends AdminApiController
{
    public function __construct(private readonly AdminCommunityService $service)
    {
    }

    public function index(Request $request, Community $community): JsonResponse
    {
        $filters = [
            'role' => $request->query('role'),
            'status' => $request->query('status'),
            'online' => $request->query('online'),
            'joined_after' => $request->query('joined_after'),
            'joined_before' => $request->query('joined_before'),
        ];
        $perPage = (int) $request->integer('page_size', 25);
        $perPage = max(5, min($perPage, 100));
        $cursor = $request->query('after');

        $result = $this->service->loadMembers($community, $filters, $perPage, $cursor);

        return $this->paginated($result['paginator'], [
            'total' => $result['total'],
        ]);
    }
}
