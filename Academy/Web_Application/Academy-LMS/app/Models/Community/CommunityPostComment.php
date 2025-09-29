<?php

declare(strict_types=1);

namespace App\Models\Community;

use App\Domain\Communities\Models\CommunityPostComment as DomainCommunityPostComment;

/**
 * Wrapper to expose post comment model under the new namespace.
 */
class CommunityPostComment extends DomainCommunityPostComment
{
}
