<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Community\Community;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunityNotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_and_update_notification_preferences(): void
    {
        $user = User::factory()->create();
        $community = Community::query()->create([
            'slug' => 'test-community',
            'name' => 'Test Community',
            'created_by' => $user->getKey(),
        ]);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/communities/{$community->getKey()}/notification-preferences")
            ->assertOk()
            ->assertJsonPath('data.channel_email', true)
            ->assertJsonPath('data.digest_frequency', 'daily');

        $payload = [
            'channel_email' => false,
            'channel_push' => true,
            'digest_frequency' => 'weekly',
            'muted_events' => ['post.created'],
        ];

        $this->putJson("/api/v1/communities/{$community->getKey()}/notification-preferences", $payload)
            ->assertOk()
            ->assertJsonPath('data.channel_email', false)
            ->assertJsonPath('data.digest_frequency', 'weekly');

        $this->assertDatabaseHas('community_notification_preferences', [
            'user_id' => $user->getKey(),
            'community_id' => $community->getKey(),
            'channel_email' => false,
            'digest_frequency' => 'weekly',
        ]);

        $this->deleteJson("/api/v1/communities/{$community->getKey()}/notification-preferences")
            ->assertNoContent();

        $this->assertDatabaseMissing('community_notification_preferences', [
            'user_id' => $user->getKey(),
            'community_id' => $community->getKey(),
        ]);
    }
}
