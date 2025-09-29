<?php

declare(strict_types=1);

namespace App\Models\Community;

use App\Domain\Communities\Models\CommunityPost as DomainCommunityPost;

/**
 * Community post model alias supporting the service layer refactor.
 */
class CommunityPost extends DomainCommunityPost
{
}
