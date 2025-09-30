<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @mixin \Illuminate\Database\Eloquent\Builder */

class NotificationDeliveryMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'notification_id',
        'user_id',
        'channel',
        'event',
        'provider',
        'status',
        'context',
        'occurred_at',
    ];

    protected $casts = [
        'context' => 'array',
        'occurred_at' => 'datetime',
    ];

    public static function record(
        ?string $notificationId,
        ?int $userId,
        string $channel,
        string $status,
        ?string $provider = null,
        ?string $event = null,
        array $context = []
    ): self {
        return self::query()->create([
            'notification_id' => $notificationId,
            'user_id' => $userId,
            'channel' => $channel,
            'status' => $status,
            'provider' => $provider,
            'event' => $event,
            'context' => $context,
            'occurred_at' => CarbonImmutable::now(),
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
