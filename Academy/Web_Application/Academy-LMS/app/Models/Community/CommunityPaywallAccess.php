<?php

declare(strict_types=1);

namespace App\Models\Community;

use App\Domain\Communities\Models\CommunityPaywallAccess as DomainCommunityPaywallAccess;

/**
 * Wrapper exposing paywall access entitlements.
 */
class CommunityPaywallAccess extends DomainCommunityPaywallAccess
{
}
