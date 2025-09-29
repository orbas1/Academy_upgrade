<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Enums\Community\CommunityPointsEvent;
use App\Models\Community\Community;
use App\Models\Community\CommunityMember;
use App\Models\Community\CommunityPointsLedger;
use App\Models\Community\CommunityPointsRule;
use Illuminate\Support\Collection;

/**
 * Contract for awarding and reconciling community points.
 */
interface PointsService
{
    public function awardPoints(CommunityMember $member, CommunityPointsEvent $event, array $context = []): CommunityPointsLedger;

    public function revokePoints(CommunityPointsLedger $ledger, ?string $reason = null): void;

    public function getBalance(CommunityMember $member): int;

    public function getAvailableRules(Community $community): Collection;

    public function syncRules(Community $community, Collection $rules): void;
}
