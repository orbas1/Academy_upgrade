<?php

declare(strict_types=1);

namespace App\Models\Community;

use App\Domain\Communities\Models\Community as DomainCommunity;

/**
 * Thin wrapper around the domain Community model to expose a PSR-4 location
 * aligned with the enterprise package blueprint.
 */
class Community extends DomainCommunity
{
}
