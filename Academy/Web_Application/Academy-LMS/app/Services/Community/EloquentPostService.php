<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Domain\Communities\Services\CommunityPostService as DomainPostService;
use App\Enums\Community\CommunityPostVisibility;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPost;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class EloquentPostService implements PostService
{
    public function __construct(private readonly DomainPostService $posts)
    {
    }

    public function composePost(
        Community $community,
        CommunityMember $author,
        array $payload,
        ?Collection $media = null,
        ?CarbonImmutable $publishAt = null
    ): CommunityPost {
        $this->assertSameCommunity($community, $author);

        $postPayload = $payload;
        if ($media) {
            $postPayload['media'] = $media->toArray();
        }

        if ($publishAt) {
            $postPayload['scheduled_at'] = $publishAt->toIso8601String();
        }

        if (! isset($postPayload['visibility'])) {
            $postPayload['visibility'] = $community->default_post_visibility;
        }

        $post = $this->posts->compose($community, $author->user, $postPayload);

        return $post->fresh(['author', 'community']);
    }

    public function updatePost(CommunityPost $post, CommunityMember $actor, array $payload, ?Collection $media = null): CommunityPost
    {
        $this->assertSameCommunityId($post->community_id, $actor->community_id);

        if ($media) {
            $payload['media'] = $media->toArray();
        }

        $post = $this->posts->update($post, $payload);

        return $post->fresh(['author', 'paywallTier']);
    }

    public function deletePost(CommunityPost $post, CommunityMember $actor): void
    {
        $this->assertSameCommunityId($post->community_id, $actor->community_id);
        $this->posts->destroy($post, $actor->user);
    }

    public function pinPost(CommunityPost $post, CommunityMember $actor): CommunityPost
    {
        $this->assertSameCommunityId($post->community_id, $actor->community_id);

        $post = $this->posts->update($post, ['is_pinned' => true]);

        return $post->fresh(['author']);
    }

    public function unpinPost(CommunityPost $post, CommunityMember $actor): CommunityPost
    {
        $this->assertSameCommunityId($post->community_id, $actor->community_id);

        $post = $this->posts->update($post, ['is_pinned' => false]);

        return $post->fresh(['author']);
    }

    public function lockPost(CommunityPost $post, CommunityMember $actor, bool $locked = true): CommunityPost
    {
        $this->assertSameCommunityId($post->community_id, $actor->community_id);

        $post = $this->posts->update($post, ['is_locked' => $locked]);

        return $post->fresh(['author']);
    }

    public function attachMedia(CommunityPost $post, Collection $media): CommunityPost
    {
        $payload = $post->toArray();
        $payload['media'] = $media->toArray();

        $post = $this->posts->update($post, $payload);

        return $post->fresh(['author']);
    }

    public function uploadComposerMedia(User $actor, UploadedFile $file): string
    {
        $path = sprintf('communities/%s/%s', $actor->getKey(), CarbonImmutable::now()->format('Y/m'));
        $storedPath = $file->storePublicly($path, ['disk' => config('filesystems.default', 'public')]);

        return Storage::disk(config('filesystems.default', 'public'))->url($storedPath);
    }

    public function changeVisibility(CommunityPost $post, CommunityMember $actor, CommunityPostVisibility $visibility): CommunityPost
    {
        $this->assertSameCommunityId($post->community_id, $actor->community_id);

        $post = $this->posts->update($post, ['visibility' => $visibility->value]);

        return $post->fresh(['author']);
    }

    private function assertSameCommunity(Community $community, CommunityMember $member): void
    {
        if ((int) $community->getKey() !== (int) $member->community_id) {
            throw new InvalidArgumentException('Member must belong to the target community.');
        }
    }

    private function assertSameCommunityId(int $postCommunityId, int $memberCommunityId): void
    {
        if ($postCommunityId !== $memberCommunityId) {
            throw new InvalidArgumentException('Member must belong to the post community.');
        }
    }
}
