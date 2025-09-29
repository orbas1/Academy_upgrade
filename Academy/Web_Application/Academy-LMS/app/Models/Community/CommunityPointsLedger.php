<?php

declare(strict_types=1);

namespace App\Models\Community;

use App\Domain\Communities\Models\CommunityPointsLedger as DomainCommunityPointsLedger;

/**
 * Wrapper exposing ledger entries under the PSR-4 models namespace.
 */
class CommunityPointsLedger extends DomainCommunityPointsLedger
{
}
