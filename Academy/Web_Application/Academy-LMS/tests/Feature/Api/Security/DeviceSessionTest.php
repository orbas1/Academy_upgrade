<?php

namespace Tests\Feature\Api\Security;

use App\Models\DeviceIp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeviceSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_creates_device_session_and_token(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'status' => 1,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
            'device_token' => 'device-login-test',
        ], [
            'X-Device-Id' => 'device-login-test',
            'X-Device-Name' => 'Pixel 9 Pro',
            'X-Device-Platform' => 'android',
            'X-App-Version' => '2.0.0',
        ]);

        $response->assertStatus(201)->assertJsonStructure([
            'token',
            'access_token',
            'user' => ['id'],
        ]);

        $device = DeviceIp::where('user_id', $user->id)->firstOrFail();

        $this->assertSame('device-login-test', $device->user_agent);
        $this->assertSame('Pixel 9 Pro', $device->device_name);
        $this->assertSame('android', $device->platform);
        $this->assertSame('2.0.0', $device->app_version);

        $this->assertDatabaseHas('device_access_tokens', [
            'device_ip_id' => $device->id,
        ]);
    }

    public function test_can_list_and_revoke_device_sessions(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'status' => 1,
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
            'device_token' => 'device-session-primary',
        ], [
            'X-Device-Id' => 'device-session-primary',
            'X-Device-Name' => 'MacBook Pro',
            'X-Device-Platform' => 'macOS',
            'X-App-Version' => '14.0',
        ])->assertStatus(201);

        $accessToken = $loginResponse->json('token');
        $device = DeviceIp::where('user_id', $user->id)->firstOrFail();

        $index = $this->getJson('/api/v1/security/device-sessions', [
            'Authorization' => 'Bearer '.$accessToken,
            'Accept' => 'application/json',
        ])->assertOk()->json();

        $this->assertNotEmpty($index['data']);
        $this->assertSame($device->id, $index['data'][0]['id']);

        $this->deleteJson('/api/v1/security/device-sessions/'.$device->id, [], [
            'Authorization' => 'Bearer '.$accessToken,
            'Accept' => 'application/json',
        ])->assertOk();

        $this->assertDatabaseHas('device_ips', [
            'id' => $device->id,
            'revoked_at' => $device->fresh()->revoked_at,
        ]);

        $this->assertDatabaseMissing('device_access_tokens', [
            'device_ip_id' => $device->id,
        ]);
    }

    public function test_role_middleware_blocks_guest_roles(): void
    {
        $user = User::factory()->create([
            'role' => 'guest',
            'status' => 1,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/top_courses')
            ->assertStatus(403);
    }
}
