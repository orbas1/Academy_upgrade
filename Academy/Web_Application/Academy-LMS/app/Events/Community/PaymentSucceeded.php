<?php

declare(strict_types=1);

namespace App\Events\Community;

use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPost;
use App\Models\Community\CommunityPostComment;
use App\Models\Community\CommunitySubscription;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ${event}
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public CommunityMember $member,
        public ?CommunityPost $post = null,
        public ?CommunityPostComment $comment = null,
        public ?CommunitySubscription $subscription = null,
        public array $context = []
    ) {
    }
}
