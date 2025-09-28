<?php

namespace Tests\Unit\Security;

use App\Exceptions\Security\TooManyDevicesException;
use App\Models\DeviceIp;
use App\Models\User;
use App\Services\Security\DeviceTrustService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DeviceTrustServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_trust_flow(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $service = app(DeviceTrustService::class);

        $this->assertFalse($service->deviceIsTrusted($user, 'device-token'));

        $service->recordLogin($user, 'device-token', '127.0.0.1', 'session-id', true);

        $this->assertDatabaseHas('device_ips', [
            'user_id' => $user->id,
            'user_agent' => 'device-token',
        ]);

        $this->assertTrue($service->deviceIsTrusted($user, 'device-token'));

        $service->recordLogin($user, 'device-token', '127.0.0.1', 'new-session', false);
        $device = DeviceIp::where('user_agent', 'device-token')->first();

        $this->assertEquals('new-session', $device->session_id);
    }

    public function test_device_limit_triggers_exception(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $service = app(DeviceTrustService::class);

        // create existing devices equal to limit (default 1)
        $service->recordLogin($user, 'existing-device', '127.0.0.1', 'session-one', false);

        Mail::fake();
        $this->expectException(TooManyDevicesException::class);
        $service->recordLogin($user, 'new-device', '127.0.0.1', 'session-two', false);
    }
}
