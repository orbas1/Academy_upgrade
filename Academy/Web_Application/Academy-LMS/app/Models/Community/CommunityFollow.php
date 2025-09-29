<?php

declare(strict_types=1);

namespace App\Models\Community;

use App\Domain\Communities\Models\CommunityFollow as DomainCommunityFollow;

/**
 * Wrapper for follow relationships between community members.
 */
class CommunityFollow extends DomainCommunityFollow
{
}
