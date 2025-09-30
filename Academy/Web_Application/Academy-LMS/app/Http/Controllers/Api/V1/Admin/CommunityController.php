<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\Communities\Models\Community;
use App\Http\Requests\Community\StoreCommunityRequest;
use App\Http\Requests\Community\UpdateCommunityRequest;
use App\Services\Admin\AdminCommunityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CommunityController extends AdminApiController
{
    public function __construct(private readonly AdminCommunityService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'query' => $request->query('query'),
            'visibility' => $request->query('visibility', 'all'),
            'paywall' => $request->query('paywall', 'all'),
        ];
        $perPage = (int) $request->integer('page_size', 25);
        $perPage = max(5, min($perPage, 100));
        $cursor = $request->query('after');

        $result = $this->service->summarizeCommunities($filters, $perPage, $cursor);

        return $this->paginated($result['paginator'], [
            'total' => $result['total'],
        ]);
    }

    public function show(Community $community): JsonResponse
    {
        return $this->ok($this->service->showCommunity($community));
    }

    public function store(StoreCommunityRequest $request): JsonResponse
    {
        // Delegating to service layer for consistency; actual creation flows remain managed elsewhere in roadmap.
        $payload = $request->validated();
        $community = Community::create($payload + [
            'created_by' => $request->user()->getKey(),
            'updated_by' => $request->user()->getKey(),
        ]);

        return $this->ok($this->service->showCommunity($community->refresh()), [
            'message' => 'Community created successfully.',
        ], SymfonyResponse::HTTP_CREATED);
    }

    public function update(UpdateCommunityRequest $request, Community $community): JsonResponse
    {
        $community->fill($request->validated());
        $community->updated_by = $request->user()->getKey();
        $community->save();

        return $this->ok($this->service->showCommunity($community->refresh()));
    }

    public function destroy(Community $community): JsonResponse
    {
        $community->delete();

        return $this->ok(null, [], SymfonyResponse::HTTP_NO_CONTENT);
    }
}
