<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Community\CalendarService;
use App\Services\Community\CommentService;
use App\Services\Community\ClassroomLinkService;
use App\Services\Community\EloquentCalendarService;
use App\Services\Community\EloquentCommentService;
use App\Services\Community\EloquentFollowService;
use App\Services\Community\EloquentLeaderboardService;
use App\Services\Community\EloquentGeoService;
use App\Services\Community\EloquentLikeService;
use App\Services\Community\EloquentMembershipService;
use App\Services\Community\EloquentPointsService;
use App\Services\Community\EloquentPostService;
use App\Services\Community\EloquentPaywallService;
use App\Services\Community\EloquentFeedService;
use App\Services\Community\FeedService;
use App\Services\Community\FollowService;
use App\Services\Community\GeoService;
use App\Services\Community\LeaderboardService;
use App\Services\Community\LikeService;
use App\Domain\Communities\Contracts\MembershipService as MembershipContract;
use App\Domain\Communities\Contracts\PointsService as PointsContract;
use App\Domain\Communities\Services\Adapters\MembershipServiceAdapter;
use App\Domain\Communities\Services\Adapters\PointsServiceAdapter;
use App\Services\Community\MembershipService;
use App\Services\Community\NullClassroomLinkService;
use App\Services\Community\StripeSubscriptionService;
use App\Services\Community\PaywallService;
use App\Services\Community\PointsService;
use App\Services\Community\PostService;
use App\Services\Community\SubscriptionService;
use Illuminate\Support\ServiceProvider;

class CommunityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MembershipService::class, EloquentMembershipService::class);
        $this->app->bind(MembershipContract::class, MembershipServiceAdapter::class);
        $this->app->bind(FeedService::class, EloquentFeedService::class);
        $this->app->bind(PostService::class, EloquentPostService::class);
        $this->app->bind(LikeService::class, EloquentLikeService::class);
        $this->app->bind(FollowService::class, EloquentFollowService::class);
        $this->app->bind(PointsService::class, EloquentPointsService::class);
        $this->app->bind(PointsContract::class, PointsServiceAdapter::class);
        $this->app->bind(LeaderboardService::class, EloquentLeaderboardService::class);
        $this->app->bind(GeoService::class, EloquentGeoService::class);
        $this->app->bind(SubscriptionService::class, StripeSubscriptionService::class);
        $this->app->bind(PaywallService::class, EloquentPaywallService::class);
        $this->app->bind(CalendarService::class, EloquentCalendarService::class);
        $this->app->bind(ClassroomLinkService::class, NullClassroomLinkService::class);
        $this->app->bind(CommentService::class, EloquentCommentService::class);
    }

    public function boot(): void
    {
    }
}
