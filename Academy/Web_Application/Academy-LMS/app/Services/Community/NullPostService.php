<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Services\Community\Concerns\NotImplemented;

class NullPostService implements PostService
{
    use NotImplemented;
    public function composePost(\App\Models\Community\Community $community, \App\Models\Community\CommunityMember $author, array $payload, ?\Illuminate\Support\Collection $media = null, ?\Carbon\CarbonInterface $publishAt = null): \App\Models\Community\CommunityPost
    {
        $this->notImplemented();
    }

    public function updatePost(\App\Models\Community\CommunityPost $post, \App\Models\Community\CommunityMember $actor, array $payload, ?\Illuminate\Support\Collection $media = null): \App\Models\Community\CommunityPost
    {
        $this->notImplemented();
    }

    public function deletePost(\App\Models\Community\CommunityPost $post, \App\Models\Community\CommunityMember $actor): void
    {
        $this->notImplemented();
    }

    public function pinPost(\App\Models\Community\CommunityPost $post, \App\Models\Community\CommunityMember $actor): \App\Models\Community\CommunityPost
    {
        $this->notImplemented();
    }

    public function unpinPost(\App\Models\Community\CommunityPost $post, \App\Models\Community\CommunityMember $actor): \App\Models\Community\CommunityPost
    {
        $this->notImplemented();
    }

    public function lockPost(\App\Models\Community\CommunityPost $post, \App\Models\Community\CommunityMember $actor, bool $locked = true): \App\Models\Community\CommunityPost
    {
        $this->notImplemented();
    }

    public function attachMedia(\App\Models\Community\CommunityPost $post, \Illuminate\Support\Collection $media): \App\Models\Community\CommunityPost
    {
        $this->notImplemented();
    }

    public function uploadComposerMedia(\App\Models\User $actor, \Illuminate\Http\UploadedFile $file): string
    {
        $this->notImplemented();
    }

    public function changeVisibility(\App\Models\Community\CommunityPost $post, \App\Models\Community\CommunityMember $actor, \App\Enums\Community\CommunityPostVisibility $visibility): \App\Models\Community\CommunityPost
    {
        $this->notImplemented();
    }
}
