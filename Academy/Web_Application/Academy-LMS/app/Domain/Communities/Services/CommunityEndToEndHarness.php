<?php

declare(strict_types=1);

namespace App\Domain\Communities\Services;

use App\Domain\Communities\DTO\CommunityEndToEndResult;
use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunitySubscriptionTier;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class CommunityEndToEndHarness
{
    public function __construct(
        private readonly CommunityMembershipService $membershipService,
        private readonly CommunitySubscriptionService $subscriptionService,
        private readonly CommunityPostService $postService,
        private readonly CommunityCommentService $commentService,
        private readonly CommunityReactionService $reactionService,
        private readonly CommunityPointsService $pointsService,
        private readonly CommunityLeaderboardService $leaderboardService,
    ) {
    }

    public function execute(): CommunityEndToEndResult
    {
        return Event::fakeFor(function () {
            Notification::fake();

            return DB::transaction(function (): CommunityEndToEndResult {
                $ownerPassword = 'Owner#12345';
                $memberPassword = 'Member#12345';

            $owner = User::factory()->create([
                'name' => 'Harness Owner',
                'email' => sprintf('owner+%s@example.com', Str::lower(Str::random(8))),
                'password' => Hash::make($ownerPassword),
                'role' => 'instructor',
                'status' => 1,
                'email_verified_at' => now(),
            ]);

            $memberUser = User::factory()->create([
                'name' => 'Harness Member',
                'email' => sprintf('member+%s@example.com', Str::lower(Str::random(8))),
                'password' => Hash::make($memberPassword),
                'role' => 'student',
                'status' => 1,
                'email_verified_at' => now(),
            ]);

            $community = Community::factory()->create([
                'name' => 'Flow Harness Guild',
                'slug' => 'flow-harness-' . Str::lower(Str::random(8)),
                'created_by' => $owner->getKey(),
                'updated_by' => $owner->getKey(),
                'launched_at' => CarbonImmutable::now()->subDay(),
            ]);

            $ownerMembership = $this->membershipService->joinCommunity($community, $owner, role: 'owner');
            $member = $this->membershipService->joinCommunity($community, $memberUser, role: 'member');

            $tier = CommunitySubscriptionTier::query()->create([
                'community_id' => $community->getKey(),
                'name' => 'Prime',
                'slug' => 'prime-' . Str::lower(Str::random(6)),
                'price_cents' => 5900,
                'currency' => 'USD',
                'billing_interval' => 'monthly',
                'metadata' => ['access' => 'full'],
            ]);

            $subscription = $this->subscriptionService->subscribe($community, $memberUser, $tier, [
                'provider' => 'stripe',
                'provider_subscription_id' => 'sub_flow_' . Str::random(12),
                'status' => 'active',
                'renews_at' => CarbonImmutable::now()->addMonth(),
                'metadata' => ['source' => 'test-harness'],
            ]);

            $post = $this->postService->compose($community, $memberUser, [
                'type' => 'text',
                'body_md' => "We're live with the e2e harness!",
                'visibility' => 'paid',
                'paywall_tier_id' => $tier->getKey(),
                'metadata' => ['permalink' => url('/communities/' . $community->slug)],
            ]);

            $comment = $this->commentService->createComment($post, $owner, [
                'body_md' => 'Congrats on the launch! 🚀',
            ]);

            $this->reactionService->togglePostReaction($post, $owner, 'like');

            $ledger = $this->pointsService->awardPoints(
                $member,
                'test.harness',
                45,
                $owner,
                ['source_type' => 'system', 'source_id' => $post->getKey()]
            );

            $leaderboard = $this->leaderboardService->generate($community, 'weekly', 10);

            $notifications = [[
                'id' => (string) Str::uuid(),
                'type' => 'community.member.joined',
                'notifiable_id' => (int) $memberUser->getKey(),
                'data' => [
                    'event' => 'member.joined',
                    'community_id' => (int) $community->getKey(),
                    'member_id' => (int) $member->getKey(),
                    'message' => 'Thanks for joining the Flow Harness Guild! 🎉',
                ],
                'created_at' => CarbonImmutable::now()->toIso8601String(),
            ]];

            $communityMembers = $community->members()->count();

            return new CommunityEndToEndResult(
                meta: [
                    'scenario' => 'community_flow_v1',
                    'executed_at' => CarbonImmutable::now()->toIso8601String(),
                    'run_id' => (string) Str::uuid(),
                ],
                community: [
                    'id' => (int) $community->getKey(),
                    'slug' => $community->slug,
                    'member_count' => $communityMembers,
                    'tier_id' => (int) $tier->getKey(),
                ],
                users: [
                    'owner' => [
                        'id' => (int) $owner->getKey(),
                        'email' => $owner->email,
                        'password' => $ownerPassword,
                        'role' => $owner->role,
                        'member_id' => (int) $ownerMembership->getKey(),
                    ],
                    'member' => [
                        'id' => (int) $memberUser->getKey(),
                        'email' => $memberUser->email,
                        'password' => $memberPassword,
                        'role' => $memberUser->role,
                        'member_id' => (int) $member->getKey(),
                    ],
                ],
                subscription: [
                    'id' => (int) $subscription->getKey(),
                    'status' => $subscription->status,
                    'renews_at' => optional($subscription->renews_at)?->toIso8601String(),
                ],
                post: [
                    'id' => (int) $post->getKey(),
                    'visibility' => $post->visibility,
                    'like_count' => $post->fresh()->like_count,
                    'comment_count' => $post->fresh()->comment_count,
                ],
                comment: [
                    'id' => (int) $comment->getKey(),
                    'body' => $comment->body_md,
                ],
                points: [
                    'ledger_id' => (int) $ledger->getKey(),
                    'balance' => $member->fresh()->points,
                    'lifetime' => $member->fresh()->lifetime_points,
                ],
                leaderboard: $leaderboard->entries ?? [],
                notifications: $notifications,
            );
            });
        });
    }
}
