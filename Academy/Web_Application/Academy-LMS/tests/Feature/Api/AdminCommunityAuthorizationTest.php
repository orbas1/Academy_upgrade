<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCommunityAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_access_admin_community_routes(): void
    {
        $user = User::factory()->create([
            'role' => 'student',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/admin/communities');

        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_community_routes(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/admin/communities');

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'meta' => ['pagination' => ['type', 'has_more']],
        ]);
    }
}
