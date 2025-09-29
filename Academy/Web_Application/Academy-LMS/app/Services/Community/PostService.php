<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Enums\Community\CommunityPostVisibility;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPost;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;

/**
 * Contract describing how posts are created, updated, and curated.
 */
interface PostService
{
    public function composePost(Community $community, CommunityMember $author, array $payload, ?Collection $media = null, ?CarbonInterface $publishAt = null): CommunityPost;

    public function updatePost(CommunityPost $post, CommunityMember $actor, array $payload, ?Collection $media = null): CommunityPost;

    public function deletePost(CommunityPost $post, CommunityMember $actor): void;

    public function pinPost(CommunityPost $post, CommunityMember $actor): CommunityPost;

    public function unpinPost(CommunityPost $post, CommunityMember $actor): CommunityPost;

    public function lockPost(CommunityPost $post, CommunityMember $actor, bool $locked = true): CommunityPost;

    public function attachMedia(CommunityPost $post, Collection $media): CommunityPost;

    public function uploadComposerMedia(User $actor, UploadedFile $file): string;

    public function changeVisibility(CommunityPost $post, CommunityMember $actor, CommunityPostVisibility $visibility): CommunityPost;
}
