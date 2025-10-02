<?php

namespace App\Services\Security;

use App\Models\AuditLog;
use App\Models\DeviceAccessToken;
use App\Models\DeviceIp;
use App\Models\UploadScan;
use App\Models\UploadUsage;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

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
            'upload_scans_deleted' => 0,
            'quarantine_files_removed' => 0,
            'upload_usage_deleted' => 0,
            'export_archives_deleted' => 0,
        ];

        $results['audit_logs_deleted'] = $this->pruneAuditLogs($dryRun);

        /** @var array{device_sessions_deleted: int, device_tokens_deleted: int, personal_access_tokens_deleted: int, personal_access_tokens_retained: array<int>} $deviceResult */
        $deviceResult = $this->pruneDeviceSessions($dryRun);
        $results['device_sessions_deleted'] = $deviceResult['device_sessions_deleted'];
        $results['device_tokens_deleted'] = $deviceResult['device_tokens_deleted'];
        $results['personal_access_tokens_deleted'] += $deviceResult['personal_access_tokens_deleted'];

        $results['personal_access_tokens_deleted'] += $this->pruneStandaloneTokens(
            $dryRun,
            $deviceResult['personal_access_tokens_retained']
        );

        /** @var array{deleted: int, quarantine_deleted: int} $scanResult */
        $scanResult = $this->pruneUploadScans($dryRun);
        $results['upload_scans_deleted'] = $scanResult['deleted'];
        $results['quarantine_files_removed'] = $scanResult['quarantine_deleted'];

        $results['upload_usage_deleted'] = $this->pruneUploadUsage($dryRun);

        $results['export_archives_deleted'] = $this->pruneExportArchives($dryRun);

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
     * @return array{deleted: int, quarantine_deleted: int}
     */
    private function pruneUploadScans(bool $dryRun): array
    {
        $retentionDays = (int) $this->config->get('security.data_protection.upload_scans.retention_days', 60);
        if ($retentionDays <= 0) {
            return ['deleted' => 0, 'quarantine_deleted' => 0];
        }

        $cutoff = CarbonImmutable::now()->subDays($retentionDays);
        $query = UploadScan::query()
            ->whereNotNull('scanned_at')
            ->where('scanned_at', '<', $cutoff);

        if ($dryRun) {
            return [
                'deleted' => (int) $query->count(),
                'quarantine_deleted' => 0,
            ];
        }

        $deleted = 0;
        $quarantineDeleted = 0;
        $quarantineRetention = max((int) $this->config->get('security.data_protection.upload_scans.quarantine_retention_days', 30), 0);
        $quarantineCutoff = CarbonImmutable::now()->subDays($quarantineRetention);

        $query->chunkById(100, function (Collection $scans) use (&$deleted, &$quarantineDeleted, $quarantineCutoff): void {
            /** @var UploadScan $scan */
            foreach ($scans as $scan) {
                if ($scan->quarantine_path && File::exists($scan->quarantine_path)) {
                    try {
                        $lastModified = CarbonImmutable::createFromTimestamp(File::lastModified($scan->quarantine_path));
                        if ($quarantineCutoff->greaterThanOrEqualTo($lastModified)) {
                            File::delete($scan->quarantine_path);
                            $quarantineDeleted++;
                        }
                    } catch (Throwable) {
                        // Ignore filesystem errors to avoid blocking retention
                    }
                }

                $scan->delete();
                $deleted++;
            }
        });

        return [
            'deleted' => $deleted,
            'quarantine_deleted' => $quarantineDeleted,
        ];
    }

    private function pruneExportArchives(bool $dryRun): int
    {
        $retentionDays = (int) $this->config->get('security.data_protection.exports.retention_days', 30);
        if ($retentionDays <= 0) {
            return 0;
        }

        $diskName = $this->config->get('security.data_protection.exports.disk', $this->config->get('compliance.export_disk', 'local'));
        $path = trim((string) $this->config->get('security.data_protection.exports.path', 'compliance/exports'), '/');

        try {
            $disk = Storage::disk($diskName);
        } catch (Throwable) {
            return 0;
        }

        $deadline = CarbonImmutable::now()->subDays($retentionDays)->getTimestamp();
        $deleted = 0;

        foreach ($disk->files($path) as $file) {
            if ($disk->lastModified($file) < $deadline) {
                if (! $dryRun) {
                    $disk->delete($file);
                }
                $deleted++;
            }
        }

        return $deleted;
    }

    private function pruneUploadUsage(bool $dryRun): int
    {
        $retentionDays = (int) $this->config->get('security.data_protection.upload_usage.retention_days', 365);
        if ($retentionDays <= 0) {
            return 0;
        }

        $cutoff = CarbonImmutable::now()->subDays($retentionDays);
        $query = UploadUsage::query()->where('created_at', '<', $cutoff);

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

    /**
     * @param array<int, int> $excludedTokenIds
     */
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
