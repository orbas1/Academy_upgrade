<?php

declare(strict_types=1);

namespace App\Domain\Communities\Services;

use App\Domain\Communities\DTO\CommunityLoadTestOptions;
use App\Domain\Communities\DTO\CommunityLoadTestSummary;
use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Domain\Communities\Models\CommunityPointsLedger;
use App\Domain\Communities\Models\CommunityPost;
use App\Domain\Communities\Models\CommunityPostComment;
use App\Domain\Communities\Models\CommunityPostLike;
use App\Domain\Communities\Models\CommunitySubscriptionTier;
use App\Domain\Communities\Models\ProfileActivity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class CommunityLoadTestSeeder
{
    public function __construct(private readonly DatabaseManager $database)
    {
    }

    /**
     * @param  CommunityLoadTestOptions|array<string, mixed>  $config
     */
    public function seed(CommunityLoadTestOptions|array $config = []): CommunityLoadTestSummary
    {
        $options = $config instanceof CommunityLoadTestOptions
            ? $config
            : CommunityLoadTestOptions::fromArray($config);

        return $this->database->transaction(function () use ($options): CommunityLoadTestSummary {
            $communitiesCreated = 0;
            $membersCreated = 0;
            $postsCreated = 0;
            $commentsCreated = 0;
            $reactionsCreated = 0;
            $pointsEventsCreated = 0;
            $profileActivitiesCreated = 0;
            $credentialPayloads = [];

            $now = CarbonImmutable::now();

            for ($index = 1; $index <= $options->communityCount; $index++) {
                $owner = User::factory()->create([
                    'name' => sprintf('Load Owner %d', $index),
                    'email' => sprintf('load-owner-%d-%s@example.com', $index, Str::lower(Str::random(6))),
                    'password' => Hash::make($options->ownerPassword),
                    'role' => 'instructor',
                    'status' => 1,
                    'email_verified_at' => $now,
                ]);

                $community = Community::factory()->create([
                    'name' => sprintf('Load Test Guild %d', $index),
                    'slug' => sprintf('load-guild-%d-%s', $index, Str::lower(Str::random(6))),
                    'created_by' => $owner->getKey(),
                    'updated_by' => $owner->getKey(),
                    'launched_at' => $now->subDays(random_int(1, 5)),
                    'settings' => [
                        'tags' => ['load-test', 'profile-activity'],
                        'location' => [
                            'city' => 'Distributed',
                            'country' => 'Remote',
                        ],
                    ],
                ]);

                $ownerMembership = CommunityMember::factory()
                    ->for($community)
                    ->for($owner, 'user')
                    ->state([
                        'role' => 'owner',
                        'status' => 'active',
                        'joined_at' => $now->subDays(7),
                        'last_seen_at' => $now,
                        'is_online' => true,
                        'points' => 0,
                        'lifetime_points' => 0,
                    ])
                    ->create();

                $tier = CommunitySubscriptionTier::factory()
                    ->for($community)
                    ->create([
                        'name' => 'Scale Plan',
                        'slug' => sprintf('scale-plan-%d', $index),
                        'price_cents' => 3900,
                        'billing_interval' => 'monthly',
                        'metadata' => [
                            'description' => 'Load testing tier',
                        ],
                    ]);

                $members = Collection::make();

                for ($memberIndex = 0; $memberIndex < $options->membersPerCommunity; $memberIndex++) {
                    $memberUser = User::factory()->create([
                        'name' => sprintf('Load Member %d-%d', $index, $memberIndex + 1),
                        'email' => sprintf('load-member-%d-%d-%s@example.com', $index, $memberIndex + 1, Str::lower(Str::random(6))),
                        'password' => Hash::make($options->memberPassword),
                        'role' => 'student',
                        'status' => 1,
                        'email_verified_at' => $now,
                    ]);

                    $member = CommunityMember::factory()
                        ->for($community)
                        ->for($memberUser, 'user')
                        ->state([
                            'role' => 'member',
                            'status' => 'active',
                            'joined_at' => $now->subDays(random_int(1, 30)),
                            'last_seen_at' => $now->subHours(random_int(1, 72)),
                            'is_online' => $memberIndex % 4 === 0,
                            'points' => 0,
                            'lifetime_points' => 0,
                        ])
                        ->create();

                    $members->push([
                        'user' => $memberUser,
                        'membership' => $member,
                    ]);
                }

                $posts = Collection::make();

                foreach ($members as $memberData) {
                    /** @var \App\Models\User $memberUser */
                    $memberUser = $memberData['user'];

                    for ($postIndex = 0; $postIndex < $options->postsPerMember; $postIndex++) {
                        $visibility = $postIndex % 3 === 0 ? 'paid' : 'community';
                        $post = CommunityPost::factory()
                            ->for($community)
                            ->for($memberUser, 'author')
                            ->create([
                                'metadata' => [
                                    'title' => sprintf('Scaling Scenario %d-%d', $memberUser->getKey(), $postIndex + 1),
                                ],
                                'visibility' => $visibility,
                                'paywall_tier_id' => $visibility === 'paid' ? $tier->getKey() : null,
                                'published_at' => $now->subMinutes(random_int(5, 480)),
                                'created_at' => $now->subMinutes(random_int(30, 720)),
                                'updated_at' => $now->subMinutes(random_int(1, 240)),
                            ]);

                        $posts->push($post);
                        ++$postsCreated;

                        if ($options->seedProfileActivity) {
                            ProfileActivity::factory()->create([
                                'user_id' => $memberUser->getKey(),
                                'community_id' => $community->getKey(),
                                'activity_type' => 'community_post.published',
                                'subject_type' => 'community_post',
                                'subject_id' => $post->getKey(),
                                'occurred_at' => $post->published_at ?? $now,
                                'context' => [
                                    'visibility' => $post->visibility,
                                    'post_id' => $post->getKey(),
                                ],
                            ]);

                            ++$profileActivitiesCreated;
                        }

                        $commentCountForPost = 0;
                        for ($commentIndex = 0; $commentIndex < $options->commentsPerPost; $commentIndex++) {
                            $authorData = $members[$commentIndex % $members->count()];
                            /** @var \App\Models\User $commentAuthor */
                            $commentAuthor = $authorData['user'];

                            $comment = CommunityPostComment::factory()
                                ->for($community)
                                ->for($post, 'post')
                                ->for($commentAuthor, 'author')
                                ->create([
                                    'created_at' => $now->subMinutes(random_int(1, 360)),
                                    'updated_at' => $now->subMinutes(random_int(1, 180)),
                                ]);

                            ++$commentCountForPost;
                            ++$commentsCreated;

                            if ($options->seedProfileActivity) {
                                ProfileActivity::factory()->create([
                                    'user_id' => $commentAuthor->getKey(),
                                    'community_id' => $community->getKey(),
                                    'activity_type' => 'community_comment.posted',
                                    'subject_type' => 'community_comment',
                                    'subject_id' => $comment->getKey(),
                                    'occurred_at' => $comment->created_at ?? $now,
                                    'context' => [
                                        'post_id' => $post->getKey(),
                                    ],
                                ]);

                                ++$profileActivitiesCreated;
                            }
                        }

                        $reactionSlots = min($options->reactionsPerPost, $members->count());
                        $reactionMembers = $members->shuffle()->take($reactionSlots);
                        $reactionCountForPost = 0;
                        foreach ($reactionMembers as $reactionData) {
                            /** @var \App\Models\User $reactionUser */
                            $reactionUser = $reactionData['user'];
                            CommunityPostLike::query()->create([
                                'post_id' => $post->getKey(),
                                'user_id' => $reactionUser->getKey(),
                                'reaction' => $reactionCountForPost % 4 === 0 ? 'celebrate' : 'like',
                                'reacted_at' => $now->subMinutes(random_int(1, 240)),
                            ]);

                            ++$reactionCountForPost;
                            ++$reactionsCreated;
                        }

                        $post->forceFill([
                            'comment_count' => $commentCountForPost,
                            'like_count' => $reactionCountForPost,
                        ])->save();
                    }
                }

                foreach ($members as $memberData) {
                    /** @var CommunityMember $membership */
                    $membership = $memberData['membership'];
                    /** @var User $memberUser */
                    $memberUser = $memberData['user'];

                    for ($awardIndex = 0; $awardIndex < $options->pointsEventsPerMember; $awardIndex++) {
                        /** @var CommunityPost $post */
                        $post = $posts[$awardIndex % $posts->count()];
                        $delta = 10 + ($awardIndex % 5) * 5;

                        $membership->points += $delta;
                        $membership->lifetime_points += $delta;
                        $membership->save();

                        CommunityPointsLedger::query()->create([
                            'member_id' => $membership->getKey(),
                            'community_id' => $community->getKey(),
                            'action' => 'loadtest.engagement',
                            'points_delta' => $delta,
                            'balance_after' => $membership->points,
                            'source_type' => CommunityPost::class,
                            'source_id' => $post->getKey(),
                            'acted_by' => $owner->getKey(),
                            'metadata' => [
                                'post_id' => $post->getKey(),
                                'member_user_id' => $memberUser->getKey(),
                            ],
                            'occurred_at' => $now->subMinutes(random_int(1, 720)),
                        ]);

                        ++$pointsEventsCreated;
                    }
                }

                $ownerToken = $owner->createToken(
                    name: 'community-load-test',
                    abilities: ['communities:read', 'profile-activity:read', 'communities:write']
                )->plainTextToken;

                $memberTokens = [];
                $tokenSlots = min($options->tokensPerCommunity, $members->count());
                for ($tokenIndex = 0; $tokenIndex < $tokenSlots; $tokenIndex++) {
                    /** @var User $memberUser */
                    $memberUser = $members[$tokenIndex]['user'];
                    /** @var CommunityMember $membership */
                    $membership = $members[$tokenIndex]['membership'];

                    $memberTokens[] = [
                        'user_id' => (int) $memberUser->getKey(),
                        'member_id' => (int) $membership->getKey(),
                        'email' => $memberUser->email,
                        'password' => $options->memberPassword,
                        'api_token' => $memberUser->createToken(
                            name: 'community-load-test',
                            abilities: ['communities:read', 'profile-activity:read']
                        )->plainTextToken,
                    ];
                }

                $credentialPayloads[] = [
                    'community_id' => (int) $community->getKey(),
                    'slug' => $community->slug,
                    'tier_id' => (int) $tier->getKey(),
                    'owner' => [
                        'user_id' => (int) $owner->getKey(),
                        'member_id' => (int) $ownerMembership->getKey(),
                        'email' => $owner->email,
                        'password' => $options->ownerPassword,
                        'api_token' => $ownerToken,
                    ],
                    'members' => $memberTokens,
                ];

                ++$communitiesCreated;
                $membersCreated += 1 + $members->count();
            }

            return new CommunityLoadTestSummary(
                communities: $communitiesCreated,
                members: $membersCreated,
                posts: $postsCreated,
                comments: $commentsCreated,
                reactions: $reactionsCreated,
                pointsEvents: $pointsEventsCreated,
                profileActivities: $profileActivitiesCreated,
                credentials: ['communities' => $credentialPayloads],
            );
        });
    }
}
