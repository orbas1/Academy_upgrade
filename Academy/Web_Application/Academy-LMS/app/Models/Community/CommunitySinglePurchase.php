<?php

declare(strict_types=1);

namespace App\Models\Community;

use App\Domain\Communities\Models\CommunitySinglePurchase as DomainCommunitySinglePurchase;

/**
 * Wrapper exposing one-off purchase entitlements.
 */
class CommunitySinglePurchase extends DomainCommunitySinglePurchase
{
}
