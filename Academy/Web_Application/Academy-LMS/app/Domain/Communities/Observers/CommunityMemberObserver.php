<?php

namespace App\Domain\Communities\Observers;

use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPointsLedger;

class CommunityMemberObserver
{
    public function deleting(CommunityMember $member): void
    {
        if ($member->isForceDeleting()) {
            CommunityPointsLedger::where('member_id', $member->id)->delete();
        }
    }
}
