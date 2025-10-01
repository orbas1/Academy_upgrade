<?php

namespace Tests\Feature\Console;

use App\Models\AuditLog;
use App\Models\DeviceAccessToken;
use App\Models\DeviceIp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class PrunePersonalDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prunes_records_past_retention_thresholds(): void
    {
        $this->configureRetention();

        $user = User::factory()->create();

        $oldDevice = DeviceIp::create([
            'user_id' => $user->id,
            'user_agent' => 'legacy-device',
            'ip_address' => '192.0.2.10',
            'last_seen_at' => now()->subDays(200),
            'revoked_at' => now()->subDays(120),
        ]);

        $oldAccessToken = $user->createToken('device:legacy');
        DeviceAccessToken::create([
            'device_ip_id' => $oldDevice->id,
            'token_id' => $oldAccessToken->accessToken->id,
            'last_used_at' => now()->subDays(120),
        ]);
        PersonalAccessToken::query()->whereKey($oldAccessToken->accessToken->id)->update([
            'created_at' => now()->subDays(200),
            'updated_at' => now()->subDays(120),
            'last_used_at' => now()->subDays(120),
        ]);

        $recentDevice = DeviceIp::create([
            'user_id' => $user->id,
            'user_agent' => 'recent-device',
            'ip_address' => '198.51.100.5',
            'last_seen_at' => now()->subDays(5),
        ]);
        $recentAccessToken = $user->createToken('device:recent');
        DeviceAccessToken::create([
            'device_ip_id' => $recentDevice->id,
            'token_id' => $recentAccessToken->accessToken->id,
            'last_used_at' => now()->subDay(),
        ]);

        $staleToken = PersonalAccessToken::forceCreate([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'legacy',
            'token' => hash('sha256', (string) Str::uuid()),
            'abilities' => ['*'],
            'last_used_at' => now()->subDays(120),
            'created_at' => now()->subDays(200),
            'updated_at' => now()->subDays(120),
        ]);

        $oldAudit = AuditLog::create([
            'user_id' => $user->id,
            'actor_role' => 'admin',
            'action' => 'user.delete',
            'ip_address' => '203.0.113.9',
            'user_agent' => 'Test',
            'metadata' => ['reason' => 'cleanup'],
            'performed_at' => now()->subDays(400),
        ]);
        $recentAudit = AuditLog::create([
            'user_id' => $user->id,
            'actor_role' => 'admin',
            'action' => 'user.update',
            'ip_address' => '203.0.113.10',
            'user_agent' => 'Test',
            'metadata' => ['reason' => 'update'],
            'performed_at' => now()->subDay(),
        ]);

        $this->artisan('compliance:prune-personal-data')
            ->expectsOutputToContain('Audit logs pruned')
            ->expectsOutputToContain('Data protection pruning finished successfully.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('audit_logs', ['id' => $oldAudit->id]);
        $this->assertDatabaseHas('audit_logs', ['id' => $recentAudit->id]);

        $this->assertDatabaseMissing('device_ips', ['id' => $oldDevice->id]);
        $this->assertDatabaseHas('device_ips', ['id' => $recentDevice->id]);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $oldAccessToken->accessToken->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $recentAccessToken->accessToken->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $staleToken->id]);
    }

    public function test_dry_run_reports_without_deleting(): void
    {
        $this->configureRetention();

        $user = User::factory()->create();

        $device = DeviceIp::create([
            'user_id' => $user->id,
            'user_agent' => 'dry-run-device',
            'ip_address' => '192.0.2.30',
            'last_seen_at' => now()->subDays(200),
            'revoked_at' => now()->subDays(120),
        ]);

        $token = $user->createToken('device:dry-run');
        DeviceAccessToken::create([
            'device_ip_id' => $device->id,
            'token_id' => $token->accessToken->id,
            'last_used_at' => now()->subDays(120),
        ]);
        PersonalAccessToken::query()->whereKey($token->accessToken->id)->update([
            'created_at' => now()->subDays(200),
            'updated_at' => now()->subDays(120),
            'last_used_at' => now()->subDays(120),
        ]);

        $audit = AuditLog::create([
            'user_id' => $user->id,
            'actor_role' => 'system',
            'action' => 'dry-run',
            'ip_address' => '198.51.100.55',
            'user_agent' => 'cli',
            'metadata' => ['note' => 'dry-run'],
            'performed_at' => now()->subDays(400),
        ]);

        $this->artisan('compliance:prune-personal-data --dry-run')
            ->expectsOutputToContain('Audit logs pruned')
            ->expectsOutputToContain('Dry run complete. No records were deleted.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('audit_logs', ['id' => $audit->id]);
        $this->assertDatabaseHas('device_ips', ['id' => $device->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    private function configureRetention(): void
    {
        config()->set('security.data_protection.audit_logs.retention_days', 180);
        config()->set('security.data_protection.device_sessions.retention_days', 90);
        config()->set('security.data_protection.personal_access_tokens.retention_days', 45);
        config()->set('security.data_protection.backup.enabled', false);
    }
}
