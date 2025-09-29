<?php

declare(strict_types=1);

namespace Tests\Unit\Community;

use App\Enums\Community\CommunityJoinPolicy;
use App\Enums\Community\CommunityLeaderboardPeriod;
use App\Enums\Community\CommunityMemberRole;
use App\Enums\Community\CommunityMemberStatus;
use App\Enums\Community\CommunityPointsEvent;
use App\Enums\Community\CommunityPostType;
use App\Enums\Community\CommunityPostVisibility;
use App\Enums\Community\CommunitySubscriptionInterval;
use App\Enums\Community\CommunitySubscriptionStatus;
use App\Enums\Community\CommunityVisibility;
use App\Support\Community\ColumnDefinition;
use App\Support\Community\TableRegistry;
use Illuminate\Support\Arr;
use Tests\TestCase;

class CommunityConfigurationTest extends TestCase
{
    public function test_table_registry_enforces_conventions(): void
    {
        $tables = TableRegistry::all();

        $this->assertNotEmpty($tables, 'Community tables configuration cannot be empty.');
        $this->assertArrayHasKey('communities', $tables);
        $this->assertSame('communities', $tables['communities']);
        $this->assertCount(count($tables), array_unique($tables), 'Community tables must be unique.');

        foreach ($tables as $name) {
            TableRegistry::assertConvention($name);
        }
    }

    public function test_column_definitions_are_snake_case(): void
    {
        $columns = ColumnDefinition::names();

        $this->assertNotEmpty($columns);
        foreach ($columns as $column) {
            $this->assertMatchesRegularExpression('/^[a-z]+(_[a-z]+)*$/', $column);
        }
    }

    public function test_enum_registry_references_backed_enums(): void
    {
        $registry = config('community.enums');

        $this->assertNotEmpty($registry);

        foreach ($registry as $key => $enumClass) {
            $this->assertTrue(enum_exists($enumClass), sprintf('Enum [%s] not found for key [%s].', $enumClass, $key));
            $cases = $enumClass::cases();
            $values = array_map(static fn ($case) => $case->value, $cases);
            foreach ($values as $value) {
                $this->assertMatchesRegularExpression('/^[a-z]+(_[a-z]+)*$/', $value);
            }
        }
    }

    public function test_default_configuration_matches_domain_expectations(): void
    {
        $defaults = config('community.defaults');

        $this->assertSame(0, Arr::get($defaults, 'members.points'));
        $this->assertSame(1, Arr::get($defaults, 'members.level'));
        $this->assertFalse((bool) Arr::get($defaults, 'members.is_online'));
        $this->assertSame('USD', Arr::get($defaults, 'subscription_tiers.currency'));
        $this->assertSame(
            CommunitySubscriptionInterval::MONTH->value,
            Arr::get($defaults, 'subscription_tiers.interval')
        );
    }

    public function test_enum_values_align_with_specification(): void
    {
        $this->assertSame(
            ['public', 'private', 'unlisted'],
            array_map(static fn ($case) => $case->value, CommunityVisibility::cases())
        );
        $this->assertSame(
            ['open', 'request', 'invite'],
            array_map(static fn ($case) => $case->value, CommunityJoinPolicy::cases())
        );
        $this->assertSame(
            ['owner', 'admin', 'moderator', 'member'],
            array_map(static fn ($case) => $case->value, CommunityMemberRole::cases())
        );
        $this->assertSame(
            ['active', 'pending', 'banned', 'left'],
            array_map(static fn ($case) => $case->value, CommunityMemberStatus::cases())
        );
        $this->assertSame(
            ['text', 'image', 'video', 'link', 'poll'],
            array_map(static fn ($case) => $case->value, CommunityPostType::cases())
        );
        $this->assertSame(
            ['community', 'public', 'paid'],
            array_map(static fn ($case) => $case->value, CommunityPostVisibility::cases())
        );
        $this->assertSame(
            ['daily', 'weekly', 'monthly', 'alltime'],
            array_map(static fn ($case) => $case->value, CommunityLeaderboardPeriod::cases())
        );
        $this->assertSame(
            ['post', 'comment', 'like_received', 'login_streak', 'course_complete', 'assignment_submit'],
            array_map(static fn ($case) => $case->value, CommunityPointsEvent::cases())
        );
        $this->assertSame(
            ['active', 'cancelled', 'past_due'],
            array_map(static fn ($case) => $case->value, CommunitySubscriptionStatus::cases())
        );
    }
}
