<?php

declare(strict_types=1);

namespace App\Models\Community;

use App\Domain\Communities\Models\CommunityLeaderboard as DomainCommunityLeaderboard;

/**
 * Wrapper around leaderboard snapshots for dependency injection clarity.
 */
class CommunityLeaderboard extends DomainCommunityLeaderboard
{
}
