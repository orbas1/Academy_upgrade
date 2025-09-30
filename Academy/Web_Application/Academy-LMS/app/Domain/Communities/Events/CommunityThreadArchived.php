<?php

declare(strict_types=1);

namespace App\Domain\Communities\Events;

use App\Domain\Communities\Models\CommunityPost;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommunityThreadArchived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly CommunityPost $post,
        public readonly string $reason,
        public readonly array $context = []
    ) {
    }
}
