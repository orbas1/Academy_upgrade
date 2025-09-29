<?php

declare(strict_types=1);

namespace App\Models\Community;

use App\Domain\Communities\Models\CommunityPostLike as DomainCommunityPostLike;

/**
 * Wrapper around the community post like aggregate.
 */
class CommunityPostLike extends DomainCommunityPostLike
{
}
