<?php

declare(strict_types=1);

namespace App\Domain\Communities\DTO;

final class MembershipBackfillReport
{
    public function __construct(
        public readonly int $communitiesProcessed,
        public readonly int $enrollmentsScanned,
        public readonly int $membersCreated,
        public readonly int $membersReactivated,
        public readonly int $membersUpdated,
        public readonly int $recordsSkipped,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'communities_processed' => $this->communitiesProcessed,
            'enrollments_scanned' => $this->enrollmentsScanned,
            'members_created' => $this->membersCreated,
            'members_reactivated' => $this->membersReactivated,
            'members_updated' => $this->membersUpdated,
            'records_skipped' => $this->recordsSkipped,
        ];
    }
}
