<?php

declare(strict_types=1);

namespace App\Http\Controllers\Testing;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunitySubscriptionTier;
use App\Domain\Communities\Services\CommunityCommentService;
use App\Domain\Communities\Services\CommunityLeaderboardService;
use App\Domain\Communities\Services\CommunityMembershipService;
use App\Domain\Communities\Services\CommunityPointsService;
use App\Domain\Communities\Services\CommunityPostService;
use App\Domain\Communities\Services\CommunityReactionService;
use App\Domain\Communities\Services\CommunitySubscriptionService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

class CommunityFlowTestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return DB::transaction(function (): JsonResponse {
            $owner = User::factory()->create([
                'role' => 'instructor',
                'status' => 1,
            ]);

            $memberUser = User::factory()->create([
                'role' => 'student',
                'status' => 1,
            ]);

            $community = Community::factory()->create([
                'name' => 'Flow Harness Guild',
                'slug' => 'flow-harness-' . uniqid(),
                'created_by' => $owner->getKey(),
                'updated_by' => $owner->getKey(),
                'launched_at' => CarbonImmutable::now()->subDay(),
            ]);

            $membershipService = app(CommunityMembershipService::class);
            $subscriptionService = app(CommunitySubscriptionService::class);
            $postService = app(CommunityPostService::class);
            $commentService = app(CommunityCommentService::class);
            $reactionService = app(CommunityReactionService::class);
            $pointsService = app(CommunityPointsService::class);
            $leaderboardService = app(CommunityLeaderboardService::class);

            $ownerMembership = $membershipService->joinCommunity($community, $owner, role: 'owner');
            $member = $membershipService->joinCommunity($community, $memberUser, role: 'member');

            $tier = CommunitySubscriptionTier::query()->create([
                'community_id' => $community->getKey(),
                'name' => 'Prime',
                'slug' => 'prime-' . uniqid(),
                'price_cents' => 5900,
                'currency' => 'USD',
                'billing_interval' => 'month',
                'metadata' => ['access' => 'full'],
            ]);

            $subscription = $subscriptionService->subscribe($community, $memberUser, $tier, [
                'provider' => 'stripe',
                'provider_subscription_id' => 'sub_flow_' . uniqid(),
                'status' => 'active',
                'renews_at' => CarbonImmutable::now()->addMonth(),
                'metadata' => ['source' => 'test-harness'],
            ]);

            $post = $postService->compose($community, $memberUser, [
                'type' => 'text',
                'body_md' => "We're live with the e2e harness!",
                'visibility' => 'paid',
                'paywall_tier_id' => $tier->getKey(),
                'metadata' => ['permalink' => url('/communities/' . $community->slug)],
            ]);

            $comment = $commentService->createComment($post, $owner, [
                'body_md' => 'Congrats on the launch! 🚀',
            ]);

            $reactionService->togglePostReaction($post, $owner, 'like');

            $ledger = $pointsService->awardPoints(
                $member,
                'test.harness',
                45,
                $owner,
                ['source_type' => 'system', 'source_id' => $post->getKey()]
            );

            $leaderboard = $leaderboardService->generate($community, 'weekly', 10);

            $notifications = DatabaseNotification::query()
                ->orderByDesc('created_at')
                ->limit(5)
                ->get()
                ->map(fn (DatabaseNotification $notification) => [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'notifiable_id' => $notification->notifiable_id,
                    'created_at' => optional($notification->created_at)->toIso8601String(),
                ])
                ->all();

            return response()->json([
                'status' => 'ok',
                'community' => [
                    'id' => $community->getKey(),
                    'slug' => $community->slug,
                    'member_count' => $community->members()->count(),
                ],
                'subscription' => [
                    'id' => $subscription->getKey(),
                    'status' => $subscription->status,
                    'renews_at' => optional($subscription->renews_at)->toIso8601String(),
                ],
                'post' => [
                    'id' => $post->getKey(),
                    'visibility' => $post->visibility,
                    'like_count' => $post->fresh()->like_count,
                    'comment_count' => $post->fresh()->comment_count,
                ],
                'comment' => [
                    'id' => $comment->getKey(),
                    'body' => $comment->body_md,
                ],
                'points' => [
                    'ledger_id' => $ledger->getKey(),
                    'balance' => $member->fresh()->points,
                    'lifetime' => $member->fresh()->lifetime_points,
                ],
                'leaderboard' => $leaderboard->entries,
                'notifications' => $notifications,
            ]);
        });
    }
}
