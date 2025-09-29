<?php

declare(strict_types=1);

namespace App\Models\Community;

use App\Domain\Communities\Models\CommunityCategory as DomainCommunityCategory;

/**
 * Wrapper to reference community categories within the new models namespace.
 */
class CommunityCategory extends DomainCommunityCategory
{
}
