<?php

declare(strict_types=1);

namespace App\Events\Community;

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

    public CommunityMember $member;

    public CommunityPost $post;

    public CommunityPostLike $like;

    /**
     * @var array<string, mixed>
     */
    public array $context;

    public function __construct(CommunityMember $member, CommunityPost $post, CommunityPostLike $like, array $context = [])
    {
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
