<?php

declare(strict_types=1);

namespace App\Models\Community;

use App\Domain\Communities\Models\CommunityMember as DomainCommunityMember;

/**
 * Adapter for community members to support the consolidated models namespace.
 */
class CommunityMember extends DomainCommunityMember
{
}
