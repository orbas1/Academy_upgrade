<?php

namespace App\Services\Security;

use App\Exceptions\Security\QuotaExceededException;
use App\Models\UploadUsage;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Arr;

class UploadQuotaService
{
    public function __construct(private readonly ConfigRepository $config)
    {
    }

    public function assertWithinQuota(?int $userId, ?int $communityId, int $bytes): void
    {
        if ($userId !== null) {
            $this->guardScope('user', $userId, $bytes);
        }

        if ($communityId !== null) {
            $this->guardScope('community', $communityId, $bytes);
        }
    }

    public function recordUsage(string $path, int $bytes, array $context = []): UploadUsage
    {
        $payload = [
            'user_id' => $context['user_id'] ?? null,
            'community_id' => $context['community_id'] ?? null,
            'disk' => $context['disk'] ?? null,
            'visibility' => $context['visibility'] ?? 'public',
            'path' => $path,
            'size' => $bytes,
        ];

        return UploadUsage::create($payload);
    }

    public function summarize(?int $userId, ?int $communityId): array
    {
        $now = CarbonImmutable::now();

        return [
            'generated_at' => $now->toIso8601String(),
            'user' => $userId !== null ? $this->scopeSummary('user', $userId, $now) : null,
            'community' => $communityId !== null ? $this->scopeSummary('community', $communityId, $now) : null,
        ];
    }

    private function guardScope(string $scope, int $identifier, int $incomingBytes): void
    {
        $limit = $this->scopeLimitBytes($scope);
        if ($limit <= 0) {
            return;
        }

        $used = $this->bytesUsedWithinWindow($scope, $identifier);
        if ($used + $incomingBytes <= $limit) {
            return;
        }

        $limitMb = round($limit / (1024 * 1024));
        throw new QuotaExceededException(match ($scope) {
            'community' => __('This community has reached its media storage allowance (%s MB).', [$limitMb]),
            default => __('You have reached your personal media storage allowance (%s MB).', [$limitMb]),
        });
    }

    private function scopeSummary(string $scope, int $identifier, CarbonImmutable $now): array
    {
        $limit = $this->scopeLimitBytes($scope);
        $windowDays = max($this->scopeWindowDays($scope), 0);
        $used = $this->bytesUsedWithinWindow($scope, $identifier);
        $remaining = $limit > 0 ? max($limit - $used, 0) : null;

        return [
            'scope' => $scope,
            'scope_id' => $identifier,
            'limit_bytes' => $limit > 0 ? $limit : null,
            'used_bytes' => $used,
            'remaining_bytes' => $remaining,
            'window_duration_days' => $windowDays,
            'window_started_at' => $windowDays > 0 ? $now->subDays($windowDays)->toIso8601String() : null,
        ];
    }

    private function scopeLimitBytes(string $scope): int
    {
        $config = Arr::get($this->config->get('security.uploads.quota'), $scope, []);
        $limitMb = (int) ($config['limit_mb'] ?? 0);

        return $limitMb > 0 ? $limitMb * 1024 * 1024 : 0;
    }

    private function scopeWindowDays(string $scope): int
    {
        $config = Arr::get($this->config->get('security.uploads.quota'), $scope, []);

        return max((int) ($config['window_days'] ?? 30), 0);
    }

    private function bytesUsedWithinWindow(string $scope, int $identifier): int
    {
        $windowDays = $this->scopeWindowDays($scope);
        $query = UploadUsage::query();

        if ($scope === 'user') {
            $query->where('user_id', $identifier);
        } elseif ($scope === 'community') {
            $query->where('community_id', $identifier);
        }

        if ($windowDays > 0) {
            $query->where('created_at', '>=', CarbonImmutable::now()->subDays($windowDays));
        }

        return (int) $query->sum('size');
    }
}
