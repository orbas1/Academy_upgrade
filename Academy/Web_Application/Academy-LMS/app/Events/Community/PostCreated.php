<?php

declare(strict_types=1);

namespace App\Events\Community;

use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPost;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public CommunityPost $post;

    public CommunityMember $member;

    /**
     * @var array<string, mixed>
     */
    public array $context;

    public function __construct(CommunityPost $post, CommunityMember $member, array $context = [])
    {
        $this->post = $post;
        $this->member = $member;
        $this->context = $context + [
            'community_id' => $post->community_id,
            'post_id' => $post->getKey(),
            'actor_id' => $member->user_id,
        ];
    }
}
