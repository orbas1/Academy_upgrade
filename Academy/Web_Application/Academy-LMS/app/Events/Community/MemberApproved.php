<?php

declare(strict_types=1);

namespace App\Events\Community;

use App\Models\Community\CommunityMember;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberApproved
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public CommunityMember $member;

    public ?User $approver;

    /**
     * @var array<string, mixed>
     */
    public array $context;

    public function __construct(CommunityMember $member, ?User $approver = null, array $context = [])
    {
        $this->member = $member;
        $this->approver = $approver;
        $this->context = $context + [
            'community_id' => $member->community_id,
            'member_id' => $member->getKey(),
            'actor_id' => $approver?->getKey(),
        ];
    }
}
