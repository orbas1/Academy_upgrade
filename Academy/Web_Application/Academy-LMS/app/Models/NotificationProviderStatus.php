<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationProviderStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel',
        'provider',
        'healthy',
        'failure_count',
        'last_failure_at',
        'last_recovered_at',
        'metadata',
    ];

    protected $casts = [
        'healthy' => 'boolean',
        'metadata' => 'array',
        'last_failure_at' => 'datetime',
        'last_recovered_at' => 'datetime',
    ];

    public function markFailure(string $reason, array $context = [], int $threshold = 3): void
    {
        $failureCount = $this->failure_count + 1;
        $metadata = $this->metadata ?? [];
        $metadata['last_failure_reason'] = $reason;
        $metadata['last_context'] = $context;

        $now = CarbonImmutable::now();
        $isHealthy = $failureCount < $threshold;

        $this->forceFill([
            'failure_count' => $failureCount,
            'healthy' => $isHealthy,
            'last_failure_at' => $now,
            'metadata' => $metadata,
        ])->save();
    }

    public function markSuccess(): void
    {
        $this->forceFill([
            'healthy' => true,
            'failure_count' => 0,
            'last_recovered_at' => CarbonImmutable::now(),
        ])->save();
    }
}
