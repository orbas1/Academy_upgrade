<?php

declare(strict_types=1);

namespace App\Events\Community;

use App\Domain\Communities\Models\CommunityMember as DomainCommunityMember;
use App\Domain\Communities\Models\CommunityPost as DomainCommunityPost;
use App\Domain\Communities\Models\CommunityPostLike as DomainCommunityPostLike;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityPostLike;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostLiked
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public CommunityMember|DomainCommunityMember $member;

    public CommunityPost|DomainCommunityPost $post;

    public CommunityPostLike|DomainCommunityPostLike $like;

    /**
     * @var array<string, mixed>
     */
    public array $context;

    public function __construct(
        CommunityMember|DomainCommunityMember $member,
        CommunityPost|DomainCommunityPost $post,
        CommunityPostLike|DomainCommunityPostLike $like,
        array $context = []
    ) {
        $this->member = $member;
        $this->post = $post;
        $this->like = $like;
        $this->context = $context + [
            'community_id' => $post->community_id,
            'post_id' => $post->getKey(),
            'like_id' => $like->getKey(),
            'actor_id' => $member->user_id,
            'reaction' => $like->reaction,
        ];
    }
}
