<?php

namespace Tests\Feature\Api\Ops;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MigrationRunbookTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_fetch_runbooks(): void
    {
        $user = User::factory()->create([
            'role' => 'owner',
            'status' => 1,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ops/migration-runbooks');

        $response->assertOk();
        $this->assertNotEmpty($response->json('data.runbooks'));
        $this->assertSame('communities_schema_v1', $response->json('data.runbooks.0.key'));
        $this->assertNotEmpty($response->json('data.runbooks.0.steps'));
    }

    public function test_member_cannot_access_runbooks(): void
    {
        $user = User::factory()->create([
            'role' => 'member',
            'status' => 1,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/ops/migration-runbooks')
            ->assertStatus(403);
    }
}
