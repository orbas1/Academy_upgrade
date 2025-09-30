<?php

declare(strict_types=1);

namespace App\Listeners\Community;

use App\Events\Community\MemberApproved;
use App\Jobs\Community\SendWelcomeDirectMessage;

class QueueWelcomeMessage
{
    public function handle(MemberApproved $event): void
    {
        SendWelcomeDirectMessage::dispatch($event->member->getKey());
    }
}
