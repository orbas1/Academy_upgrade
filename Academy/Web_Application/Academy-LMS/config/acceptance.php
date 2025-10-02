<?php

declare(strict_types=1);

return [
    'requirements' => [
        [
            'id' => 'AC-01',
            'title' => 'Community domain foundation',
            'description' => 'Eloquent models and ledgers representing communities, memberships, feed items, paywalls, and progression.',
            'tags' => ['backend', 'domain'],
            'checks' => [
                ['type' => 'class', 'identifier' => App\Models\Community\Community::class],
                ['type' => 'class', 'identifier' => App\Models\Community\CommunityMember::class],
                ['type' => 'class', 'identifier' => App\Models\Community\CommunityPost::class],
                ['type' => 'class', 'identifier' => App\Models\Community\CommunitySubscriptionTier::class],
                ['type' => 'class', 'identifier' => App\Models\Community\CommunityPointsLedger::class],
                ['type' => 'class', 'identifier' => App\Models\Community\CommunityLevel::class],
            ],
            'evidence' => [
                ['type' => 'feature-test', 'identifier' => Tests\Feature\CommunityControllerTest::class],
                ['type' => 'feature-test', 'identifier' => Tests\Feature\CommunityFeedApiTest::class],
                ['type' => 'feature-test', 'identifier' => Tests\Feature\CommunityMemberControllerTest::class],
            ],
        ],
        [
            'id' => 'AC-02',
            'title' => 'API, policies, and services',
            'description' => 'REST controllers, authorization policies, and feed/paywall services exposed to clients.',
            'tags' => ['backend', 'api'],
            'checks' => [
                ['type' => 'class', 'identifier' => App\Http\Controllers\Api\V1\Community\CommunityController::class],
                ['type' => 'class', 'identifier' => App\Http\Controllers\Api\V1\Community\CommunityFeedController::class],
                ['type' => 'class', 'identifier' => App\Policies\Community\CommunityPolicy::class],
                ['type' => 'class', 'identifier' => App\Policies\Community\CommunityPostPolicy::class],
                ['type' => 'class', 'identifier' => App\Services\Community\EloquentFeedService::class],
                ['type' => 'class', 'identifier' => App\Services\Community\EloquentMembershipService::class],
            ],
            'evidence' => [
                ['type' => 'feature-test', 'identifier' => Tests\Feature\AdminCommunityApiTest::class],
                ['type' => 'feature-test', 'identifier' => Tests\Feature\CommunityFeedApiTest::class],
                ['type' => 'feature-test', 'identifier' => Tests\Feature\CommunityNotificationPreferencesTest::class],
            ],
        ],
        [
            'id' => 'AC-03',
            'title' => 'Mobile community parity',
            'description' => 'Flutter community explorer, detail, and presence controllers deliver parity with web experiences.',
            'tags' => ['mobile', 'flutter'],
            'checks' => [
                ['type' => 'file', 'identifier' => '../../Student Mobile APP/academy_lms_app/lib/features/communities/presentation/community_explorer_screen.dart'],
                ['type' => 'file', 'identifier' => '../../Student Mobile APP/academy_lms_app/lib/features/communities/presentation/community_detail_screen.dart'],
                ['type' => 'file', 'identifier' => '../../Student Mobile APP/academy_lms_app/lib/features/communities/state/community_notifier.dart'],
                ['type' => 'file', 'identifier' => '../../Student Mobile APP/academy_lms_app/lib/features/communities/state/community_presence_notifier.dart'],
                ['type' => 'file', 'identifier' => '../../Student Mobile APP/academy_lms_app/test/features/communities/community_feed_repository_test.dart'],
            ],
            'evidence' => [
                ['type' => 'flutter-test', 'identifier' => 'test/features/communities/community_presence_notifier_test.dart'],
                ['type' => 'flutter-test', 'identifier' => 'test/features/communities/community_feed_repository_test.dart'],
            ],
        ],
        [
            'id' => 'AC-04',
            'title' => 'Operational readiness & reporting',
            'description' => 'Operational tooling, acceptance reporting, and documentation provide go-live guardrails.',
            'tags' => ['operations', 'compliance'],
            'checks' => [
                ['type' => 'class', 'identifier' => App\Console\Commands\CommunitySeedBaselineCommand::class],
                ['type' => 'class', 'identifier' => App\Console\Commands\CommunityBackfillMembershipCommand::class],
                ['type' => 'file', 'identifier' => '../../docs/upgrade/artifacts/progress_tracker.md'],
                ['type' => 'file', 'identifier' => '../../docs/upgrade/testing/stage12_acceptance_report.md'],
            ],
            'evidence' => [
                ['type' => 'artisan-command', 'identifier' => 'community:seed-baseline'],
                ['type' => 'artisan-command', 'identifier' => 'community:backfill-membership'],
                ['type' => 'documentation', 'identifier' => 'docs/upgrade/testing/stage12_acceptance_report.md'],
            ],
        ],
    ],
];
