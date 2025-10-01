<?php

declare(strict_types=1);

namespace App\Events\Community;

use App\Domain\Communities\Models\CommunityMember as DomainCommunityMember;
use App\Domain\Communities\Models\CommunityPost as DomainCommunityPost;
use App\Domain\Communities\Models\CommunityPostComment as DomainCommunityPostComment;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityPostComment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public CommunityMember|DomainCommunityMember $member;

    public CommunityPost|DomainCommunityPost $post;

    public CommunityPostComment|DomainCommunityPostComment $comment;

    /**
     * @var array<string, mixed>
     */
    public array $context;

    public function __construct(
        CommunityMember|DomainCommunityMember $member,
        CommunityPost|DomainCommunityPost $post,
        CommunityPostComment|DomainCommunityPostComment $comment,
        array $context = []
    ) {
        $this->member = $member;
        $this->post = $post;
        $this->comment = $comment;
        $this->context = $context + [
            'community_id' => $post->community_id,
            'post_id' => $post->getKey(),
            'comment_id' => $comment->getKey(),
            'actor_id' => $member->user_id,
        ];
    }
}
