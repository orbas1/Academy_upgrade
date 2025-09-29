<?php

declare(strict_types=1);

namespace App\Listeners\Community;

use App\Events\Community\CommentCreated;
use App\Events\Community\MemberApproved;
use App\Events\Community\MemberJoined;
use App\Events\Community\PaymentSucceeded;
use App\Events\Community\PointsAwarded;
use App\Events\Community\PostCreated;
use App\Events\Community\SubscriptionStarted;

class ${listener}
{
    public function handle(
        MemberJoined|MemberApproved|PostCreated|CommentCreated|PointsAwarded|SubscriptionStarted|PaymentSucceeded $event
    ): void {
        // Event handling will be implemented in Section 2.4.
    }
}
