<?php

declare(strict_types=1);

namespace App\Events\Community;

use App\Models\Community\CommunityMember;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberJoined
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public CommunityMember $member;

    /**
     * @var array<string, mixed>
     */
    public array $context;

    public function __construct(CommunityMember $member, array $context = [])
    {
        $this->member = $member;
        $this->context = $context + [
            'community_id' => $member->community_id,
            'member_id' => $member->getKey(),
        ];
    }
}
