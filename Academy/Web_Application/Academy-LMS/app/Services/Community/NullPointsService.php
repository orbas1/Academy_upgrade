<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Services\Community\Concerns\NotImplemented;

class NullPointsService implements PointsService
{
    use NotImplemented;
    public function awardPoints(\App\Models\Community\CommunityMember $member, \App\Enums\Community\CommunityPointsEvent $event, array $context = []): \App\Models\Community\CommunityPointsLedger
    {
        $this->notImplemented();
    }

    public function revokePoints(\App\Models\Community\CommunityPointsLedger $ledger, ?string $reason = null): void
    {
        $this->notImplemented();
    }

    public function getBalance(\App\Models\Community\CommunityMember $member): int
    {
        $this->notImplemented();
    }

    public function getAvailableRules(\App\Models\Community\Community $community): \Illuminate\Support\Collection
    {
        $this->notImplemented();
    }

    public function syncRules(\App\Models\Community\Community $community, \Illuminate\Support\Collection $rules): void
    {
        $this->notImplemented();
    }
}
