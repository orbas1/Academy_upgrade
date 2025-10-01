<?php

declare(strict_types=1);

namespace App\Events\Community;

use App\Domain\Communities\Models\CommunityMember as DomainCommunityMember;
use App\Domain\Communities\Models\CommunityPost as DomainCommunityPost;
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

    public CommunityPost|DomainCommunityPost $post;

    public CommunityMember|DomainCommunityMember $member;

    /**
     * @var array<string, mixed>
     */
    public array $context;

    public function __construct(CommunityPost|DomainCommunityPost $post, CommunityMember|DomainCommunityMember $member, array $context = [])
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
