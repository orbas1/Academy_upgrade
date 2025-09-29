<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Community\CalendarService;
use App\Services\Community\ClassroomLinkService;
use App\Services\Community\FeedService;
use App\Services\Community\FollowService;
use App\Services\Community\GeoService;
use App\Services\Community\LeaderboardService;
use App\Services\Community\LikeService;
use App\Services\Community\MembershipService;
use App\Services\Community\NullCalendarService;
use App\Services\Community\NullClassroomLinkService;
use App\Services\Community\NullFeedService;
use App\Services\Community\NullFollowService;
use App\Services\Community\NullGeoService;
use App\Services\Community\NullLeaderboardService;
use App\Services\Community\NullLikeService;
use App\Services\Community\NullMembershipService;
use App\Services\Community\NullPaywallService;
use App\Services\Community\NullPointsService;
use App\Services\Community\NullPostService;
use App\Services\Community\NullSubscriptionService;
use App\Services\Community\PaywallService;
use App\Services\Community\PointsService;
use App\Services\Community\PostService;
use App\Services\Community\SubscriptionService;
use Illuminate\Support\ServiceProvider;

class CommunityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MembershipService::class, NullMembershipService::class);
        $this->app->bind(FeedService::class, NullFeedService::class);
        $this->app->bind(PostService::class, NullPostService::class);
        $this->app->bind(LikeService::class, NullLikeService::class);
        $this->app->bind(FollowService::class, NullFollowService::class);
        $this->app->bind(PointsService::class, NullPointsService::class);
        $this->app->bind(LeaderboardService::class, NullLeaderboardService::class);
        $this->app->bind(GeoService::class, NullGeoService::class);
        $this->app->bind(SubscriptionService::class, NullSubscriptionService::class);
        $this->app->bind(PaywallService::class, NullPaywallService::class);
        $this->app->bind(CalendarService::class, NullCalendarService::class);
        $this->app->bind(ClassroomLinkService::class, NullClassroomLinkService::class);
    }

    public function boot(): void
    {
    }
}
