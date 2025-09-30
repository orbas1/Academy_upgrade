<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Community;

use App\Http\Requests\Community\StoreCommunityRequest;
use App\Http\Requests\Community\UpdateCommunityRequest;
use App\Models\Community\Community;
use App\Services\Community\MembershipService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CommunityController extends CommunityApiController
{
    public function __construct(private readonly MembershipService $memberships)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Community::query()
            ->with(['category:id,name', 'geoPlace:id,name,country_code,timezone'])
            ->withCount([
                'members as active_members_count' => fn (Builder $builder) => $builder->where('status', 'active'),
                'members as online_members_count' => fn (Builder $builder) => $builder
                    ->where('status', 'active')
                    ->where('is_online', true),
                'posts as published_posts_count' => fn (Builder $builder) => $builder->whereNotNull('published_at'),
            ])
            ->when($request->filled('visibility'), fn ($q) => $q->where('visibility', $request->query('visibility')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('featured'), fn ($q) => $q->where('is_featured', filter_var($request->query('featured'), FILTER_VALIDATE_BOOL)))
            ->when($request->filled('search'), function ($builder) use ($request) {
                $term = Str::lower((string) $request->query('search'));
                $builder->where(function ($searchQuery) use ($term) {
                    $searchQuery->whereRaw('LOWER(name) like ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(tagline) like ?', ["%{$term}%"])
                        ->orWhereRaw('LOWER(slug) like ?', ["%{$term}%"]);
                });
            });

        $perPage = (int) $request->integer('per_page', 25);
        $perPage = max(5, min($perPage, 100));
        $cursor = $request->query('cursor');

        $paginator = $query
            ->orderByDesc('launched_at')
            ->orderBy('name')
            ->cursorPaginate($perPage, ['*'], 'cursor', $cursor);

        $paginator->setCollection($paginator->getCollection()->map(fn (Community $community) => $this->transformCommunity($community)));

        return $this->respondWithPagination($paginator, [
            'filter' => $request->only(['visibility', 'category_id', 'featured', 'search']),
        ]);
    }

    public function store(StoreCommunityRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        if (Community::query()->where('slug', $data['slug'])->exists()) {
            return $this->respondWithError('Duplicate slug', 'A community with this slug already exists.', 422);
        }

        $community = Community::create([
            'slug' => Str::slug($data['slug']),
            'name' => $data['name'],
            'visibility' => $data['visibility'],
            'tagline' => $request->input('tagline'),
            'join_policy' => $request->input('join_policy', 'open'),
            'default_post_visibility' => $request->input('default_post_visibility', 'community'),
            'settings' => $request->input('settings', []),
            'created_by' => $user?->getKey(),
            'updated_by' => $user?->getKey(),
            'launched_at' => CarbonImmutable::now(),
        ]);

        if ($user) {
            $member = $this->memberships->requestJoin($user, $community);
            $this->memberships->promoteMember($member, \App\Enums\Community\CommunityMemberRole::OWNER, $user);
        }

        return $this->created($this->transformCommunity($community->fresh(['category', 'geoPlace'])));
    }

    public function show(Community $community): JsonResponse
    {
        $community->load(['category:id,name', 'geoPlace:id,name,country_code,timezone'])
            ->loadCount([
                'members as active_members_count' => fn (Builder $builder) => $builder->where('status', 'active'),
                'members as online_members_count' => fn (Builder $builder) => $builder
                    ->where('status', 'active')
                    ->where('is_online', true),
            ]);

        return $this->ok($this->transformCommunity($community));
    }

    public function update(UpdateCommunityRequest $request, Community $community): JsonResponse
    {
        $payload = $request->validated();

        if (isset($payload['slug']) && $payload['slug'] !== $community->slug) {
            if (Community::query()->where('slug', $payload['slug'])->whereKeyNot($community->getKey())->exists()) {
                return $this->respondWithError('Duplicate slug', 'A community with this slug already exists.', 422);
            }
            $payload['slug'] = Str::slug($payload['slug']);
        }

        $community->fill($payload);
        $community->updated_by = $request->user()?->getKey();
        $community->save();

        return $this->ok($this->transformCommunity($community->fresh(['category', 'geoPlace'])));
    }

    public function destroy(Community $community): JsonResponse
    {
        $community->delete();

        return $this->respondNoContent();
    }

    private function transformCommunity(Community $community): array
    {
        $settings = $community->settings ?? [];

        return [
            'id' => (int) $community->getKey(),
            'slug' => $community->slug,
            'name' => $community->name,
            'tagline' => $community->tagline,
            'visibility' => $community->visibility,
            'join_policy' => $community->join_policy,
            'default_post_visibility' => $community->default_post_visibility,
            'category' => $community->category?->only(['id', 'name']),
            'geo' => $community->geoPlace ? [
                'id' => $community->geoPlace->getKey(),
                'name' => $community->geoPlace->name,
                'country_code' => $community->geoPlace->country_code,
                'timezone' => $community->geoPlace->timezone,
            ] : null,
            'links' => $community->links,
            'settings' => $settings,
            'is_featured' => (bool) $community->is_featured,
            'metrics' => [
                'members_active' => (int) ($community->active_members_count ?? 0),
                'members_online' => (int) ($community->online_members_count ?? 0),
                'posts_published' => (int) ($community->published_posts_count ?? 0),
            ],
            'created_at' => optional($community->created_at)->toIso8601String(),
            'launched_at' => optional($community->launched_at)->toIso8601String(),
            'updated_at' => optional($community->updated_at)->toIso8601String(),
        ];
    }
}
