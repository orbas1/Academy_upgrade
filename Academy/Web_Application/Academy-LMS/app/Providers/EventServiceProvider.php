<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\Community\CommentCreated;
use App\Events\Community\MemberApproved;
use App\Events\Community\MemberJoined;
use App\Events\Community\PaymentSucceeded;
use App\Events\Community\PointsAwarded;
use App\Events\Community\PostCreated;
use App\Events\Community\PostLiked;
use App\Events\Community\SubscriptionStarted;
use App\Listeners\Community\DispatchCommentCreatedNotifications;
use App\Listeners\Community\DispatchPostCreatedNotifications;
use App\Listeners\Community\HandlePaymentSucceeded;
use App\Listeners\Community\QueueWelcomeMessage;
use App\Listeners\Community\RecordPointsLedgerEntry;
use App\Listeners\Community\SendWelcomeNotification;
use App\Listeners\Community\SyncSubscriptionEntitlements;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        MemberJoined::class => [
            SendWelcomeNotification::class,
        ],
        MemberApproved::class => [
            SendWelcomeNotification::class,
            QueueWelcomeMessage::class,
        ],
        PostCreated::class => [
            DispatchPostCreatedNotifications::class,
        ],
        PostLiked::class => [
            DispatchPostCreatedNotifications::class,
        ],
        CommentCreated::class => [
            DispatchCommentCreatedNotifications::class,
        ],
        PointsAwarded::class => [
            RecordPointsLedgerEntry::class,
        ],
        SubscriptionStarted::class => [
            SyncSubscriptionEntitlements::class,
        ],
        PaymentSucceeded::class => [
            HandlePaymentSucceeded::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
