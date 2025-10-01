<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Domain\Communities\Models\Community;
use App\Domain\Communities\Models\CommunityMember;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityBackfillMembershipCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_backfills_memberships_and_is_idempotent(): void
    {
        $community = Community::factory()->create([
            'settings' => [
                'classroom_links' => [
                    101 => [
                        'course_id' => 101,
                        'default_role' => 'student',
                    ],
                ],
            ],
        ]);

        $user = User::factory()->create();
        Enrollment::create([
            'user_id' => $user->getKey(),
            'course_id' => 101,
            'entry_date' => now()->subDays(5)->toDateTimeString(),
        ]);

        $this->artisan('community:backfill-membership', [
            '--community' => (string) $community->slug,
            '--batch' => 50,
        ])->assertExitCode(0);

        $member = CommunityMember::where('community_id', $community->getKey())
            ->where('user_id', $user->getKey())
            ->first();

        $this->assertNotNull($member);
        $this->assertSame('member', $member->role);
        $this->assertSame('active', $member->status);
        $this->assertNotNull($member->joined_at);
        $this->assertArrayHasKey('backfill', $member->metadata ?? []);

        $this->artisan('community:backfill-membership', [
            '--community' => (string) $community->slug,
            '--batch' => 50,
        ])->assertExitCode(0);

        $this->assertSame(1, CommunityMember::where('community_id', $community->getKey())->count());
    }
}
