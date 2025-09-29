<?php

declare(strict_types=1);

namespace App\Events\Community;

use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPointsLedger;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PointsAwarded
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public CommunityMember $member;

    public CommunityPointsLedger $ledger;

    public int $points;

    public string $action;

    public ?User $actor;

    /**
     * @var array<string, mixed>
     */
    public array $context;

    public function __construct(
        CommunityMember $member,
        CommunityPointsLedger $ledger,
        int $points,
        string $action,
        ?User $actor = null,
        array $context = []
    ) {
        $this->member = $member;
        $this->ledger = $ledger;
        $this->points = $points;
        $this->action = $action;
        $this->actor = $actor;
        $this->context = $context + [
            'community_id' => $member->community_id,
            'member_id' => $member->getKey(),
            'ledger_id' => $ledger->getKey(),
            'actor_id' => $actor?->getKey(),
            'points' => $points,
            'action' => $action,
        ];
    }
}
