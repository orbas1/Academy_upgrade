<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use App\Models\DeviceAccessToken;
use App\Models\DeviceIp;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class DataRetentionService
{
    public function __construct(private readonly Repository $config)
    {
    }

    /**
     * Prune sensitive data stores according to configured retention policies.
     *
     * @return array<string, int>
     */
    public function prune(bool $dryRun = false): array
    {
        $results = [
            'audit_logs_deleted' => 0,
            'device_sessions_deleted' => 0,
            'device_tokens_deleted' => 0,
            'personal_access_tokens_deleted' => 0,
        ];

        $results['audit_logs_deleted'] = $this->pruneAuditLogs($dryRun);

        $deviceResult = $this->pruneDeviceSessions($dryRun);
        $results['device_sessions_deleted'] = $deviceResult['device_sessions_deleted'];
        $results['device_tokens_deleted'] = $deviceResult['device_tokens_deleted'];
        $results['personal_access_tokens_deleted'] += $deviceResult['personal_access_tokens_deleted'];

        $results['personal_access_tokens_deleted'] += $this->pruneStandaloneTokens(
            $dryRun,
            $deviceResult['personal_access_tokens_retained']
        );

        return $results;
    }

    private function pruneAuditLogs(bool $dryRun): int
    {
        $retentionDays = (int) $this->config->get('security.data_protection.audit_logs.retention_days', 3650);
        if ($retentionDays <= 0) {
            return 0;
        }

        $cutoff = CarbonImmutable::now()->subDays($retentionDays);

        $query = AuditLog::query()->where('performed_at', '<', $cutoff);

        if ($dryRun) {
            return (int) $query->count();
        }

        return (int) $query->delete();
    }

    /**
     * @return array{device_sessions_deleted: int, device_tokens_deleted: int, personal_access_tokens_deleted: int, personal_access_tokens_retained: array<int>}
     */
    private function pruneDeviceSessions(bool $dryRun): array
    {
        $retentionDays = (int) $this->config->get('security.data_protection.device_sessions.retention_days', 180);
        if ($retentionDays <= 0) {
            return [
                'device_sessions_deleted' => 0,
                'device_tokens_deleted' => 0,
                'personal_access_tokens_deleted' => 0,
                'personal_access_tokens_retained' => [],
            ];
        }

        $cutoff = CarbonImmutable::now()->subDays($retentionDays);

        $devices = DeviceIp::query()
            ->where(function ($query) use ($cutoff) {
                $query->whereNotNull('revoked_at')
                    ->where('revoked_at', '<', $cutoff);
            })
            ->orWhere(function ($query) use ($cutoff) {
                $query->whereNull('revoked_at')
                    ->where(function ($inner) use ($cutoff) {
                        $inner->whereNull('last_seen_at')
                            ->orWhere('last_seen_at', '<', $cutoff);
                    });
            })
            ->get(['id']);

        if ($devices->isEmpty()) {
            return [
                'device_sessions_deleted' => 0,
                'device_tokens_deleted' => 0,
                'personal_access_tokens_deleted' => 0,
                'personal_access_tokens_retained' => [],
            ];
        }

        $deviceIds = $devices->pluck('id')->all();

        $deviceTokens = DeviceAccessToken::query()
            ->with('token')
            ->whereIn('device_ip_id', $deviceIds)
            ->get();

        $tokenIds = $deviceTokens
            ->pluck('token_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($dryRun) {
            return [
                'device_sessions_deleted' => count($deviceIds),
                'device_tokens_deleted' => $deviceTokens->count(),
                'personal_access_tokens_deleted' => $tokenIds->count(),
                'personal_access_tokens_retained' => $tokenIds->all(),
            ];
        }

        $personalTokensDeleted = 0;
        $deviceTokensDeleted = 0;

        DB::transaction(function () use (
            $deviceIds,
            $tokenIds,
            $deviceTokens,
            &$personalTokensDeleted,
            &$deviceTokensDeleted
        ) {
            if ($tokenIds->isNotEmpty()) {
                $personalTokensDeleted = PersonalAccessToken::query()
                    ->whereIn('id', $tokenIds->all())
                    ->delete();
            }

            if ($deviceTokens->isNotEmpty()) {
                $deviceTokensDeleted = DeviceAccessToken::query()
                    ->whereIn('id', $deviceTokens->pluck('id')->all())
                    ->delete();
            }

            DeviceIp::query()->whereIn('id', $deviceIds)->delete();
        });

        return [
            'device_sessions_deleted' => count($deviceIds),
            'device_tokens_deleted' => $deviceTokensDeleted,
            'personal_access_tokens_deleted' => $personalTokensDeleted,
            'personal_access_tokens_retained' => [],
        ];
    }

    private function pruneStandaloneTokens(bool $dryRun, array $excludedTokenIds): int
    {
        $retentionDays = (int) $this->config->get('security.data_protection.personal_access_tokens.retention_days', 90);
        if ($retentionDays <= 0) {
            return 0;
        }

        $cutoff = CarbonImmutable::now()->subDays($retentionDays);

        $query = PersonalAccessToken::query()
            ->where(function ($inner) use ($cutoff) {
                $inner->whereNull('last_used_at')
                    ->where('created_at', '<', $cutoff);
            })
            ->orWhere(function ($inner) use ($cutoff) {
                $inner->whereNotNull('last_used_at')
                    ->where('last_used_at', '<', $cutoff);
            });

        if (! empty($excludedTokenIds)) {
            $query->whereNotIn('id', $excludedTokenIds);
        }

        if ($dryRun) {
            return (int) $query->count();
        }

        return (int) $query->delete();
    }
}
